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

use App\Liturgy\ItemHelpers\FreetextItemHelper;
use App\Liturgy\SongBeamer\ImportableSchedule;
use App\Models\Liturgy\Songbook;
use App\Models\Liturgy\SongReference;
use App\Models\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Liturgy sheet rendered as a SongBeamer slide list. */
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

    /**
     * @param  Service  $service  Service to render.
     * @return mixed
     */
    public function render(Service $service)
    {
        $songBeamerSchedule = new ImportableSchedule;

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
                    /** @var FreetextItemHelper $ftHelper */
                    $ftHelper = $item->getHelper();
                    $songBeamerSchedule->addSimpleTextItem('Ehr sei dem Vater', $ftHelper->getText());
                } else {
                    $methodName = 'add'.ucfirst($item->data_type).'Item';
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
                ."\r\n"
                .'<h:20><valign:bottom><align:right><wordwrap>'
                .utf8_decode($service->credits)
                .'</wordwrap></align:right></valign></h:20>',
                [],
                ['UseSongProfile' => '@@True'],
                ['FontSize' => 8],
                false
            );
        }

        if ($this->config['includeJingleAndIntro'] ?? false) {
            $songBeamerSchedule->addImport('Jingle', base_path('assets/jingle.mp4'), false);
        }

        return $this->sendToBrowser($this->getFileName($service, 'Texte und Lieder'), $songBeamerSchedule);
    }

    /**
     * Send the generated SongBeamer schedule as a ZIP download.
     *
     * @param  string  $filename  Name of the downloaded file.
     * @return BinaryFileResponse Download response for the generated ZIP.
     */
    protected function sendToBrowser($filename, ImportableSchedule $sb)
    {
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Type: application/zip');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        $tempFile = $sb->toZip();
        if ($tempFile === false) {
            throw new \RuntimeException('SongBeamer-Archiv konnte nicht erstellt werden.');
        }

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Add a slide listing the service's songs and psalms to the schedule.
     *
     * @param  Service  $service  Service whose songs are listed.
     * @param  ImportableSchedule  $songBeamerSchedule  Schedule receiving the slide.
     */
    protected function renderSongListSlide(Service $service, ImportableSchedule $songBeamerSchedule): void
    {
        $slideText = '';

        /** @var array<int, array{ref: string, verses: string, image: string, code: string, color: string, psalm: bool}> $listItems */
        $listItems = [];
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                /** @var array<string, mixed> $data */
                $data = $item->data;
                if (($item->data_type == 'song') && isset($data['song'])) {
                    /** @var array{id?: int|string, reference?: string, songbook?: array{image?: string, code?: string}} $song */
                    $song = $data['song'];
                    $refColor = '';
                    if (isset($song['id'])) {
                        $songRef = SongReference::find($song['id']);
                        /** @var string|null $color */
                        $color = $songRef->color ?? null;
                        $refColor = $color ?? '';
                    }
                    /** @var string $verses */
                    $verses = $data['verses'] ?? '';
                    /** @var string $songbookImage */
                    $songbookImage = $song['songbook']['image'] ?? '';
                    /** @var string $songbookCode */
                    $songbookCode = $song['songbook']['code'] ?? '';
                    $listItems[] = [
                        'ref' => $song['reference'] ?? '',
                        'verses' => $verses,
                        'image' => $songbookImage,
                        'code' => $songbookCode,
                        'color' => $refColor,
                        'psalm' => false,
                    ];
                } elseif (($item->data_type == 'psalm') && isset($data['psalm'])) {
                    /** @var array{songbook_abbreviation?: string, reference?: string, title?: string} $psalm */
                    $psalm = $data['psalm'];
                    $refColor = '';
                    $songbook = null;
                    if (isset($psalm['songbook_abbreviation'])) {
                        $refColor = ($psalm['songbook_abbreviation'] == 'EG') ? '#c8baf7' : '';
                        $songbook = Songbook::where('code', $psalm['songbook_abbreviation'])->first();
                    }

                    /** @var string|null $songbookImage */
                    $songbookImage = $songbook ? $songbook->image : null;
                    $listItems[] = [
                        'ref' => $psalm['reference'] ?? '',
                        'verses' => $psalm['title'] ?? '',
                        'image' => $songbookImage ?? '',
                        'code' => $psalm['songbook_abbreviation'] ?? '',
                        'color' => $refColor,
                        'psalm' => true,
                    ];
                }
            }
        }

        if (count($listItems)) {
            foreach ($listItems as $listItem) {
                if ($listItem['color']) {
                    $slideText .= '<posx:70><bgcolor:'.$listItem['color'].'><&nbsp></bgcolor></posx>';
                }
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
