<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
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
use App\Helpers\StoragePath;
use App\Liturgy\ItemHelpers\FreetextItemHelper;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Liturgy\Music\ABCMusic;
use App\Models\Announcements;
use App\Models\Attachment;
use App\Models\Calendar\Occurence;
use App\Models\Liturgy\Item;
use App\Models\Liturgy\Song;
use App\Models\Liturgy\Songbook;
use App\Models\Liturgy\SongReference;
use App\Models\People\User;
use App\Models\Sermon;
use App\Models\Service;
use App\Services\ImageService;
use App\Services\QRService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use PhpOffice\Common\Drawing;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\Exception\FileNotFoundException;
use PhpOffice\PhpPresentation\Exception\OutOfBoundsException;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Render songs, liturgy texts and configurable separator slides.
 *
 * @phpstan-type EventListLayout array{
 *     listFontSize: int,
 *     descriptionFontSize: int,
 *     locationFontSize: int,
 *     listWidth: int,
 *     listHeight: int,
 *     listOffsetX: int,
 *     listOffsetY: int,
 *     leftColumnWidthPixels: int,
 *     rightColumnOffsetX: int,
 *     rightColumnWidth: int,
 *     detailsInnerPaddingPx: int,
 *     maxTitleWidthPx: int,
 *     titleFontTtfPath: string,
 *     descriptionFontTtfPath: string,
 *     locationFontTtfPath: string,
 *     titleMarginBottom: int,
 *     descriptionMarginBottom: int,
 *     locationMarginBottom: int
 * }
 */
class SongPPTLiturgySheet extends AbstractLiturgySheet
{
    /**
     * Configuration of this sheet with the concrete value types of its defaults.
     *
     * @var array{
     *     textColor: string,
     *     backgroundColor: string,
     *     backgroundColorEmpty: string,
     *     slideBetweenElements: string,
     *     titleSlideTitle: string,
     *     titleSlideSubtitle: string,
     *     titleSlideAttachmentId: int|string|null,
     *     includeJingleAndIntro: bool|int,
     *     includeCredits: bool|int,
     *     includeSongList: bool|int,
     *     includeSongbookReference: bool|int,
     *     verticalAlignment: string,
     *     fontSize: int,
     *     renderMusic: bool,
     *     includeAdLoopStart: bool|int,
     *     includeAdLoopEnd: bool|int,
     *     includeAdLoopElements: array<int, mixed>,
     *     showAdsFromCities: array<int, int>,
     *     adLoopDelay: int,
     *     includeVirtualSongsheetQR: bool|int,
     *     outputFormat: string
     * }
     */
    protected $config;

    protected $title = 'Powerpoint mit Liedern';

    protected $icon = 'fa fa-file-powerpoint';

    protected $extension = 'pptx';

    protected $configurationPage = 'Liturgy/LiturgySheets/SongPPTSongSheetConfiguration';

    protected $configurationComponent = 'SongPPTLiturgySheetConfiguration';

    protected $defaultConfig = [
        'textColor' => 'FFFFFFFF',
        'backgroundColor' => 'FF043b04',
        'backgroundColorEmpty' => 'FF043b04',
        'slideBetweenElements' => 'empty',
        'titleSlideTitle' => '',
        'titleSlideSubtitle' => '',
        'titleSlideAttachmentId' => null,
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

    /** @var array<string, string> */
    protected $counterColor = [
        'FFFFFFFF' => 'FF000000',
        'FF000000' => 'FFFFFFFF',
        'FF043b04' => 'FFFFFFFF',
    ];

    /** @var array<string, int> */
    protected $musicColorSet = [
        'FFFFFFFF' => ABCMusic::COLORS_NORMAL,
        'FF000000' => ABCMusic::COLORS_INVERTED,
        'FF043b04' => ABCMusic::COLORS_GREENSCREEN,
    ];

    /**
     * Cache ad events to be listed to prevent multiple queries
     *
     * @var array<int|string, Occurence>|Collection<int|string, Occurence>
     */
    protected $adEventsToBeListed = [];

    /**
     * Cache ad events to be highlighted to prevent multiple queries
     *
     * @var array<string, mixed>|Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Occurence>>
     */
    protected $adEventsToBeHighlighted = [];

    /**
     * Slide names (for slide name fix), keyed by slide index.
     *
     * @var array<int, string>
     */
    protected $slideNames = [];

    /** @var PhpPresentation */
    protected $ppt;

    /** @var Service Service currently rendered for the export. */
    protected $service;

    /**
     * Render the liturgy as a PowerPoint or OpenDocument presentation.
     *
     * @param  Service  $service  Service whose liturgy is exported.
     * @return mixed Download response for the generated presentation.
     */
    public function render(Service $service)
    {
        $this->service = $service;
        $this->ppt = new PhpPresentation;
        $this->setDocumentProperties($service);
        $this->ppt->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        $textColor = new Color($this->config['textColor']);
        $highlightedTextColor = new Color('FFF79646');
        $this->ppt->removeSlideByIndex(0);

        if ($this->config['includeAdLoopStart']) {
            $this->renderAdLoop($service);
        }

        if ($this->config['includeSongList']) {
            if (! $this->config['includeAdLoopStart']) {
                $slide = $this->createEmptySlide($this->config['backgroundColor']);
                $this->renderSongListSlide($slide, $service);
            }
        } elseif ($this->getInterElementSlideMode() !== 'none') {
            $this->renderInterElementSlide($service);
        }

        if ($this->config['includeVirtualSongsheetQR'] && (! $this->config['includeAdLoopStart'])) {
            $slide = $this->createEmptySlide($this->config['backgroundColor']);
            $this->renderVirtualSongsheetQR($slide, $service);
        }

        if ($this->config['includeJingleAndIntro']) {
            $this->slide('Hier Jingle einfügen');
            $this->slide('Hier Intro einfügen');
        }
        if ($this->getInterElementSlideMode() !== 'none') {
            $this->renderInterElementSlide($service);
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
                } elseif ($item->data_type == 'freetext') {
                    if ($lastItem && ($lastItem->data_type == 'psalm')) {
                        if (($item->title == 'Ehr sei dem Vater') && (isset($item->data['description']))) {
                            /** @var FreetextItemHelper $ftHelper */
                            $ftHelper = $item->getHelper();
                            $this->renderGloriaPatriSlide($ftHelper->getText());
                        }
                        if ($this->getInterElementSlideMode() !== 'none') {
                            $this->renderInterElementSlide($service);
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
        if ($this->config['outputFormat'] == 'odp') {
            $this->slide();
        }

        // we need to patch the PPTX to become a PPTM right here:
        if (($this->config['includeAdLoopStart'])
            || $this->config['includeAdLoopEnd']
            || count($this->config['includeAdLoopElements']) && $this->config['adLoopDelay']) {
            if ($this->config['outputFormat'] == 'ppt') {
                $this->setExtension('pptm');
            }
        }

        return $this->sendToBrowser($this->getFileName($service, 'Texte und Lieder'));
    }

    /**
     * Return the configured kind of separator slide between liturgy elements.
     */
    protected function getInterElementSlideMode(): string
    {
        return $this->config['slideBetweenElements'] ?? 'empty';
    }

    /**
     * Render the configured separator slide for the current service.
     *
     * @param  Service  $service  Service whose title slide is rendered.
     */
    protected function renderInterElementSlide(Service $service): void
    {
        if ($this->getInterElementSlideMode() === 'title') {
            $this->renderTitleSlide($service);

            return;
        }

        if ($this->getInterElementSlideMode() === 'empty') {
            $this->slide();
        }
    }

    /**
     * Get the configured title slide content and its service attachment.
     *
     * @param  Service  $service  Service owning the configured attachment.
     * @return array{title: string, subtitle: string, image: ?string}
     */
    protected function getTitleSlideData(Service $service): array
    {
        $attachmentId = $this->config['titleSlideAttachmentId'] ?? null;
        /** @var Collection<int, Attachment> $attachments */
        $attachments = $service->relationLoaded('attachments')
            ? $service->getRelation('attachments')
            : $service->attachments()->get();
        $attachment = $attachmentId ? $attachments->firstWhere('id', (int) $attachmentId) : null;

        $title = (string) ($this->config['titleSlideTitle'] ?? '');
        /** @var Sermon|null $sermon */
        $sermon = $service->relationLoaded('sermon')
            ? $service->getRelation('sermon')
            : $service->sermon()->first();
        $sermonTitle = trim((string) ($sermon ? $sermon->title : ''));
        $pastorNames = $this->getTitleSlidePastorNames($service);

        if ($title === '') {
            $title = $sermonTitle ?: 'Gottesdienst am '.$service->date?->isoFormat('dddd, DD.MM.YYYY');
        }

        $subtitle = (string) ($this->config['titleSlideSubtitle'] ?? '');
        if ($subtitle === '') {
            $subtitle = 'mit '.$pastorNames;
            $sermonReference = trim((string) ($sermon ? $sermon->reference : ''));
            if ($sermonTitle && $sermonReference !== '') {
                $subtitle .= ' zu '.$sermonReference;
            }
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'image' => $attachment instanceof Attachment && $attachment->file
                ? StoragePath::for($attachment->file)
                : null,
        ];
    }

    /**
     * Format all pastors assigned to a service for a title slide subtitle.
     *
     * @param  Service  $service  Service whose pastors are listed.
     * @return string Comma-separated names with "und" before the last name.
     */
    protected function getTitleSlidePastorNames(Service $service): string
    {
        /** @var Collection<int, User> $pastors */
        $pastors = $service->relationLoaded('pastors')
            ? $service->getRelation('pastors')
            : $service->pastors;
        $names = $pastors
            ->map(fn ($pastor) => $pastor->fullName(true))
            ->filter()
            ->values()
            ->all();

        if (count($names) < 2) {
            return $names[0] ?? '';
        }

        $lastName = array_pop($names);

        return implode(', ', $names).' und '.$lastName;
    }

    /**
     * Render a title slide following the supplied presentation template.
     *
     * @param  Service  $service  Service whose title slide is rendered.
     */
    protected function renderTitleSlide(Service $service): void
    {
        $data = $this->getTitleSlideData($service);
        $slide = $this->createEmptySlide($this->config['backgroundColor']);

        if ($data['image']) {
            $slide->createDrawingShape()
                ->setPath($data['image'])
                ->setOffsetX(0)
                ->setOffsetY(0)
                ->setWidth(960)
                ->setHeight(540);
        }

        $overlay = $slide->createRichTextShape()
            ->setOffsetX(0)
            ->setOffsetY($this->centimetersToPixels(10.6))
            ->setWidth(960)
            ->setHeight($this->centimetersToPixels(2.6));
        $overlay->getFill()?->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('D9FFFFFF'))
            ->setEndColor(new Color('D9FFFFFF'));
        $overlay->getBorder()->setLineStyle(Border::LINE_NONE);

        $textColor = new Color('FF000000');
        $titleParagraph = $overlay->getActiveParagraph();
        $titleParagraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $titleParagraph->createTextRun($data['title'])->getFont()
            ->setBold(true)
            ->setSize(28)
            ->setColor($textColor)
            ->setName('Calibri');

        if ($data['subtitle'] !== '') {
            $subtitleParagraph = $overlay->createParagraph();
            $subtitleParagraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $subtitleParagraph->createTextRun($data['subtitle'])->getFont()
                ->setBold(false)
                ->setSize(26)
                ->setColor($textColor)
                ->setName('Calibri');
        }
    }

    /**
     * Add a formatted text box to a presentation slide.
     *
     * @param  Slide  $slide  Slide receiving the text box.
     * @param  float  $yOffset  Vertical position in centimetres.
     * @param  string  $text  Text to render.
     * @param  Color|null  $color  Optional text color.
     * @param  float  $xOffset  Horizontal position in centimetres.
     * @param  int|null  $fontSize  Optional font size.
     * @param  bool  $bold  Whether the text is bold.
     * @param  string  $alignment  Horizontal paragraph alignment.
     * @return void
     */
    protected function createTextBox($slide, $yOffset, $text, $color = null, $xOffset = 0, $fontSize = null, $bold = false, $alignment = Alignment::HORIZONTAL_LEFT)
    {
        $color ??= new Color($this->config['textColor']);
        $fontSize ??= $this->config['fontSize'];
        $shape2 = $slide->createRichTextShape()
            ->setOffsetX($this->centimetersToPixels($xOffset))
            ->setOffsetY($this->centimetersToPixels($yOffset))
            ->setWidth(
                $this->centimetersToPixels(25.4 - $xOffset)
            );
        $paragraph = $shape2->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal($alignment);
        $paragraph->getFont()?->setBold($bold)
            ->setSize($fontSize)
            ->setColor($color)
            ->setName('Sarabun');
        $paragraph->createTextRun($text);
    }

    /**
     * Render all configured presentation slides for a free-text item.
     *
     * @param  Item  $item  Free-text item to render.
     * @return void
     */
    protected function renderFreeTextSlides(Item $item)
    {
        if (! ($item->data['slideText'] ?? false)) {
            return;
        }
        $helper = $item->getHelper();

        /** @var string $slideText */
        $slideText = $item->data['slideText'];
        $slides = collect(explode("\n---", $slideText))->map(fn ($slideLine) => trim($slideLine));
        foreach ($slides as $slideText) {
            $this->slide(
                $slideText,
                $this->config['fontSize'],
                $this->config['textColor'],
                true,
                ''
            );
        }

        if ($this->getInterElementSlideMode() !== 'none') {
            $this->renderInterElementSlide($this->service);
        }
    }

    /**
     * Render the song and psalm list at the beginning of a presentation.
     *
     * @param  Slide  $slide  Slide receiving the list.
     * @param  Service  $service  Service whose liturgy is listed.
     * @return void
     */
    protected function renderSongListSlide(Slide $slide, Service $service)
    {
        $color = new Color($this->config['textColor']);
        $listItems = [];
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (($item->data_type == 'song') && (isset($item->data['song']))) {
                    /** @var array{id?: int|string, reference?: string, songbook?: array{image?: string, code?: string}} $song */
                    $song = $item->data['song'];
                    $refColor = '';
                    if (isset($song['id'])) {
                        $songRef = SongReference::find($song['id']);
                        $refColor = $songRef->color ?? '';
                    }
                    $listItems[] = [
                        'ref' => $song['reference'] ?? '',
                        'verses' => $item->data['verses'],
                        'image' => isset($song['songbook']) ? ($song['songbook']['image'] ?? '') : '',
                        'code' => isset($song['songbook']) ? ($song['songbook']['code'] ?? '') : '',
                        'color' => $refColor,
                        'psalm' => false,
                    ];
                } elseif (($item->data_type == 'psalm') && isset($item->data['psalm'])) {
                    /** @var array{reference?: string, title?: string, songbook_abbreviation?: string} $psalm */
                    $psalm = $item->data['psalm'];
                    $refColor = '';
                    $songbook = null;
                    if (isset($psalm['songbook_abbreviation'])) {
                        $refColor = ($psalm['songbook_abbreviation'] == 'EG') ? '#c8baf7' : '';
                        $songbook = Songbook::where('code', $psalm['songbook_abbreviation'])->first();
                    }

                    $listItems[] = [
                        'ref' => $psalm['reference'] ?? '',
                        'verses' => $psalm['title'] ?? '',
                        'image' => $songbook ? $songbook->image : '',
                        'code' => $psalm['songbook_abbreviation'] ?? '',
                        'color' => $refColor,
                        'psalm' => true,
                    ];
                }
            }
        }

        /** @var list<array{ref: string, verses: string, image: string, code: string, color: string, psalm: bool}> $listItems */
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
                        ->setOffsetY($this->centimetersToPixels($offset + .4))
                        ->setOffsetX($this->centimetersToPixels(2.4))
                        ->setWidth($this->centimetersToPixels(0.2))
                        ->setHeight($this->centimetersToPixels(1));
                    $shape->getFill()?->setStartColor($shapeColor)->setEndColor($shapeColor)->setFillType(Fill::FILL_SOLID);
                    $shape->getBorder()->setLineStyle(Border::LINE_NONE);
                }
                if ($listItem['image']) {
                    $shape = $slide->createDrawingShape();
                    $shape->setName('')
                        ->setPath(StoragePath::for($listItem['image']))
                        ->setResizeProportional(true)
                        ->setHeight($this->centimetersToPixels(1))
                        ->setOffsetX($this->centimetersToPixels(1.5))
                        ->setOffsetY($this->centimetersToPixels($offset + .4));
                }
                $offset += 1.5;

            }
        }
    }

    /**
     * Render one song item and its active verses.
     *
     * @param  Item  $item  Song item to render.
     * @return void
     */
    protected function renderSongItem(Item $item)
    {
        /** @var array{song?: array{notation?: mixed, copyrights?: string, refrain?: string}, code?: string, reference?: string} $songData */
        $songData = $item->data['song'] ?? [];
        /** @var array{notation?: mixed, copyrights?: string, refrain?: string} $songDetails */
        $songDetails = $songData['song'] ?? [];
        if ($this->config['includeSongbookReference']) {
            $this->songbookReferenceSlide($item);
        }
        if ($this->config['renderMusic'] && (isset($songDetails['notation']))) {
            $this->renderSongItemWithMusic($item);

            return;
        }

        /** @var SongItemHelper $helper */
        $helper = $item->getHelper();
        $copyrights = $songDetails['copyrights'] ?? '';
        if ($copyrights) {
            if ($songData['code'] ?? null) {
                $copyrights = $songData['code'].' '.($songData['reference'] ?? '').'. '.$copyrights;
            }
        }
        $activeVerses = $helper->getActiveVerses();
        foreach ($activeVerses as $verse) {
            $showVerseNumber = ! ((count($activeVerses) == 1) && ($verse['number'] == 1));

            if ($verse['refrain_before']) {
                $this->slide(
                    $songDetails['refrain'] ?? '',
                    $this->config['fontSize'],
                    $this->config['textColor'],
                    true,
                    $copyrights
                );
            }
            /** @var int|string $verseNumber */
            $verseNumber = $verse['number'];
            /** @var string $verseText */
            $verseText = $verse['text'];
            $this->slide(
                ($showVerseNumber ? $verseNumber.'. ' : '').$verseText,
                $this->config['fontSize'],
                $this->config['textColor'],
                true,
                $copyrights
            );
            if ($verse['refrain_after']) {
                $this->slide(
                    $songDetails['refrain'] ?? '',
                    $this->config['fontSize'],
                    $this->config['textColor'],
                    true,
                    $copyrights
                );
            }
        }
        if ($this->getInterElementSlideMode() !== 'none') {
            $this->renderInterElementSlide($this->service);
        }
    }

    /**
     * Render the musical notation images for a song item.
     *
     * @param  Item  $item  Song item to render.
     * @return void
     */
    protected function renderSongItemWithMusic(Item $item)
    {
        /** @var array{song_id?: int|string, title?: string} $songData */
        $songData = $item->data['song'] ?? [];
        /** @var array<int, string>|string $verses */
        $verses = $item->data['verses'] ?? '';
        $song = Song::find($songData['song_id'] ?? null);
        $colorSet = $this->musicColorSet[$this->config['backgroundColor']];
        $images = $song instanceof Song ? ABCMusic::images($song, $verses, $colorSet) : [];

        foreach ($images as $key => $image) {
            $slide = $this->createEmptySlide($this->config['backgroundColor']);
            $shape = $slide->createDrawingShape()
                ->setName(($songData['title'] ?? '').' '.$key)
                ->setPath($image)
                ->setWidth(940)
                ->setOffsetX(10);
            $shape->setOffsetY(520 - $shape->getHeight());
        }

        if ($this->getInterElementSlideMode() !== 'none') {
            $this->renderInterElementSlide($this->service);
        }
    }

    /**
     * Render a special slide with the Gloria Patri ("Ehr sei dem Vater...")
     *
     * @return void
     */
    /**
     * @param  string  $text  Gloria patri text to render.
     * @return void
     */
    protected function renderGloriaPatriSlide($text)
    {
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

    /**
     * Create a text slide, splitting content when it exceeds the layout.
     *
     * @param  string|array<int, string>  $text  Text or paragraphs to render.
     * @param  int  $size  Font size or -1 for the configured size.
     * @param  string|int  $rgb  Text color or -1 for the configured color.
     * @param  bool  $bold  Whether the text is bold.
     * @param  string  $copyrights  Optional footer text.
     * @param  string|null  $backgroundColor  Optional background color.
     * @return Slide|null Last created slide.
     */
    protected function slide($text = '', $size = -1, $rgb = -1, $bold = true, $copyrights = '', $backgroundColor = null)
    {
        if ($size == -1) {
            $size = $this->config['fontSize'];
        }
        if ($rgb == -1) {
            $rgb = $this->config['textColor'];
        }

        /** @var string $rgbColor */
        $rgbColor = $rgb;
        $color = new Color($rgbColor);

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
                $chunk = (string) $chunk;

                // Normalize Windows/Mac newlines
                $chunk = str_replace(["\r\n", "\r"], "\n", $chunk);

                // Split into lines
                foreach (explode("\n", $chunk) as $line) {
                    $lines[] = $line;
                }
            }
        } else {
            $text = (string) $text;
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
                $copyrightParagraph->getFont()?->setBold(false)->setSize(10)->setColor($color)->setName('Sarabun');
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
        if (! file_exists($ttfPath)) {
            $ttfPath = resource_path('fonts/Sarabun-SemiBold.ttf');
        }

        // These must match createFullScreenRichTextShape()
        $textAreaWidthPx = 950;
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
                    (int) $size,
                    (int) $textAreaWidthPx,
                    (int) $maxHeightPx,
                    $ttfPath
                );

                // Create the slide for part1
                $slide = $this->createEmptySlide(
                    $backgroundColor ?: ($part1Lines && trim(implode('', $part1Lines)) !== ''
                        ? $this->config['backgroundColor']
                        : $this->config['backgroundColorEmpty'])
                );

                // Add the main text if any
                if ($part1Lines && ! (count($part1Lines) === 1 && trim($part1Lines[0]) === '')) {
                    $shape = $this->createFullScreenRichTextShape($slide);
                    // If your createFullScreenRichTextShape() does not clear paragraphs,
                    // we handle paragraph creation explicitly below.

                    $paragraphIndex = 0;
                    foreach ($part1Lines as $line) {
                        $paragraph = ($paragraphIndex === 0) ? $shape->getActiveParagraph() : $shape->createParagraph();
                        $paragraphIndex++;

                        $line = (string) $line;

                        if (mb_substr($line, 0, 1) === "\t") {
                            $paragraph->getAlignment()->setMarginLeft(35);
                            $line = mb_substr($line, 1);
                            if ($isOdp) {
                                $line = "\u{00A0}\u{00A0}".$line;
                            }
                        }

                        $paragraph->getAlignment()->setVertical($this->config['verticalAlignment']);
                        $paragraph->getFont()?->setBold($bold)
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
                    $copyrightParagraph->getFont()?->setBold(false)->setSize(10)->setColor($color)->setName('Sarabun');
                    $copyrightParagraph->createTextRun($copyrights);
                }

                $lastCreatedSlide = $slide;

                // If there's no overflow, we're done with this manual chunk
                if (! $part2Lines || trim(implode('', $part2Lines)) === '') {
                    break;
                }

                // Continue with remainder
                $pendingLines = $part2Lines;
            }
        }

        return $lastCreatedSlide;
    }

    /**
     * Render a songbook reference slide for a song or psalm.
     *
     * @param  Item  $item  Item whose reference is rendered.
     * @return void
     */
    protected function songbookReferenceSlide(Item $item)
    {
        /** @var array{songbook?: array{image?: string, code?: string, name?: string}, reference?: string, title?: string, verses?: string, songbook_abbreviation?: string} $referenceData */
        $referenceData = [];
        if ($item->data_type == 'song') {
            if (! isset($item->data['song'])) {
                return;
            }
            /** @var array{songbook?: array{image?: string, code?: string, name?: string}, reference?: string, verses?: string} $song */
            $song = $item->data['song'];
            if (! isset($song['songbook'])) {
                return;
            }
            $referenceData = $song;
        } elseif ($item->data_type == 'psalm') {
            if (! isset($item->data['psalm'])) {
                return;
            }
            /** @var array{reference?: string, title?: string, songbook_abbreviation?: string, songbook?: string} $psalm */
            $psalm = $item->data['psalm'];
            $referenceData = $psalm;
            if (isset($psalm['songbook_abbreviation'])) {
                $songbook = Songbook::where('code', $psalm['songbook_abbreviation'])->first();
                $referenceData['songbook'] = [
                    'code' => $psalm['songbook_abbreviation'],
                    'image' => $songbook ? ($songbook->image ?: '') : '',
                    'name' => $psalm['songbook'] ?? '',
                ];
            } else {
                $referenceData['songbook'] = [
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

        if (! isset($referenceData['songbook']['image'])) {
            $shape2 = $slide->createRichTextShape()
                ->setOffsetX(0)
                ->setOffsetY($this->centimetersToPixels(7.8))
                ->setWidth($this->centimetersToPixels(25.4));
            $paragraph = $shape2->getActiveParagraph();
            $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $paragraph->getFont()?->setBold(false)
                ->setSize($this->config['fontSize'])
                ->setColor($color)
                ->setName('Sarabun');
            $paragraph->createTextRun($referenceData['songbook']['name'] ?? '');
        } else {
            if (trim($referenceData['songbook']['image'])) {
                $shape = $slide->createDrawingShape();
                $shape->setName('')
                    ->setPath(StoragePath::for($referenceData['songbook']['image']))
                    ->setResizeProportional(true)
                    ->setWidth($this->centimetersToPixels(4))
                    ->setOffsetX($this->centimetersToPixels(10.7))
                    ->setOffsetY($this->centimetersToPixels(1.7));
            }
        }
        $shape2 = $slide->createRichTextShape()
            ->setOffsetX(0)
            ->setOffsetY($this->centimetersToPixels(9))
            ->setWidth($this->centimetersToPixels(25.4));
        $paragraph = $shape2->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $paragraph->getFont()?->setBold(false)
            ->setSize($this->config['fontSize'] * 2)
            ->setColor($color)
            ->setName('Sarabun');

        if ($item->data_type == 'song') {
            $verses = $referenceData['verses'] ?? '';
            $refText = ($verses ? ', '.$verses : '');
        } else {
            $refText = ' '.($referenceData['title'] ?? '');
        }
        $paragraph->createTextRun(($referenceData['reference'] ?? '').$refText);
    }

    /**
     * Render the credits slide.
     *
     * @param  string  $credits  Credits text.
     * @return void
     */
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

    /**
     * Create a slide with a solid background color.
     *
     * @param  string  $color  ARGB background color.
     */
    protected function createEmptySlide($color): Slide
    {
        $backgroundColor = new Color($color);
        $backgroundColorBG = new Slide\Background\Color;
        $backgroundColorBG->setColor($backgroundColor);
        $slide = $this->ppt->createSlide();
        $slide->setBackground($backgroundColorBG);

        return $slide;
    }

    /**
     * Convert centimetres to integer pixels for presentation geometry.
     *
     * @param  float|int  $centimeters  Length in centimetres.
     * @return int Length in pixels.
     */
    protected function centimetersToPixels($centimeters): int
    {
        /** @var int $pixels */
        $pixels = PPTUnitsHelper::convert($centimeters, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL);

        return $pixels;
    }

    /**
     * Create a rich text shape covering the entire slide
     */
    protected function createFullScreenRichTextShape(Slide $slide): RichText
    {
        $shape = $slide->createRichTextShape()
            ->setWidth(950)
            ->setHeight(500)
            ->setOffsetX(10)
            ->setOffsetY(0);

        $shape->getFill()?->setFillType(Fill::FILL_NONE);
        $shape->getBorder()->setLineStyle(Border::LINE_NONE);

        return $shape;
    }

    /**
     * @param  string  $filename  Name for the downloaded file.
     * @return BinaryFileResponse
     *
     * @throws \Exception
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
     *
     * @return void
     *
     * @throws \DOMException
     */
    /**
     * @param  string  $tempFile  Temporary output path.
     * @return void
     */
    protected function createPPTFile($tempFile)
    {
        $objWriter = IOFactory::createWriter($this->ppt, 'PowerPoint2007');
        $objWriter->save($tempFile);

        $patchFile = PowerPoint::fromFile($tempFile);
        $patchFile->applyImageFileTypeFix();
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
     *
     * @return void
     *
     * @throws \DOMException
     */
    /**
     * @param  string  $tempFile  Temporary output path.
     * @return void
     */
    protected function createODPFile($tempFile)
    {
        $objWriter = IOFactory::createWriter($this->ppt, 'ODPresentation');
        $objWriter->save($tempFile);

        $patchFile = ODP::fromFile($tempFile);
        $patchFile->applyTitleSlideTransparencyFix();
        $patchFile->applyTextEntityDecodingFix();
        $patchFile->applySlideNameFix($this->slideNames);
        $patchFile->injectBasicMacroFromBas(
            resource_path('macros/LO/ODF/LoopListener.bas')
        );

    }

    /**
     * Set document metadata from the exported service.
     *
     * @param  Service  $service  Service represented by the presentation.
     * @return void
     */
    protected function setDocumentProperties(Service $service)
    {
        $titleLine = $service->titleText(false).' am '.$service->dateText().', '.$service->timeText(
        ).', '.$service->locationText();
        $userName = Auth::user()?->fullName() ?? '';
        $this->ppt->getDocumentProperties()
            ->setCreator($userName)
            ->setLastModifiedBy($userName)
            ->setTitle('Lieder und Texte')
            ->setSubject($titleLine)
            ->setDescription($titleLine)
            ->setCategory('Gottesdienst')
            ->setKeywords('Gottesdienst, Lieder, Psalm, Mitwirkende')
            ->setCompany('Evangelische Kirchengemeinde '.$service->city?->name);
    }

    /**
     * Render a complete loop of ad slides
     *
     * @param  bool  $isFinalLoop  True if this loop is the very end of the presentation
     * @return void
     */
    protected function renderAdLoop(Service $service, bool $isFinalLoop = false)
    {
        if (! count($this->config['showAdsFromCities'] ?? [])) {
            return;
        }

        $date = $service->date;
        if (! $date instanceof Carbon) {
            return;
        }
        $start = $date->copy()->addHour();
        $end = $date->copy()->addWeek()->startOfWeek();
        if ($date->diffInDays($end) < 6) {
            $end->addWeek();
        }

        $announcements = new Announcements($service, $this->config['showAdsFromCities'], false, 'YYYY-MM-DD');

        // get the events to be listed (if not already cached)
        if (! count($this->adEventsToBeListed)) {
            /** @var Collection<int|string, Occurence> $events */
            $events = $announcements->getEvents();
            $this->adEventsToBeListed = $events;
        }

        // get the events to be highlighted (if not already cached)
        if (! count($this->adEventsToBeHighlighted)) {
            $this->adEventsToBeHighlighted = Occurence::adRunningAt('ppt', $date)
                ->whereHas('service', function ($query) use ($service) {
                    $query->inCities($this->config['showAdsFromCities'])
                        ->notHidden()
                        ->displayable($service->date);
                })->orderBy('start')
                ->get()
                ->groupBy(function ($occurence) {
                    return $occurence->start->setTimeZone('Europe/Berlin')->format('Y-m-d');
                });
        }

        $renderableHighlightedEvents = collect($this->adEventsToBeHighlighted)
            ->map(function ($events) {
                /** @var array<int, Occurence> $events */
                return collect($events)
                    ->filter(function (Occurence $event) {
                        return ! empty($event->service?->getImageCutPath('bildschirm-16x9'));
                    })
                    ->values();
            })
            ->filter(function (Collection $events) {
                return $events->isNotEmpty();
            });
        /** @var Collection<string, Collection<int, Occurence>> $renderableHighlightedEvents */
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
            $adSlidesCount += (int) $this->config['includeVirtualSongsheetQR'] + (int) $this->config['includeSongList'];
            $finalAdSlideNumber += (int) $this->config['includeVirtualSongsheetQR'] + (int) $this->config['includeSongList'];
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
                    $currentSlideNumber + 1,
                    $finalAdSlideNumber,
                    $isFinalLoop);
            }
            /** @var Collection<int, Occurence> $highlightedEventsForDay */
            $highlightedEventsForDay = $renderableHighlightedEvents[$cursor->format('Y-m-d')] ?? collect();
            if (count($highlightedEventsForDay)) {
                foreach ($highlightedEventsForDay as $event) {
                    $this->renderEventHighlightSlide($cursor,
                        $event,
                        $currentSlideNumber + 1,
                        $finalAdSlideNumber,
                        $isFinalLoop);
                }
            }
            $cursor->addDay();
        }

        // add future highlights
        foreach ($renderableHighlightedEvents as $date => $events) {
            $cursor = Carbon::parse($date);
            if ($cursor->gt($end)) {
                foreach ($events as $event) {
                    $this->renderEventHighlightSlide($cursor,
                        $event,
                        $currentSlideNumber + 1,
                        $finalAdSlideNumber,
                        $isFinalLoop);
                }

            }
        }
    }

    /**
     * Split event lists into slide-sized chunks per day.
     *
     * @param  iterable<int|string, Occurence>  $eventsByDate  Events grouped by date.
     * @return array<int|string, array<int, Collection<int, mixed>>>
     */
    protected function paginateEventListSlidesByDate(iterable $eventsByDate): array
    {
        $pagesByDate = [];

        foreach ($eventsByDate as $date => $events) {
            /** @var array<int, mixed> $events */
            $pagesByDate[$date] = $this->paginateEventListSlides(collect($events));
        }

        return $pagesByDate;
    }

    /**
     * Remove event list slides whose entries are all rendered as dedicated highlight slides.
     *
     * @param  array<int|string, array<int, Collection<int, mixed>>>  $pagesByDate
     * @param  iterable<string, mixed>  $highlightedEventsByDate  Highlighted events by date.
     * @return array<int|string, array<int, Collection<int, mixed>>>
     */
    protected function suppressHighlightedOnlyEventListSlides(array $pagesByDate, iterable $highlightedEventsByDate): array
    {
        $highlightKeysByDate = [];

        foreach ($highlightedEventsByDate as $date => $events) {
            /** @var array<int, mixed> $events */
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

            if (! count($highlightKeys)) {
                continue;
            }

            $pagesByDate[$date] = array_values(array_filter($pages, function (Collection $page) use ($highlightKeys) {
                return $page->contains(function ($event) use ($highlightKeys) {
                    $comparisonKey = $this->getEventListComparisonKey($event);

                    return empty($comparisonKey) || ! in_array($comparisonKey, $highlightKeys, true);
                });
            }));
        }

        return $pagesByDate;
    }

    /**
     * Split a single day's event list into multiple slide-sized chunks.
     *
     * @param  Collection<int, mixed>  $events  Events of the day.
     * @return array<int, Collection<int, mixed>>
     */
    protected function paginateEventListSlides(Collection $events): array
    {
        if ($events->isEmpty()) {
            return [];
        }

        $layout = $this->getEventsListLayout();
        $titleLineHeight = PPTUnitsHelper::estimateLineHeightPx($layout['titleFontTtfPath'], $layout['listFontSize']);
        $descriptionLineHeight = PPTUnitsHelper::estimateLineHeightPx($layout['descriptionFontTtfPath'], $layout['descriptionFontSize']);
        $locationLineHeight = PPTUnitsHelper::estimateLineHeightPx($layout['locationFontTtfPath'], $layout['locationFontSize']);

        $pages = [];
        $currentPageEvents = collect();
        $currentHeight = 0;

        foreach ($events as $event) {
            $eventHeight = $this->estimateEventListEntryHeight(
                $event,
                $layout,
                $titleLineHeight,
                $descriptionLineHeight,
                $locationLineHeight
            );

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
     * @param  mixed  $event
     */
    protected function getEventListComparisonKey($event): ?string
    {
        /** @var object{id?: int|string, service?: object{id?: int|string}, start?: string|int|\Stringable}|null $listEvent */
        $listEvent = is_object($event) ? $event : null;
        if ($listEvent === null) {
            return null;
        }

        if (isset($listEvent->id) && $listEvent->id) {
            return 'id:'.$listEvent->id;
        }

        if (method_exists($listEvent, 'getKey') && $listEvent->getKey()) {
            /** @var int|string $key */
            $key = $listEvent->getKey();

            return 'key:'.$key;
        }

        if (isset($listEvent->service) && isset($listEvent->start)) {
            /** @var object{id?: int|string} $service */
            $service = $listEvent->service;
            if (isset($service->id)) {
                return 'service:'.$service->id.'|start:'.$listEvent->start;
            }
        }

        return null;
    }

    /**
     * Get the layout configuration for event list slides.
     *
     * @return EventListLayout
     */
    protected function getEventsListLayout(): array
    {
        $listFontSize = (int) ($this->config['fontSize'] * 0.8);
        $locationFontSize = (int) ($listFontSize * 0.6);
        $descriptionFontSize = $locationFontSize;
        $listWidth = 950;
        $listHeight = 500;
        $leftColumnWidthPixels = $this->centimetersToPixels(5.75);
        $rightColumnWidth = $listWidth - $leftColumnWidthPixels;
        $detailsInnerPaddingPx = 10;

        return [
            'listFontSize' => $listFontSize,
            'descriptionFontSize' => $descriptionFontSize,
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
            'descriptionFontTtfPath' => resource_path('fonts/Sarabun-SemiBold.ttf'),
            'locationFontTtfPath' => resource_path('fonts/Sarabun-Light.ttf'),
            'titleMarginBottom' => 4,
            'descriptionMarginBottom' => 4,
            'locationMarginBottom' => 14,
        ];
    }

    /**
     * Build the wrapped text layout for a single event list entry.
     *
     * @param  mixed  $event
     * @param  EventListLayout  $layout
     * @return array{titleLines: list<string>, descriptionLines: list<string>, locationText: string}
     */
    protected function getEventListEntryLayout($event, array $layout): array
    {
        /** @var Occurence $listEvent */
        $listEvent = $event;
        $titleText = $listEvent->getAdText('ppt', $listEvent->service?->titleText(false) ?? '');
        $descriptionText = trim($listEvent->event?->descriptionText(['baptism']) ?? '');
        /** @var list<string> $titleLines */
        $titleLines = PPTUnitsHelper::wrapTextByPixelWidth(
            $titleText,
            $layout['titleFontTtfPath'],
            $layout['listFontSize'],
            $layout['maxTitleWidthPx']
        );
        /** @var list<string> $descriptionLines */
        $descriptionLines = $descriptionText === ''
            ? []
            : PPTUnitsHelper::wrapTextByPixelWidth(
                $descriptionText,
                $layout['descriptionFontTtfPath'],
                $layout['descriptionFontSize'],
                $layout['maxTitleWidthPx']
            );

        /** @var string $locationText */
        $locationText = $listEvent->service ? $listEvent->service->locationTextWithCity : '';

        return [
            'titleLines' => $titleLines,
            'descriptionLines' => $descriptionLines,
            'locationText' => $locationText,
        ];
    }

    /**
     * Estimate the vertical space needed for one event in the list slide layout.
     *
     * @param  mixed  $event
     * @param  EventListLayout  $layout
     */
    protected function estimateEventListEntryHeight($event, array $layout, int $titleLineHeight, int $descriptionLineHeight, int $locationLineHeight): int
    {
        $entryLayout = $this->getEventListEntryLayout($event, $layout);

        return (count($entryLayout['titleLines']) * ($titleLineHeight + $layout['titleMarginBottom']))
            + (count($entryLayout['descriptionLines']) * ($descriptionLineHeight + $layout['descriptionMarginBottom']))
            + $locationLineHeight
            + $layout['locationMarginBottom'];
    }

    /**
     * Create an empty ad slide
     *
     * @param  int  $firstAdSlideNumber  Number of the first slide in the loop
     * @param  int  $finalAdSlideNumber  Number of the last slide in the loop
     * @param  bool  $isFinalLoop  True, if this loop is at the very end of the presentation
     */
    protected function createEmptyAdSlide(int $firstAdSlideNumber, int $finalAdSlideNumber, bool $isFinalLoop): Slide
    {
        $slide = $this->createEmptySlide($this->config['backgroundColor']);
        if ($this->config['adLoopDelay']) {
            $transition = new Slide\Transition;
            $transition->setTimeTrigger(true, $this->config['adLoopDelay'] * 1000);
            $slide->setTransition($transition);
            if ($this->ppt->getSlideCount() == $firstAdSlideNumber) {
                $slideName = 'LOOP_START_'.uniqid();
                $slide->setName($slideName);
                $this->slideNames[$this->ppt->getSlideCount()] = $slideName;
            } elseif ($this->ppt->getSlideCount() == $finalAdSlideNumber) {
                $slideName = 'LOOP_END_'.uniqid();
                $slide->setName($slideName);
                $this->slideNames[$this->ppt->getSlideCount()] = $slideName;
            }
        }

        return $slide;
    }

    /**
     * Create a slide for a highlighted event
     *
     * @param  int  $firstAdSlideNumber  Number of the first slide in the loop
     * @param  int  $finalAdSlideNumber  Number of the last slide in the loop
     * @param  bool  $isFinalLoop  True, if this loop is at the very end of the presentation
     * @return void
     *
     * @throws FileNotFoundException
     */
    protected function renderEventHighlightSlide(Carbon $date, Occurence $event, int $firstAdSlideNumber, int $finalAdSlideNumber, bool $isFinalLoop)
    {
        $imageCutPath = $event->service?->getImageCutPath('bildschirm-16x9');
        if (empty($imageCutPath)) {
            return;
        }

        $textColor = new Color($this->config['textColor']);
        $gray = new Color('777777');
        $inverseTextColor = new Color($this->config['backgroundColor']);
        $shapeBackground = new Color('CCFFFFFF');

        $averageImageColor = ImageService::sampleAverageRgb($imageCutPath, 20, 470, 150, 150);
        $overlayARGB = ImageService::tintedOverlayArgb($averageImageColor, 0.95);
        $overlayColor = new Color($overlayARGB);
        $overlayTextColor = new Color(ImageService::blackOrWhiteForArgb($overlayARGB));

        $slideWidth = (int) Drawing::emuToPixels((int) $this->ppt->getLayout()->getCX());
        $slideHeight = (int) Drawing::emuToPixels((int) $this->ppt->getLayout()->getCY());

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
        $shape->getFill()?->setFillType(Fill::FILL_SOLID)
            ->setStartColor($shapeBackground);

        $paragraph = $shape->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setMarginLeft(150);
        if (! $event->event?->is_allday) {
            $run = $paragraph->createTextRun($event->start->copy()->setTimezone('Europe/Berlin')->format('H:i').' Uhr')->getFont()
                ->setBold(false)
                ->setSize((int) ($this->config['fontSize'] * 0.5))
                ->setColor($inverseTextColor)
                ->setName('Sarabun Light');
            $run = $paragraph->createTextRun(' | ')->getFont()
                ->setBold(false)
                ->setColor($gray)
                ->setSize((int) ($this->config['fontSize'] * 0.5))
                ->setName('Sarabun Light');
        }
        /** @var string $locationText */
        $locationText = $event->service->locationTextWithCity;
        $run = $paragraph->createTextRun($locationText)
            ->getFont()
            ->setBold(false)
            ->setSize((int) ($this->config['fontSize'] * 0.5))
            ->setColor($inverseTextColor)
            ->setName('Sarabun Light');

        $paragraph = $shape->createParagraph();
        $run = $paragraph->createTextRun($event->getAdText('ppt', $event->service?->titleText(false) ?? ''))
            ->getFont()
            ->setName('Sarabun SemiBold')
            ->setSize($this->config['fontSize'])
            ->setColor($inverseTextColor);

        $multiDay = $event->event?->is_allday && ($event->start->copy()->setTimeZone('Europe/Berlin')->format('Ymd') != $event->end->copy()->setTimeZone('Europe/Berlin')->format('Ymd'));

        $shape = $slide->createAutoShape()
            ->setType(AutoShape::TYPE_OVAL)
            ->setWidth(150)
            ->setHeight(150)
            ->setOffsetX(20)
            ->setOffsetY($slideHeight - 250)
            ->getFill()?->setFillType(Fill::FILL_SOLID)
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
     *
     * @param  Collection<Occurence>  $events
     * @param  int  $firstAdSlideNumber  Number of the first slide in the loop
     * @param  int  $finalAdSlideNumber  Number of the last slide in the loop
     * @param  bool  $isFinalLoop  True, if this loop is at the very end of the presentation
     * @param  Collection<int, mixed>  $events  Events rendered on this slide.
     * @return void
     *
     * @throws OutOfBoundsException
     */
    protected function renderEventsListSlide(
        Carbon $date,
        Collection $events,
        int $firstAdSlideNumber,
        int $finalAdSlideNumber,
        bool $isFinalLoop
    ) {
        $textColor = new Color($this->config['textColor']);
        $gray = new Color('777777');

        $slide = $this->createEmptyAdSlide($firstAdSlideNumber, $finalAdSlideNumber, $isFinalLoop);

        $this->renderEventsListSlideHeader($slide, $date, $textColor, $gray);
        $layout = $this->getEventsListLayout();

        // Shapes
        $timeColumnShape = $slide->createRichTextShape()
            ->setOffsetX($layout['listOffsetX'])
            ->setOffsetY($layout['listOffsetY'])
            ->setWidth($layout['leftColumnWidthPixels'])
            ->setHeight($layout['listHeight']);

        $timeColumnShape->getFill()?->setFillType(Fill::FILL_NONE);
        $timeColumnShape->getBorder()->setLineStyle(Border::LINE_NONE);

        $detailsColumnShape = $slide->createRichTextShape()
            ->setOffsetX($layout['rightColumnOffsetX'])
            ->setOffsetY($layout['listOffsetY'])
            ->setWidth($layout['rightColumnWidth'])
            ->setHeight($layout['listHeight']);

        $detailsColumnShape->getFill()?->setFillType(Fill::FILL_NONE);
        $detailsColumnShape->getBorder()->setLineStyle(Border::LINE_NONE);

        $isFirstTimeParagraph = true;
        $isFirstDetailsParagraph = true;

        foreach ($events as $event) {
            /** @var Occurence $listEvent */
            $listEvent = $event;
            $timeText = (! $listEvent->event?->is_allday) ? $listEvent->service?->timeText() ?? '' : '';
            $entryLayout = $this->getEventListEntryLayout($listEvent, $layout);
            $titleLines = $entryLayout['titleLines'];
            $descriptionLines = $entryLayout['descriptionLines'];

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

            foreach ($descriptionLines as $descriptionLine) {
                $timeDescriptionSpacerParagraph = $timeColumnShape->createParagraph();
                $timeDescriptionSpacerParagraph->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setMarginRight(10)
                    ->setMarginBottom($layout['descriptionMarginBottom']);

                $timeDescriptionSpacerParagraph->createTextRun("\u{00A0}")
                    ->getFont()
                    ->setBold(false)
                    ->setSize($layout['descriptionFontSize'])
                    ->setColor($textColor)
                    ->setName('Sarabun SemiBold');
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

            foreach ($descriptionLines as $descriptionLine) {
                $descriptionParagraph = $detailsColumnShape->createParagraph();
                $descriptionParagraph->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setMarginBottom($layout['descriptionMarginBottom']);

                $descriptionParagraph->createTextRun($descriptionLine)
                    ->getFont()
                    ->setBold(true)
                    ->setSize($layout['descriptionFontSize'])
                    ->setColor($textColor)
                    ->setName('Sarabun SemiBold');
            }

            $locationParagraph = $detailsColumnShape->createParagraph();
            $locationParagraph->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setMarginBottom(14);

            $locationParagraph->createTextRun($entryLayout['locationText'])
                ->getFont()
                ->setBold(false)
                ->setSize($layout['locationFontSize'])
                ->setColor($textColor)
                ->setName('Sarabun Light');
        }
    }

    /**
     * Render the repeated day header for event list slides.
     */
    protected function renderEventsListSlideHeader(Slide $slide, Carbon $date, Color $textColor, Color $gray): void
    {
        $headerShape = $slide->createRichTextShape()
            ->setWidth(950)
            ->setHeight(50)
            ->setOffsetX(10)
            ->setOffsetY(0);

        $headerShape->getFill()?->setFillType(Fill::FILL_NONE);
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

    /**
     * Render the QR code for the virtual song sheet.
     *
     * @param  Slide  $slide  Slide receiving the QR code.
     * @param  Service  $service  Service whose QR code is rendered.
     * @return void
     */
    public function renderVirtualSongsheetQR(Slide $slide, Service $service)
    {
        $shape = $slide->createRichTextShape()
            ->setWidth(950)
            ->setHeight(50)
            ->setOffsetX(10)
            ->setOffsetY(0);

        $paragraph = $shape->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $run = $paragraph->createTextRun('Digitales Liedblatt zum Gottesdienst:')->getFont()
            ->setSize((int) ($this->config['fontSize'] * 0.8))
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
