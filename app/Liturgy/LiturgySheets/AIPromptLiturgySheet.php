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


use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AIPromptLiturgySheet extends AbstractLiturgySheet
{

    protected $title = 'KI-Prompt';
    protected $icon = 'fa fa-file-text';
    protected $isNotAFile = true;
    protected $privileged = true;

    public function render(Service $service)
    {
        $liturgy = $service->liturgical_info;
        $prompt = 'Am ' . $service->date->isoFormat('dddd, DD. MMMM YYYY') . ', ' . $service->timeText() .
            (($service->location && $service->location->at_text) ? ' ' . $service->location->at_text : ', ' . $service->locationText(
                ));
        if ($liturgy['Bezeichnung'] ?? false) {
            $prompt .= ' ist ' . $liturgy['Bezeichnung'].'.'.PHP_EOL.'Wochenspruch ist: '
                .$liturgy['Wochenspruch']['Text'].' ('.$liturgy['Wochenspruch']['Bibelstelle'].').';
        } else {
            $prompt. ' feiern wir Gottesdienst.';
        }
        $prompt .= PHP_EOL . PHP_EOL.'Folgender Ablauf ist vorgesehen: '.PHP_EOL.PHP_EOL;
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                $concernsOrganists = false;

                if (($item->data_type == 'song') && (isset($item->data['song']))) {
                    $helper = new SongItemHelper($item);
                    $verseCount = $helper->getActiveVerseCount(true, true);

                    $prompt .= '  -> ' . $item->title . ': '
                        . ($item->data[$item->data_type]['code'] ?? $item->data[$item->data_type]['songbook']['name'] ?? '')
                        . ' '
                        . $item->data[$item->data_type]['reference'] . ' '
                        . (isset($item->data[$item->data_type]['altEG']) ? '(EG ' . $item->data[$item->data_type]['altEG'] . ') ' : '')
                        . ($item->data[$item->data_type]['song']['title'] ?? '')
                        . $helper->forceVerseString(', ')
                        . ($verseCount ? ' (' . $verseCount . ')' : '')
                        . PHP_EOL;
                } elseif ($item->data_type == 'psalm') {
                    if (isset($item->data['psalm'])) {
                        $prompt .= '  -> ' . $item->title . ': '
                            . ($item->data[$item->data_type]['songbook_abbreviation'] ?? $item->data[$item->data_type]['songbook'] ?? '')
                            . ' '
                            . $item->data[$item->data_type]['reference'] . ' '
                            . ($item->data[$item->data_type]['title'] ?? '')
                            . (isset($item->data['verses']) && ($item->data['verses'] != '') ? ', ' . $item->data['verses'] : '')
                            . PHP_EOL;
                    }
                } elseif ($item->data_type == 'reading') {
                    $prompt .=  $item->title . ': ' . ($item->data['reference'] ?? '--') . PHP_EOL;
                } else {
                    $prompt .=  $item->title  . PHP_EOL;
                }
            }
        }

        if ($liturgy['Predigt'] ?? false) {
            $prompt .= PHP_EOL . PHP_EOL . 'Predigttext ist ' .$liturgy['Predigt']['Bibelstelle'].'.';
        }
        $prompt .= PHP_EOL . PHP_EOL;

        return Inertia::render('Liturgy/LiturgySheets/KIPrompt', compact('prompt', 'service'));

    }
}
