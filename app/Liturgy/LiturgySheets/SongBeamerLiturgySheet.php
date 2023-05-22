<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.de
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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
use App\Liturgy\SongBeamer\ImportableSchedule;
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

class SongBeamerLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Songbeamer: Importierbarer Ablauf';
    protected $icon = 'mdi mdi-projector';
    protected $extension = 'zip';

    protected $configurationPage = 'Liturgy/LiturgySheets/SongBeamerSongSheetConfiguration';
    protected $configurationComponent = 'SBLiturgySheetConfiguration';

    protected $defaultConfig = [
        'includeJingleAndIntro' => 1,
        'includeCredits' => 1,
        'includeSongList' => 1,
    ];



    public function render(Service $service)
    {
        $songBeamerSchedule = new ImportableSchedule();

        if ($this->config['includeSongList'] ?? false) {
            $this->renderSongListSlide($service, $songBeamerSchedule);
        }

        if ($this->config['includeJingleAndIntro'] ?? false) {
            $songBeamerSchedule->addImport('Jingle', base_path('assets/jingle.mp4'));
            $songBeamerSchedule->addGeneralItem('Intro hier einfügen');
        }

        foreach ($service->liturgyBlocks as $block) {
            $songBeamerSchedule->addSectionTitle($block->title);
            $lastItem = null;

            foreach ($block->items as $item) {
                if ($lastItem
                    && ($lastItem->data_type == 'psalm')
                    && ($item->data_type == 'freetext')
                    && ($item->title == 'Ehr sei dem Vater')
                    && (isset($item->data['description']))) {
                    /** @var Liturgy\ItemHelpers\FreetextItemHelper $ftHelper */
                    $ftHelper = $item->getHelper();
                    $songBeamerSchedule->addSimpleTextItem('Ehr sei dem Vater', $ftHelper->getText());
                } else {
                    $methodName = 'add' . ucfirst($item->data_type) . 'Item';
                    if (method_exists($songBeamerSchedule, $methodName)) {
                        $songBeamerSchedule->$methodName($item);
                    } else {
                        $songBeamerSchedule->addGeneralItem($item);
                    }
                }

                $lastItem = $item;
            }
        }

        // add credits
        if ($this->config['includeCredits'] ?? false) {
            $songBeamerSchedule->addSimpleTextItem(
                'Mitwirkende',
                '$$M=Mitwirkende'
                . "\r\n"
                . '<h:20><valign:bottom><align:right><wordwrap>'
                . utf8_decode($service->credits)
                . '</wordwrap></align:right></valign></h:20>',
                [],
                ['UseSongProfile' => '@@True'],
                ['FontSize' => 8],
                false
            );
        }

        if ($this->config['includeJingleAndIntro'] ?? false) {
            $songBeamerSchedule->addImport('Jingle', base_path('assets/jingle.mp4'), false);
        }


        $this->sendToBrowser($this->getFileName($service, 'Texte und Lieder'), $songBeamerSchedule);
    }

    protected function sendToBrowser($filename, ImportableSchedule $sb)
    {
        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/zip');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        $sb->toZip();
        exit();
    }



    protected function renderSongListSlide(Service $service, ImportableSchedule $songBeamerSchedule)
    {
        $slideText = '';

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
            foreach ($listItems as $listItem) {
                if ($listItem['color']) $slideText .= '<posx:70><bgcolor:'.$listItem['color'].'><&nbsp></bgcolor></posx>';
                $slideText .= '<posx:100>'.$listItem['code'].'</posx>';
                $slideText .= '<posx:300>'.$listItem['ref'].'</posx>';
                $slideText .= '<posx:500>'.$listItem['verses'].'</posx>';
                $slideText .= "\r\n";
            }
        }

        $slideText = '<linespacing:1.1><align:left>'.$slideText.'</align></linespacing>';

        $songBeamerSchedule->addSimpleTextItem('Singen und Beten', $slideText);
    }

}
