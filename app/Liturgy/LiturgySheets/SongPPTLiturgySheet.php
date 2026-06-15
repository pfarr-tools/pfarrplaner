<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Liturgy\LiturgySheets;


use App\FileFormats\ODP;
use App\FileFormats\PowerPoint;
use App\Helpers\PPTUnitsHelper;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Liturgy\Music\ABCMusic;
use App\Models\Announcements;
use App\Models\Calendar\Occurence;
use App\Models\Liturgy\Item;
use App\Models\Service;
use App\Services\ImageService;
use App\Services\QRService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use PhpOffice\Common\Drawing;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\AutoShape;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Border;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;
use PhpOffice\PhpPresentation\Style\Font;

class SongPPTLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Powerpoint mit Liedern';
    protected $icon = 'fa fa-file-powerpoint';
    protected $extension = 'pptx';

    protected $configurationPage = 'Liturgy/LiturgySheets/SongPPTSongSheetConfiguration';
    protected $configurationComponent = 'SongPPTLiturgySheetConfiguration';

    protected $defaultConfig = [
        'textColor' => 'FFFFFFFF',
        'backgroundColor' => 'FF043b04',
        'backgroundColorEmpty' => 'FF043b04',
        'includeEmpty' => 1,
        'includeJingleAndIntro' => 1,
        'includeCredits' => 1,
        'includeSongList' => 1,
        'includeSongbookReference' => 1,
        'verticalAlignment' => 'b',
        'fontSize' => 40,
        'renderMusic' => false,
        'includeAdLoopStart' => false,
        'includeAdLoopEnd' => false,
        'includeAdLoopElements' => [],
        'showAdsFromCities' => [],
        'adLoopDelay' => 7,
        'includeVirtualSongsheetQR' => false,
        'outputFormat' => 'ppt',
    ];

    protected $counterColor = [
        'FFFFFFFF' => 'FF000000',
        'FF000000' => 'FFFFFFFF',
        'FF043b04' => 'FFFFFFFF',
    ];

    protected $musicColorSet = [
        'FFFFFFFF' => ABCMusic::COLORS_NORMAL,
        'FF000000' => ABCMusic::COLORS_INVERTED,
        'FF043b04' => ABCMusic::COLORS_GREENSCREEN,
    ];

    /**
     * Cache ad events to be listed to prevent multiple queries
     * @var array
     */
    protected $adEventsToBeListed = [];
    /**
     * Cache ad events to be highlighted to prevent multiple queries
     * @var array
     */
    protected $adEventsToBeHighlighted = [];

    /**
     * Slide names (for slide name fix)
     * @var array
     */
    protected $slideNames = [];


    /** @var PhpPresentation */
    protected $ppt;

    public function render(Service $service)
    {
        $this->ppt = new PhpPresentation();
        $this->setDocumentProperties($service);
        $this->ppt->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        $textColor = new Color($this->config['textColor']);
        $highlightedTextColor = new Color('FFF79646');
        $this->ppt->removeSlideByIndex(0);

        if ($this->config['includeAdLoopStart']) {
            $this->renderAdLoop($service);
        }

        if ($this->config['includeSongList']) {
            if (!$this->config['includeAdLoopStart']) {
                $slide = $this->createEmptySlide($this->config['backgroundColor']);
                $this->renderSongListSlide($slide, $service);
            }
        } elseif ($this->config['includeEmpty']) {
            $this->slide();
        }

        if ($this->config['includeVirtualSongsheetQR'] && (!$this->config['includeAdLoopStart'])) {
            $slide = $this->createEmptySlide($this->config['backgroundColor']);
            $this->renderVirtualSongsheetQR($slide, $service);
        }

        if ($this->config['includeJingleAndIntro']) {
            $this->slide('Hier Jingle einfügen');
            $this->slide('Hier Intro einfügen');
        }
        if ($this->config['includeEmpty']) {
            $this->slide();
        }

        if ($this->config['outputFormat'] == 'odp') {
            $this->setExtension('odp');
        }

        $lastItem = null;

        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (in_array($item->id, $this->config['includeAdLoopElements'])) {
                    $this->renderAdLoop($service);
                }
                if ($item->data_type == 'song') {
                    $this->renderSongItem($item);
                } elseif($item->data_type == 'freetext') {
                    if ($lastItem && ($lastItem->data_type=='psalm')) {
                        if (($item->data_type=='freetext') && ($item->title == 'Ehr sei dem Vater') && (isset($item->data['description']))) {
                            /** @var Liturgy\ItemHelpers\FreetextItemHelper $ftHelper */
                            $ftHelper = $item->getHelper();
                            $this->renderGloriaPatriSlide($ftHelper->getText());
                        }
                        if ($this->config['includeEmpty']) {
                            $this->slide();
                        }
                    } else {
                        $this->renderFreeTextSlides($item);
                    }


                } elseif ($item->data_type == 'psalm') {
                    if ($this->config['includeSongbookReference']) {
                        $this->songbookReferenceSlide($item);
                    }
                    /** @var PsalmItemHelper $helper */
                    $helper = $item->getHelper();
                    foreach ($helper->getVerses() as $verse) {
                        $this->slide($verse, $this->config['fontSize']);
                    }
                }

                $lastItem = $item;
            }
        }
        if ($this->config['includeCredits']) {
            $this->creditsSlide($service->credits);
        }
        if ($this->config['includeJingleAndIntro']) {
            $this->slide('Hier Jingle einfügen', 18);
        }

        if ($this->config['includeAdLoopEnd']) {
            $this->renderAdLoop($service, true);
        }


        // ODP needs an additional slide
        if ($this->config['outputFormat'] == 'odp') $this->slide();

        // we need to patch the PPTX to become a PPTM right here:
        if (($this->config['includeAdLoopStart'])
            || $this->config['includeAdLoopEnd']
            || count($this->config['includeAdLoopElements']) && $this->config['adLoopDelay']) {
            if ($this->config['outputFormat'] == 'ppt') $this->setExtension('pptm');
        }

        return $this->sendToBrowser($this->getFileName($service, 'Texte und Lieder'));
    }

    protected function createTextBox($slide, $yOffset, $text, $color = null, $xOffset = 0, $fontSize = null, $bold = false, $alignment = Alignment::HORIZONTAL_LEFT) {
        $color ??= new Color($this->config['textColor']);
        $fontSize ??= $this->config['fontSize'];
        $shape2 = $slide->createRichTextShape()
            ->setOffsetX(PPTUnitsHelper::convert($xOffset, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
            ->setOffsetY(PPTUnitsHelper::convert($yOffset, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
            ->setWidth(
                PPTUnitsHelper::convert(25.4 - $xOffset, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL)
            );
        $paragraph = $shape2->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal($alignment);
        $paragraph->getFont()
            ->setBold($bold)
            ->setSize($fontSize)
            ->setColor($color)
            ->setName('Sarabun');
        $paragraph->createTextRun($text);
    }

    protected function renderFreeTextSlides(Item $item)
    {
        if (!($item->data['slideText'] ?? false)) {
            return;
        }
        $helper = $item->getHelper();

        $slides = collect(explode("\n---", $item->data['slideText']))->map(fn($slideText) => trim($slideText));
        foreach ($slides as $slideText) {
            $this->slide(
                $slideText,
                $this->config['fontSize'],
                $this->config['textColor'],
                true,
                ''
            );
        }

        if ($this->config['includeEmpty']) {
            $this->slide();
        }
    }

    protected function renderSongListSlide(Slide $slide, Service $service)
    {
        $color = new Color($this->config['textColor']);
        $listItems = [];
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (($item->data_type == 'song') && (isset($item->data['song']))){
                    $refColor = '';
                    if (isset($item->data['song']['id'])) {
                        $songRef = \App\Models\Liturgy\SongReference::find($item->data['song']['id']);
                        $refColor = $songRef->color ?? '';
                    };
                    $listItems[] = [
                        'ref' => $item->data['song']['reference'] ?? '',
                        'verses' => $item->data['verses'],
                        'image' => isset($item->data['song']['songbook']) ? ($item->data['song']['songbook']['image'] ?? '') : '',
                        'code' => isset($item->data['song']['songbook']) ? ($item->data['song']['songbook']['code'] ?? '') : '',
                        'color' => $refColor,
                        'psalm' => false,
                    ];
                } elseif (($item->data_type == 'psalm') && isset($item->data['psalm'])) {
                    $refColor = '';
                    $songbook = null;
                    if (isset($item->data['psalm']['songbook_abbreviation'])) {
                        $refColor = ($item->data['psalm']['songbook_abbreviation'] == 'EG') ? '#c8baf7' : '';
                        $songbook = \App\Models\Liturgy\Songbook::where('code', $item->data['psalm']['songbook_abbreviation'])->first();
                    }

                    $listItems[] = [
                        'ref' => $item->data['psalm']['reference'] ?? '',
                        'verses' => $item->data['psalm']['title'] ?? '',
                        'image' => $songbook ? $songbook->image : '',
                        'code' => $item->data['psalm']['songbook_abbreviation'] ?? '',
                        'color' => $refColor,
                        'psalm' => true,
                    ];
                }
            }
        }

        if (count($listItems)) {
            $this->createTextBox($slide, .5, 'Singen & Beten:', $color, 1);
            $offset = 3;
            foreach ($listItems as $listItem) {
                $this->createTextBox(
                    $slide,
                    $offset,
                    $listItem['ref'],
                    $color,
                    6
                );
                if ($listItem['verses']) {
                    $this->createTextBox(
                        $slide,
                        $offset,
                        $listItem['verses'],
                        $color,
                        9.5,
                    );
                }
                $this->createTextBox(
                    $slide,
                    $offset,
                    $listItem['code'],
                    $color,
                    2.5);
                if ($listItem['color']) {
                    $shapeColor = new Color(str_replace('#', 'FF', $listItem['color']));
                    $shape = $slide->createRichTextShape()
                        ->setOffsetY(PPTUnitsHelper::convert($offset+.4, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                        ->setOffsetX(PPTUnitsHelper::convert(2.4, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                        ->setWidth(PPTUnitsHelper::convert(0.2, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                        ->setHeight(PPTUnitsHelper::convert(1, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL));
                    $shape->getFill()->setStartColor($shapeColor)->setEndColor($shapeColor)->setFillType(Fill::FILL_SOLID);
                    $shape->getBorder()->setLineStyle(Border::LINE_NONE);
                }
                if ($listItem['image']) {
                    $shape = $slide->createDrawingShape();
                    $shape->setName('')
                        ->setPath(storage_path('app/' . $listItem['image']))
                        ->setResizeProportional(true)
                        ->setHeight(PPTUnitsHelper::convert(1, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                        ->setOffsetX(PPTUnitsHelper::convert(1.5, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                        ->setOffsetY(PPTUnitsHelper::convert($offset+.4, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL));
                }
                $offset += 1.5;

            }
        }
    }

    protected function renderSongItem(Item $item)
    {
        if ($this->config['includeSongbookReference']) {
            $this->songbookReferenceSlide($item);
        }
        if ($this->config['renderMusic'] && (isset($item->data['song']['song']['notation']))) {
            return $this->renderSongItemWithMusic($item);
        }

        /** @var SongItemHelper $helper */
        $helper = $item->getHelper();
        $copyrights = $item->data['song']['song']['copyrights'] ?? '';
        if ($copyrights) {
            if ($item->data['song']['code']) {
                $copyrights = $item->data['song']['code'] . ' ' . $item->data['song']['reference'] . '. ' . $copyrights;
            }
        }
        $activeVerses = $helper->getActiveVerses();
        foreach ($activeVerses as $verse) {
            $showVerseNumber = !((count($activeVerses) == 1) && ($verse['number'] == 1));

            if ($verse['refrain_before']) {
                $this->slide(
                    $item->data['song']['song']['refrain'],
                    $this->config['fontSize'],
                    $this->config['textColor'],
                    true,
                    $copyrights
                );
            }
            $this->slide(
                ($showVerseNumber ? $verse['number'] . '. ' : '') . $verse['text'],
                $this->config['fontSize'],
                $this->config['textColor'],
                true,
                $copyrights
            );
            if ($verse['refrain_after']) {
                $this->slide(
                    $item->data['song']['song']['refrain'],
                    $this->config['fontSize'],
                    $this->config['textColor'],
                    true,
                    $copyrights
                );
            }
        }
        if ($this->config['includeEmpty']) {
            $this->slide();
        }
    }

    protected function renderSongItemWithMusic(Item $item)
    {
        $song = \App\Models\Liturgy\Song::find($item->data['song']['song_id']);
        $colorSet = $this->musicColorSet[$this->config['backgroundColor']];
        $images = ABCMusic::images($song, $item->data['verses'], $colorSet);

        foreach ($images as $key => $image) {
            $slide = $this->createEmptySlide($this->config['backgroundColor']);
            $shape = $slide->createDrawingShape()
                ->setName($item->data['song']['title'] . ' ' . $key)
                ->setPath($image)
                ->setWidth(940)
                ->setOffsetX(10);
            $shape->setOffsetY(520 - $shape->getHeight());
        }

        if ($this->config['includeEmpty']) {
            $this->slide();
        }
    }

    /**
     * Render a special slide with the Gloria Patri ("Ehr sei dem Vater...")
     * @param $text
     * @return void
     */
    protected function renderGloriaPatriSlide($text) {
        $slide = $this->createEmptySlide($this->config['backgroundColor']);
        $shape = $this->createFullScreenRichTextShape($slide);
        $shape->setOffsetY(100);
        $shape->getActiveParagraph()->createTextRun($text)
            ->getFont()
            ->setItalic(true)
            ->setSize($this->config['fontSize'])
            ->setColor(new Color($this->config['textColor']))
            ->setName('Sarabun');

        $noteFile = $this->config['textColor'] == 'FFFFFFFF' ? base_path('assets/ppt/note_white.svg') : base_path('assets/ppt/note.svg');
        $slide->createDrawingShape()
            ->setPath($noteFile)
            ->setOffsetX(10)
            ->setOffsetY(10)
            ->setHeight(80)
            ->setWidth(80);
    }

    protected function slide($text = '', $size = -1, $rgb = -1, $bold = true, $copyrights = '', $backgroundColor = null)
    {
        if ($size == -1) {
            $size = $this->config['fontSize'];
        }
        if ($rgb == -1) {
            $rgb = $this->config['textColor'];
        }

        $color = new Color($rgb);

        $isOdp = ($this->config['outputFormat'] === 'odp');

        // Normalize $text into an array of "paragraph lines"
        // - honor line breaks inside strings
        // - allow manual slide breaks with a line containing only "---"
        $lines = [];

        if (is_array($text)) {
            foreach ($text as $chunk) {
                if ($chunk === null) {
                    continue;
                }
                $chunk = (string)$chunk;

                // Normalize Windows/Mac newlines
                $chunk = str_replace(["\r\n", "\r"], "\n", $chunk);

                // Split into lines
                foreach (explode("\n", $chunk) as $line) {
                    $lines[] = $line;
                }
            }
        } else {
            $text = (string)$text;
            $text = str_replace(["\r\n", "\r"], "\n", $text);
            $lines = explode("\n", $text);
        }

        // Keep your special-case handling for indented lines that come from "\n\t"
        // (this turns into "\n" + "\t" after the normalization above)
        // Also keep legacy replacement if someone passed "\n\t" literally somewhere
        // (doesn't hurt, but no longer required)
        $lines = array_map(static function ($line) {
            return str_replace("\n\t", "\r\t", $line);
        }, $lines);

        // If there's no text at all, just create one empty slide (and still show copyrights if given)
        if (count($lines) === 0 || (count($lines) === 1 && trim($lines[0]) === '')) {
            $slide = $this->createEmptySlide(
                $backgroundColor ?: $this->config['backgroundColorEmpty']
            );

            if ($copyrights) {
                $copyrightShape = $slide->createRichTextShape()
                    ->setWidth(950)
                    ->setHeight(30)
                    ->setOffsetX(10)
                    ->setOffsetY(485);

                $copyrightParagraph = $copyrightShape->getActiveParagraph();
                $copyrightParagraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $copyrightParagraph->getFont()->setBold(false)->setSize(10)->setColor($color)->setName('Sarabun');
                $copyrightParagraph->createTextRun($copyrights);
            }

            return $slide;
        }

        // Split by manual slide breaks ("---" on a line by itself)
        $manualSlideChunks = [];
        $currentChunk = [];

        foreach ($lines as $line) {
            if (trim($line) === '---') {
                // finalize current chunk (even if empty; we still want a slide)
                $manualSlideChunks[] = $currentChunk;
                $currentChunk = [];
                continue;
            }
            $currentChunk[] = $line;
        }
        // add last chunk
        $manualSlideChunks[] = $currentChunk;

        // Prepare font metrics
        $ttfPath = resource_path($bold ? 'fonts/Sarabun-SemiBold.ttf' : 'fonts/Sarabun-Regular.ttf');
        if (!file_exists($ttfPath)) {
            $ttfPath = resource_path('fonts/Sarabun-SemiBold.ttf');
        }

        // These must match createFullScreenRichTextShape()
        $textAreaWidthPx  = 950;
        $textAreaHeightPx = 500;

        // Small safety margin so we don't touch the bottom edge
        $maxHeightPx = $textAreaHeightPx - 10;

        // Render each manual chunk, auto-splitting into additional slides if it overflows
        $lastCreatedSlide = null;

        foreach ($manualSlideChunks as $chunkLines) {
            // Ensure we at least create a slide for empty chunks
            if (count($chunkLines) === 0) {
                $chunkLines = [''];
            }

            // Repeatedly split until it fits (supports 2+ slides)
            $pendingLines = $chunkLines;

            while (true) {
                [$part1Lines, $part2Lines] = PPTUnitsHelper::splitTextToFitSlide(
                    $pendingLines,
                    (int)$size,
                    (int)$textAreaWidthPx,
                    (int)$maxHeightPx,
                    $ttfPath
                );

                // Create the slide for part1
                $slide = $this->createEmptySlide(
                    $backgroundColor ?: ($part1Lines && trim(implode('', $part1Lines)) !== ''
                        ? $this->config['backgroundColor']
                        : $this->config['backgroundColorEmpty'])
                );

                // Add the main text if any
                if ($part1Lines && !(count($part1Lines) === 1 && trim($part1Lines[0]) === '')) {
                    $shape = $this->createFullScreenRichTextShape($slide);
                    // If your createFullScreenRichTextShape() does not clear paragraphs,
                    // we handle paragraph creation explicitly below.

                    $paragraphIndex = 0;
                    foreach ($part1Lines as $line) {
                        $paragraph = ($paragraphIndex === 0) ? $shape->getActiveParagraph() : $shape->createParagraph();
                        $paragraphIndex++;

                        $line = (string)$line;

                        if (mb_substr($line, 0, 1) === "\t") {
                            $paragraph->getAlignment()->setMarginLeft(35);
                            $line = mb_substr($line, 1);
                        }

                        $paragraph->getAlignment()->setVertical($this->config['verticalAlignment']);
                        $paragraph->getFont()
                            ->setBold($bold)
                            ->setSize($size)
                            ->setColor($color)
                            ->setName('Sarabun');

                        $paragraph->createTextRun(str_replace('&', '**', $line));
                        if ($isOdp) {
                            $paragraph->setLineSpacing(95);
                        }
                    }
                }

                // Copyrights must appear on every slide
                if ($copyrights) {
                    $copyrightShape = $slide->createRichTextShape()
                        ->setWidth(950)
                        ->setHeight(30)
                        ->setOffsetX(10)
                        ->setOffsetY(($part1Lines && trim(implode('', $part1Lines)) !== '') ? 505 : 485);

                    $copyrightParagraph = $copyrightShape->getActiveParagraph();
                    $copyrightParagraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $copyrightParagraph->getFont()->setBold(false)->setSize(10)->setColor($color)->setName('Sarabun');
                    $copyrightParagraph->createTextRun($copyrights);
                }

                $lastCreatedSlide = $slide;

                // If there's no overflow, we're done with this manual chunk
                if (!$part2Lines || trim(implode('', $part2Lines)) === '') {
                    break;
                }

                // Continue with remainder
                $pendingLines = $part2Lines;
            }
        }

        return $lastCreatedSlide;
    }

    protected function songbookReferenceSlide(Item $item)
    {
        $data = $item->data;
        if ($item->data_type == 'song') {
            $key = 'song';
            if (!isset($item->data['song'])) {
                return;
            }
            if (!isset($item->data['song']['songbook'])) {
                return;
            }
        } elseif ($item->data_type == 'psalm') {
            $key = 'psalm';
            if (!isset($item->data['psalm'])) {
                return;
            }
            if (isset($item->data['psalm']['songbook_abbreviation'])) {
                $songbook = \App\Models\Liturgy\Songbook::where('code', $item->data['psalm']['songbook_abbreviation'])->first();
                $data[$key]['songbook'] = [
                    'code' => $item->data['psalm']['songbook_abbreviation'],
                    'image' => $songbook ? ($songbook->image ?: '') : '',
                    'name' => $item->data['psalm']['songbook'] ?? '',
                ];
            } else {
                $data[$key]['songbook'] = [
                    'code' => '',
                    'image' => '',
                    'name' => '',
                ];
            }
        } else {
            return;
        }
        $slide = $this->createEmptySlide($this->config['backgroundColor']);
        $color = new Color($this->config['textColor']);

        if (!isset($data[$key]['songbook']['image'])) {
            $shape2 = $slide->createRichTextShape()
                ->setOffsetX(0)
                ->setOffsetY(PPTUnitsHelper::convert(7.8, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                ->setWidth(PPTUnitsHelper::convert(25.4, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL));
            $paragraph = $shape2->getActiveParagraph();
            $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $paragraph->getFont()
                ->setBold(false)
                ->setSize($this->config['fontSize'])
                ->setColor($color)
                ->setName('Sarabun');
            $paragraph->createTextRun($data[$key]['songbook']['name']);
        } else {
            if (trim($data[$key]['songbook']['image'])) {
                $shape = $slide->createDrawingShape();
                $shape->setName('')
                    ->setPath(storage_path('app/' . $data[$key]['songbook']['image']))
                    ->setResizeProportional(true)
                    ->setWidth(PPTUnitsHelper::convert(4, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                    ->setOffsetX(PPTUnitsHelper::convert(10.7, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                    ->setOffsetY(PPTUnitsHelper::convert(1.7, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL));
            }
        }
        $shape2 = $slide->createRichTextShape()
            ->setOffsetX(0)
            ->setOffsetY(PPTUnitsHelper::convert(9, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
            ->setWidth(PPTUnitsHelper::convert(25.4, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL));
        $paragraph = $shape2->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $paragraph->getFont()
            ->setBold(false)
            ->setSize($this->config['fontSize'] * 2)
            ->setColor($color)
            ->setName('Sarabun');

        if ($key == 'song') {
            $refText = ($data['verses'] ? ', ' . $data['verses'] : '');
        } elseif($key == 'psalm') {
            $refText = ' '.$data['psalm']['title'] ?? '';
        }
        $paragraph->createTextRun($data[$key]['reference'] . $refText);
    }

    protected function creditsSlide($credits)
    {
        $slide = $this->slide(
            '',
            -1,
            $this->counterColor[$this->config['backgroundColorEmpty']],
            false,
            $credits,
            $this->config['backgroundColorEmpty']
        );
    }

    protected function createEmptySlide($color): Slide
    {
        $backgroundColor = new Color($color);
        $backgroundColorBG = new \PhpOffice\PhpPresentation\Slide\Background\Color();
        $backgroundColorBG->setColor($backgroundColor);
        $slide = $this->ppt->createSlide();
        $slide->setBackground($backgroundColorBG);
        return $slide;
    }

    /**
     * Create a rich text shape covering the entire slide
     * @param Slide $slide
     * @return RichText
     */
    protected function createFullScreenRichTextShape(Slide $slide): RichText
    {
        $shape = $slide->createRichTextShape()
            ->setWidth(950)
            ->setHeight(500)
            ->setOffsetX(10)
            ->setOffsetY(0);


        $shape->getFill()->setFillType(Fill::FILL_NONE);
        $shape->getBorder()->setLineStyle(Border::LINE_NONE);
        return $shape;
    }

    /**
     * @param $filename
     * @throws Exception
     */
    protected function sendToBrowser($filename)
    {
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        if ($this->config['outputFormat'] == 'ppt') {
            $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            $this->createPPTFile($tempFile);
        } else {
            $contentType = 'application/vnd.oasis.opendocument.presentation';
            $this->extension = 'odp';
            $this->createODPFile($tempFile);
        }

        return response()->download($tempFile, $filename, ['Content-Type' => $contentType])
            ->deleteFileAfterSend(true);
    }

    /**
     * Output to PPTX/PPTM file
     * @param $tempFile
     * @return void
     * @throws \DOMException
     */
    protected function createPPTFile($tempFile)
    {
        $objWriter = IOFactory::createWriter($this->ppt, 'PowerPoint2007');
        $objWriter->save($tempFile);

        $patchFile = PowerPoint::fromFile($tempFile);
        $patchFile->applySVGFix();
        $patchFile->applySlideNameFix($this->slideNames);
        $patchFile->applyTextEntityDecodingFix();
        if ($this->extension == 'pptm') {
            // needs patch
            $patchFile->patchPPTM(base_path('assets/ppt/vbaProjectForAutoLoops.bin'));
            $contentType = 'application/vnd.ms-powerpoint.presentation.macroEnabled.12';
        }

    }

    /**
     * Output to ODP file
     * @param $tempFile
     * @return void
     * @throws \DOMException
     */
    protected function createODPFile($tempFile)
    {
        $objWriter = IOFactory::createWriter($this->ppt, 'ODPresentation');
        $objWriter->save($tempFile);

        $patchFile = ODP::fromFile($tempFile);
        $patchFile->applyTextEntityDecodingFix();
        $patchFile->applySlideNameFix($this->slideNames);
        $patchFile->injectBasicMacroFromBas(
                         resource_path('macros/LO/ODF/LoopListener.bas')
        );

    }

    protected function setDocumentProperties(Service $service)
    {
        $titleLine = $service->titleText(false) . ' am ' . $service->dateText() . ', ' . $service->timeText(
            ) . ', ' . $service->locationText();
        $this->ppt->getDocumentProperties()
            ->setCreator(Auth::user()->fullName())
            ->setLastModifiedBy(Auth::user()->fullName())
            ->setTitle('Lieder und Texte')
            ->setSubject($titleLine)
            ->setDescription($titleLine)
            ->setCategory('Gottesdienst')
            ->setKeywords('Gottesdienst, Lieder, Psalm, Mitwirkende')
            ->setCompany('Evangelische Kirchengemeinde ' . $service->city->name);
    }

    /**
     * Render a complete loop of ad slides
     * @param Service $service
     * @param bool $isFinalLoop True if this loop is the very end of the presentation
     * @return void
     */
    protected function renderAdLoop(Service $service, bool $isFinalLoop = false)
    {
        if (!count($this->config['showAdsFromCities'] ?? [])) return;

        $start = $service->date->copy()->addHour(1);
        $end = $service->date->copy()->addWeek(1)->startOfWeek();
        if ($service->date->diffInDays($end) < 6) $end->addWeek(1);


        $announcements = new Announcements($service, $this->config['showAdsFromCities'], false, 'YYYY-MM-DD');

        // get the events to be listed (if not already cached)
        if (!count($this->adEventsToBeListed)) {
            $this->adEventsToBeListed = $announcements->getEvents();
        }

        // get the events to be highlighted (if not already cached)
        if (!count($this->adEventsToBeHighlighted)) {
            $this->adEventsToBeHighlighted = Occurence::adRunningAt('ppt', $service->date)
                ->whereHas('service', function ($query) use ($service) {
                    $query->inCities($this->config['showAdsFromCities'])
                        ->notHidden()
                        ->displayable($service->start);
                })->orderBy('start')
                ->get()
                ->groupBy(function ($occurence) {
                    return $occurence->start->setTimeZone('Europe/Berlin')->format('Y-m-d');
                });
        }

        $renderableHighlightedEvents = collect($this->adEventsToBeHighlighted)
            ->map(function ($events) {
                return collect($events)
                    ->filter(function (Occurence $event) {
                        return !empty($event->service->getImageCutPath('bildschirm-16x9'));
                    })
                    ->values();
            })
            ->filter(function (Collection $events) {
                return $events->isNotEmpty();
            });

        $eventListSlideChunks = $this->suppressHighlightedOnlyEventListSlides(
            $this->paginateEventListSlidesByDate($this->adEventsToBeListed),
            $renderableHighlightedEvents
        );
        $listedSlidesCount = collect($eventListSlideChunks)->sum(function (array $pages) {
            return count($pages);
        });
        $highlightedSlidesCount = $renderableHighlightedEvents->sum(function ($events) {
            return count($events);
        });

        $currentSlideNumber = $this->ppt->getSlideCount();
        $isFirstLoop = $currentSlideNumber == 0;
        $adSlidesCount = $listedSlidesCount + $highlightedSlidesCount;
        $finalAdSlideNumber = $currentSlideNumber + $adSlidesCount;

        if ($isFirstLoop) {
            $adSlidesCount += (int)$this->config['includeVirtualSongsheetQR'] + (int)$this->config['includeSongList'];
            $finalAdSlideNumber += (int)$this->config['includeVirtualSongsheetQR'] + (int)$this->config['includeSongList'];
            if ($this->config['includeSongList']) {
                $slide = $this->createEmptyAdSlide(1, $finalAdSlideNumber, false);
                $this->renderSongListSlide($slide, $service);
            }
            if ($this->config['includeVirtualSongsheetQR']) {
                $slide = $this->createEmptyAdSlide(1, $finalAdSlideNumber, false);
                $this->renderVirtualSongsheetQR($slide, $service);
            }
        }

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            foreach (($eventListSlideChunks[$cursor->format('Y-m-d')] ?? []) as $eventListChunk) {
                $this->renderEventsListSlide($cursor,
                                             $eventListChunk,
                                             $currentSlideNumber+1,
                                             $finalAdSlideNumber,
                                             $isFinalLoop);
            }
            if (count($renderableHighlightedEvents[$cursor->format('Y-m-d')] ?? [])) {
                foreach ($renderableHighlightedEvents[$cursor->format('Y-m-d')] as $event) {
                    $this->renderEventHighlightSlide($cursor,
                                                     $event,
                                                     $currentSlideNumber+1,
                                                     $finalAdSlideNumber,
                                                     $isFinalLoop);
                }
            }
            $cursor->addDay(1);
        }

        // add future highlights
        foreach ($renderableHighlightedEvents as $date => $events) {
            $cursor = Carbon::parse($date);
            if ($cursor->gt($end)) {
                foreach ($events as $event) {
                    $this->renderEventHighlightSlide($cursor,
                                                     $event,
                                                     $currentSlideNumber+1,
                                                     $finalAdSlideNumber,
                                                     $isFinalLoop);
                }

            }
        }
    }

    /**
     * Split event lists into slide-sized chunks per day.
     *
     * @param iterable $eventsByDate
     * @return array<string, array<int, Collection>>
     */
    protected function paginateEventListSlidesByDate(iterable $eventsByDate): array
    {
        $pagesByDate = [];

        foreach ($eventsByDate as $date => $events) {
            $pagesByDate[$date] = $this->paginateEventListSlides(collect($events));
        }

        return $pagesByDate;
    }

    /**
     * Remove event list slides whose entries are all rendered as dedicated highlight slides.
     *
     * @param array<string, array<int, Collection>> $pagesByDate
     * @param iterable $highlightedEventsByDate
     * @return array<string, array<int, Collection>>
     */
    protected function suppressHighlightedOnlyEventListSlides(array $pagesByDate, iterable $highlightedEventsByDate): array
    {
        $highlightKeysByDate = [];

        foreach ($highlightedEventsByDate as $date => $events) {
            $highlightKeysByDate[$date] = collect($events)
                ->map(function ($event) {
                    return $this->getEventListComparisonKey($event);
                })
                ->filter()
                ->values()
                ->all();
        }

        foreach ($pagesByDate as $date => $pages) {
            $highlightKeys = $highlightKeysByDate[$date] ?? [];

            if (!count($highlightKeys)) {
                continue;
            }

            $pagesByDate[$date] = array_values(array_filter($pages, function (Collection $page) use ($highlightKeys) {
                return $page->contains(function ($event) use ($highlightKeys) {
                    $comparisonKey = $this->getEventListComparisonKey($event);

                    return empty($comparisonKey) || !in_array($comparisonKey, $highlightKeys, true);
                });
            }));
        }

        return $pagesByDate;
    }

    /**
     * Split a single day's event list into multiple slide-sized chunks.
     *
     * @param Collection $events
     * @return array<int, Collection>
     */
    protected function paginateEventListSlides(Collection $events): array
    {
        if ($events->isEmpty()) {
            return [];
        }

        $layout = $this->getEventsListLayout();
        $titleLineHeight = PPTUnitsHelper::estimateLineHeightPx($layout['titleFontTtfPath'], $layout['listFontSize']);
        $locationLineHeight = PPTUnitsHelper::estimateLineHeightPx($layout['locationFontTtfPath'], $layout['locationFontSize']);

        $pages = [];
        $currentPageEvents = collect();
        $currentHeight = 0;

        foreach ($events as $event) {
            $eventHeight = $this->estimateEventListEntryHeight($event, $layout, $titleLineHeight, $locationLineHeight);

            if ($currentPageEvents->isNotEmpty() && (($currentHeight + $eventHeight) > $layout['listHeight'])) {
                $pages[] = $currentPageEvents;
                $currentPageEvents = collect();
                $currentHeight = 0;
            }

            $currentPageEvents->push($event);
            $currentHeight += $eventHeight;
        }

        if ($currentPageEvents->isNotEmpty()) {
            $pages[] = $currentPageEvents;
        }

        return $pages;
    }

    /**
     * Build a stable comparison key for matching list entries to highlight slides.
     *
     * @param mixed $event
     * @return string|null
     */
    protected function getEventListComparisonKey($event): ?string
    {
        if (isset($event->id) && $event->id) {
            return 'id:' . $event->id;
        }

        if (method_exists($event, 'getKey') && $event->getKey()) {
            return 'key:' . $event->getKey();
        }

        if (isset($event->service->id) && isset($event->start)) {
            return 'service:' . $event->service->id . '|start:' . $event->start;
        }

        return null;
    }

    /**
     * Get the layout configuration for event list slides.
     *
     * @return array<string, int|string>
     */
    protected function getEventsListLayout(): array
    {
        $listFontSize     = (int)($this->config['fontSize'] * 0.8);
        $locationFontSize = (int)($listFontSize * 0.6);
        $listWidth        = 950;
        $listHeight       = 500;
        $leftColumnWidthPixels = (int)PPTUnitsHelper::convert(5.75, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL);
        $rightColumnWidth = $listWidth - $leftColumnWidthPixels;
        $detailsInnerPaddingPx = 10;

        return [
            'listFontSize' => $listFontSize,
            'locationFontSize' => $locationFontSize,
            'listWidth' => $listWidth,
            'listHeight' => $listHeight,
            'listOffsetX' => 10,
            'listOffsetY' => 80,
            'leftColumnWidthPixels' => $leftColumnWidthPixels,
            'rightColumnOffsetX' => 10 + $leftColumnWidthPixels,
            'rightColumnWidth' => $rightColumnWidth,
            'detailsInnerPaddingPx' => $detailsInnerPaddingPx,
            'maxTitleWidthPx' => max(50, $rightColumnWidth - $detailsInnerPaddingPx),
            'titleFontTtfPath' => resource_path('fonts/Sarabun-SemiBold.ttf'),
            'locationFontTtfPath' => resource_path('fonts/Sarabun-Light.ttf'),
            'titleMarginBottom' => 4,
            'locationMarginBottom' => 14,
        ];
    }

    /**
     * Estimate the vertical space needed for one event in the list slide layout.
     *
     * @param mixed $event
     * @param array<string, int|string> $layout
     * @param int $titleLineHeight
     * @param int $locationLineHeight
     * @return int
     */
    protected function estimateEventListEntryHeight($event, array $layout, int $titleLineHeight, int $locationLineHeight): int
    {
        $titleText = $event->getAdText('ppt', $event->service->titleText(false));
        $titleLines = PPTUnitsHelper::wrapTextByPixelWidth(
            $titleText,
            $layout['titleFontTtfPath'],
            $layout['listFontSize'],
            $layout['maxTitleWidthPx']
        );

        return (count($titleLines) * ($titleLineHeight + $layout['titleMarginBottom']))
            + $locationLineHeight
            + $layout['locationMarginBottom'];
    }

    /**
     * Create an empty ad slide
     * @param int $firstAdSlideNumber Number of the first slide in the loop
     * @param int $finalAdSlideNumber Number of the last slide in the loop
     * @param bool $isFinalLoop True, if this loop is at the very end of the presentation
     * @return Slide
     */
    protected function createEmptyAdSlide(int $firstAdSlideNumber, int $finalAdSlideNumber, bool $isFinalLoop): Slide {
        $slide = $this->createEmptySlide($this->config['backgroundColor']);
        if ($this->config['adLoopDelay']) {
            $transition = new Slide\Transition();
            $transition->setTimeTrigger(true, $this->config['adLoopDelay']*1000);
            $slide->setTransition($transition);
            if ($this->ppt->getSlideCount() == $firstAdSlideNumber) {
                $slideName = 'LOOP_START_'.uniqid();
                $slide->setName($slideName);
                $this->slideNames[$this->ppt->getSlideCount()] = $slideName;
            } elseif($this->ppt->getSlideCount() == $finalAdSlideNumber) {
                $slideName = 'LOOP_END_'.uniqid();
                $slide->setName($slideName);
                $this->slideNames[$this->ppt->getSlideCount()] = $slideName;
            }
        }
        return $slide;
    }

    /**
     * Create a slide for a highlighted event
     * @param Carbon $date
     * @param Occurence $event
     * @param int $firstAdSlideNumber Number of the first slide in the loop
     * @param int $finalAdSlideNumber Number of the last slide in the loop
     * @param bool $isFinalLoop True, if this loop is at the very end of the presentation
     * @return void
     * @throws \PhpOffice\PhpPresentation\Exception\FileNotFoundException
     */
    protected function renderEventHighlightSlide(Carbon $date, Occurence $event, int $firstAdSlideNumber, int $finalAdSlideNumber, bool $isFinalLoop) {
        $imageCutPath = $event->service->getImageCutPath('bildschirm-16x9');
        if (empty($imageCutPath)) return;

        $textColor = new Color($this->config['textColor']);
        $gray = new Color('777777');
        $inverseTextColor = new Color($this->config['backgroundColor']);
        $shapeBackground = new Color('CCFFFFFF');

        $averageImageColor = ImageService::sampleAverageRgb($imageCutPath, 20, 470, 150, 150);
        $overlayARGB = ImageService::tintedOverlayArgb($averageImageColor,0.95);
        $overlayColor = new Color($overlayARGB);
        $overlayTextColor = new Color(ImageService::blackOrWhiteForArgb($overlayARGB));

        $slideWidth = Drawing::emuToPixels($this->ppt->getLayout()->getCX());
        $slideHeight = Drawing::emuToPixels($this->ppt->getLayout()->getCY());

        $slide = $this->createEmptyAdSlide($firstAdSlideNumber, $finalAdSlideNumber, $isFinalLoop);
        $shape = $slide->createDrawingShape()
            ->setPath($imageCutPath)
            ->setOffsetX(0)
            ->setOffsetY(0)
            ->setWidth($slideWidth)
            ->setHeight($slideHeight);


        $shape = $slide->createRichTextShape()
            ->setWidth($slideWidth)
            ->setHeight(130)
            ->setOffsetX(0)
            ->setOffsetY($slideHeight - 130);
        $shape->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor($shapeBackground);

        $paragraph = $shape->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setMarginLeft(150);
        if (!$event->event->is_allday) {
            $run = $paragraph->createTextRun($event->start->copy()->setTimezone('Europe/Berlin')->format('H:i').' Uhr')->getFont()
                ->setBold(false)
                ->setSize((int)($this->config['fontSize']*0.5))
                ->setColor($inverseTextColor)
                ->setName('Sarabun Light');
            $run = $paragraph->createTextRun(' | ')->getFont()
                ->setBold(false)
                ->setColor($gray)
                ->setSize((int)($this->config['fontSize']*0.5))
                ->setName('Sarabun Light');
        }
        $run = $paragraph->createTextRun($event->service->locationTextWithCity)
            ->getFont()
            ->setBold(false)
            ->setSize((int)($this->config['fontSize']*0.5))
            ->setColor($inverseTextColor)
            ->setName('Sarabun Light');

        $paragraph = $shape->createParagraph();
        $run = $paragraph->createTextRun($event->getAdText('ppt', $event->service->titleText(false)))
            ->getFont()
            ->setName('Sarabun SemiBold')
            ->setSize($this->config['fontSize'])
            ->setColor($inverseTextColor);

        $multiDay = $event->event->is_allday && ($event->start->copy()->setTimeZone('Europe/Berlin')->format('Ymd') != $event->end->copy()->setTimeZone('Europe/Berlin')->format('Ymd'));

        $shape = $slide->createAutoShape()
            ->setType(AutoShape::TYPE_OVAL)
            ->setWidth(150)
            ->setHeight(150)
            ->setOffsetX(20)
            ->setOffsetY($slideHeight - 250)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor($overlayColor);

        $shape = $slide->createRichTextShape()
            ->setWidth(150)
            ->setHeight(150)
            ->setOffsetX(20)
            ->setOffsetY($slideHeight - 240);
        $paragraph = $shape->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $paragraph->createTextRun(($multiDay ? 'Ab ' : '').$event->start->copy()->setTimeZone('Europe/Berlin')->isoFormat('dddd'))->getFont()
            ->setBold(false)
            ->setSize(12)
            ->setColor($overlayTextColor)
            ->setName('Sarabun Light');
        $paragraph = $shape->createParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $paragraph->createTextRun($event->start->copy()->setTimeZone('Europe/Berlin')->isoFormat('D'))->getFont()
            ->setSize(50)
            ->setColor($overlayTextColor)
            ->setName('Sarabun ExtraBold');
        $paragraph = $shape->createParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $paragraph->createTextRun($event->start->copy()->setTimeZone('Europe/Berlin')->isoFormat('MMMM'))->getFont()
            ->setBold(false)
            ->setSize(12)
            ->setColor($overlayTextColor)
            ->setName('Sarabun Light');
    }

    /**
     * Create a slide with an events list for a single day
     * @param Carbon $date
     * @param Collection<Occurence> $events
     * @param int $firstAdSlideNumber Number of the first slide in the loop
     * @param int $finalAdSlideNumber Number of the last slide in the loop
     * @param bool $isFinalLoop True, if this loop is at the very end of the presentation
     * @return void
     * @throws \PhpOffice\PhpPresentation\Exception\OutOfBoundsException
     */
    protected function renderEventsListSlide(
        Carbon $date,
        Collection $events,
        int $firstAdSlideNumber,
        int $finalAdSlideNumber,
        bool $isFinalLoop
    ) {
        $textColor = new Color($this->config['textColor']);
        $gray      = new Color('777777');

        $slide = $this->createEmptyAdSlide($firstAdSlideNumber, $finalAdSlideNumber, $isFinalLoop);

        $this->renderEventsListSlideHeader($slide, $date, $textColor, $gray);
        $layout = $this->getEventsListLayout();

        // Shapes
        $timeColumnShape = $slide->createRichTextShape()
            ->setOffsetX($layout['listOffsetX'])
            ->setOffsetY($layout['listOffsetY'])
            ->setWidth($layout['leftColumnWidthPixels'])
            ->setHeight($layout['listHeight']);

        $timeColumnShape->getFill()->setFillType(Fill::FILL_NONE);
        $timeColumnShape->getBorder()->setLineStyle(Border::LINE_NONE);

        $detailsColumnShape = $slide->createRichTextShape()
            ->setOffsetX($layout['rightColumnOffsetX'])
            ->setOffsetY($layout['listOffsetY'])
            ->setWidth($layout['rightColumnWidth'])
            ->setHeight($layout['listHeight']);

        $detailsColumnShape->getFill()->setFillType(Fill::FILL_NONE);
        $detailsColumnShape->getBorder()->setLineStyle(Border::LINE_NONE);

        $isFirstTimeParagraph    = true;
        $isFirstDetailsParagraph = true;

        foreach ($events as $event) {
            $timeText = (!$event->event->is_allday) ? $event->service->timeText() : '';

            $titleText = $event->getAdText('ppt', $event->service->titleText(false));
            $titleLines = PPTUnitsHelper::wrapTextByPixelWidth($titleText, $layout['titleFontTtfPath'], $layout['listFontSize'], $layout['maxTitleWidthPx']);

            // --- LEFT COLUMN: time on first line, then spacer lines to match wrapped title, then spacer for location
            $timeParagraph = $isFirstTimeParagraph
                ? $timeColumnShape->getActiveParagraph()
                : $timeColumnShape->createParagraph();
            $isFirstTimeParagraph = false;

            $timeParagraph->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                ->setMarginRight(10)
                ->setMarginBottom(4);

            $timeParagraph->createTextRun($timeText)
                ->getFont()
                ->setBold(false)
                ->setSize($layout['listFontSize'])
                ->setColor($textColor)
                ->setName('Sarabun Light');

            // If title wraps into N lines, add N-1 blank lines in the time column
            for ($i = 1; $i < count($titleLines); $i++) {
                $timeWrappedSpacerParagraph = $timeColumnShape->createParagraph();
                $timeWrappedSpacerParagraph->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setMarginRight(10)
                    ->setMarginBottom(4);

                $timeWrappedSpacerParagraph->createTextRun("\u{00A0}")
                    ->getFont()
                    ->setBold(false)
                    ->setSize($layout['listFontSize'])
                    ->setColor($textColor)
                    ->setName('Sarabun Light');
            }

            // Spacer line for the location line (keeps left/right aligned)
            $timeLocationSpacerParagraph = $timeColumnShape->createParagraph();
            $timeLocationSpacerParagraph->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                ->setMarginRight(10)
                ->setMarginBottom(14);

            $timeLocationSpacerParagraph->createTextRun("\u{00A0}")
                ->getFont()
                ->setBold(false)
                ->setSize($layout['locationFontSize'])
                ->setColor($textColor)
                ->setName('Sarabun Light');

            // --- RIGHT COLUMN: title as multiple paragraphs (so wrapping is deterministic), then location paragraph
            foreach ($titleLines as $index => $titleLine) {
                $titleParagraph = $isFirstDetailsParagraph
                    ? $detailsColumnShape->getActiveParagraph()
                    : $detailsColumnShape->createParagraph();
                $isFirstDetailsParagraph = false;

                $titleParagraph->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setMarginBottom(4);

                $titleParagraph->createTextRun($titleLine)
                    ->getFont()
                    ->setSize($layout['listFontSize'])
                    ->setColor($textColor)
                    ->setName('Sarabun SemiBold');
            }

            $locationParagraph = $detailsColumnShape->createParagraph();
            $locationParagraph->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setMarginBottom(14);

            $locationParagraph->createTextRun($event->service->locationTextWithCity)
                ->getFont()
                ->setBold(false)
                ->setSize($layout['locationFontSize'])
                ->setColor($textColor)
                ->setName('Sarabun Light');
        }
    }

    /**
     * Render the repeated day header for event list slides.
     *
     * @param Slide $slide
     * @param Carbon $date
     * @param Color $textColor
     * @param Color $gray
     * @return void
     */
    protected function renderEventsListSlideHeader(Slide $slide, Carbon $date, Color $textColor, Color $gray): void
    {
        $headerShape = $slide->createRichTextShape()
            ->setWidth(950)
            ->setHeight(50)
            ->setOffsetX(10)
            ->setOffsetY(0);

        $headerShape->getFill()->setFillType(Fill::FILL_NONE);
        $headerShape->getBorder()->setLineStyle(Border::LINE_NONE);

        $headerParagraph = $headerShape->getActiveParagraph();
        $headerParagraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $headerParagraph->createTextRun($date->copy()->setTimezone('Europe/Berlin')->isoFormat('dddd'))
            ->getFont()->setSize($this->config['fontSize'])->setColor($textColor)->setName('Sarabun SemiBold');

        $headerParagraph->createTextRun(' | ')
            ->getFont()->setBold(false)->setSize($this->config['fontSize'])->setColor($gray)->setName('Sarabun Light');

        $headerParagraph->createTextRun($date->copy()->setTimezone('Europe/Berlin')->isoFormat('D. MMMM'))
            ->getFont()->setBold(false)->setSize($this->config['fontSize'])->setColor($textColor)->setName('Sarabun SemiBold');
    }

    public function renderVirtualSongsheetQR(Slide $slide, Service $service)
    {
        $shape = $slide->createRichTextShape()
            ->setWidth(950)
            ->setHeight(50)
            ->setOffsetX(10)
            ->setOffsetY(0);

        $paragraph = $shape->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);;
        $run = $paragraph->createTextRun('Digitales Liedblatt zum Gottesdienst:')->getFont()
            ->setSize((int)($this->config['fontSize']*0.8))
            ->setColor(new Color($this->config['textColor']))
            ->setName('Sarabun SemiBold');


        $shape = $slide->createDrawingShape();
        $shape->setName('')
            ->setPath(QRService::generate(route('service.publicLiturgy', $service->slug)))
            ->setResizeProportional(true)
            ->setHeight(350)
            ->setOffsetX(280)
            ->setOffsetY(100)
            ->getBorder()
            ->setLineStyle(Border::LINE_SINGLE)
            ->setLineWidth(20)
            ->setColor(new Color('FFFFFF'));

    }

}
