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


use App\Helpers\PPTUnitsHelper;
use App\Liturgy;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Liturgy\Music\ABCMusic;
use App\Service;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Border;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\Mime\DraftEmail;

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

        if ($this->config['includeSongList']) {
            $this->renderSongListSlide($service);
        } elseif ($this->config['includeEmpty']) {
            $this->slide();
        }
        if ($this->config['includeJingleAndIntro']) {
            $this->slide('Hier Jingle einfügen');
            $this->slide('Hier Intro einfügen');
        }
        if ($this->config['includeEmpty']) {
            $this->slide();
        }

        $lastItem = null;

        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($item->data_type == 'song') {
                    $this->renderSongItem($item);
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

                if ($lastItem && ($lastItem->data_type=='psalm')) {
                    if (($item->data_type=='freetext') && ($item->title == 'Ehr sei dem Vater') && (isset($item->data['description']))) {
                        /** @var Liturgy\ItemHelpers\FreetextItemHelper $ftHelper */
                        $ftHelper = $item->getHelper();
                        $this->slide($ftHelper->getText());
                    }
                    if ($this->config['includeEmpty']) {
                        $this->slide();
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
            ->setName('Calibri');
        $paragraph->createTextRun($text);
    }

    protected function renderSongListSlide(Service $service)
    {
        $slide = $this->createEmptySlide($this->config['backgroundColor']);
        $color = new Color($this->config['textColor']);
        $listItems = [];
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (($item->data_type == 'song') && (isset($item->data['song']))){
                    $refColor = '';
                    if (isset($item->data['song']['id'])) {
                        $songRef = Liturgy\SongReference::find($item->data['song']['id']);
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
                        $songbook = Liturgy\Songbook::where('code', $item->data['psalm']['songbook_abbreviation'])->first();
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

    protected function renderSongItem(Liturgy\Item $item)
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
        foreach ($helper->getActiveVerses() as $verse) {
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
                $verse['number'] . '. ' . $verse['text'],
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

    protected function renderSongItemWithMusic(Liturgy\Item $item)
    {
        $song = Liturgy\Song::find($item->data['song']['song_id']);
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

    protected function slide($text = '', $size = -1, $rgb = -1, $bold = true, $copyrights = '', $backgroundColor = null)
    {
        if ($size == -1) {
            $size = $this->config['fontSize'];
        }
        if ($rgb == -1) {
            $rgb = $this->config['textColor'];
        }
        $color = new Color($rgb);
        $slide = $this->createEmptySlide(
            $backgroundColor ?: ($text ? $this->config['backgroundColor'] : $this->config['backgroundColorEmpty'])
        );
        if ($text) {
            if (!is_array($text)) {
                $text = [$text];
            }
            $text = str_replace("\n\t", "\r\t", $text);
            $shape = $this->createFullScreenRichTextShape($slide);
            $shape->setParagraphs([]);
            $ct = 0;
            foreach ($text as $line) {
                if ($ct = 0) {
                    $paragraph = $shape->getActiveParagraph();
                } else {
                    $paragraph = $shape->createParagraph();
                }
                $ct++;
                if (substr($line, 0, 1) == "\t") {
                    $paragraph->getAlignment()->setMarginLeft(35);
                    $line = substr($line, 1);
                }
                $paragraph->getAlignment()->setVertical($this->config['verticalAlignment']);
                $paragraph->getFont()->setBold($bold)->setSize($size)->setColor($color)->setName('Calibri');
                $paragraph->createTextRun(str_replace('&', '**', $line));
            }
        }
        if ($copyrights) {
            $shape = $slide->createRichTextShape()
                ->setWidth(950)
                ->setHeight(30)
                ->setOffsetX(10)
                ->setOffsetY(($text == '') ? 485 : 505);
            $paragraph = $shape->getActiveParagraph();
            $paragraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $paragraph->getFont()->setBold(false)->setSize(10)->setColor($color)->setName('Calibri');
            $paragraph->createTextRun($copyrights);
        }
        return $slide;
    }

    protected function songbookReferenceSlide(Liturgy\Item $item)
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
                $songbook = Liturgy\Songbook::where('code', $item->data['psalm']['songbook_abbreviation'])->first();
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
                ->setName('Calibri');
            $paragraph->createTextRun($data[$key]['songbook']['name']);
        } else {
            $shape = $slide->createDrawingShape();
            $shape->setName('')
                ->setPath(storage_path('app/' . $data[$key]['songbook']['image']))
                ->setResizeProportional(true)
                ->setWidth(PPTUnitsHelper::convert(4, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                ->setOffsetX(PPTUnitsHelper::convert(10.7, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL))
                ->setOffsetY(PPTUnitsHelper::convert(1.7, PPTUnitsHelper::UNIT_CENTIMETER, PPTUnitsHelper::UNIT_PIXEL));
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
            ->setName('Calibri');

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
        return $shape;
    }

    /**
     * @param $filename
     * @throws Exception
     */
    protected function sendToBrowser($filename)
    {
        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        $objWriter = IOFactory::createWriter($this->ppt, 'PowerPoint2007');
        $objWriter->save('php://output');
        exit();
    }

    protected function setDocumentProperties(Service $service)
    {
        $titleLine = $service->titleText(false) . ' am ' . $service->dateText() . ', ' . $service->timeText(
            ) . ', ' . $service->locationText();
        $this->ppt->getDocumentProperties()
            ->setCreator(Auth::user()->name)
            ->setLastModifiedBy(Auth::user()->name)
            ->setTitle('Lieder und Texte')
            ->setSubject($titleLine)
            ->setDescription($titleLine)
            ->setCategory('Gottesdienst')
            ->setKeywords('Gottesdienst, Lieder, Psalm, Mitwirkende')
            ->setCompany('Evangelische Kirchengemeinde ' . $service->city->name);
    }

}
