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

use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Service;
use Inertia\Inertia;

class AIPromptLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'KI-Prompt';

    protected $icon = 'fa fa-file-text';

    protected $isNotAFile = true;

    protected $configurationComponent = 'AIPromptLiturgySheetDialog';

    protected $configurationCloseOnly = true;

    protected $privileged = true;

    public function render(Service $service)
    {
        $prompt = $this->buildPrompt($service);

        return Inertia::render('Liturgy/LiturgySheets/KIPrompt', compact('prompt', 'service'));

    }

    public function getDialogData(Service $service): array
    {
        return ['prompt' => $this->buildPrompt($service)];
    }

    protected function buildPrompt(Service $service): string
    {
        $liturgy = $service->liturgical_info;
        $liturgy = is_array($liturgy) ? $liturgy : [];
        $prompt = 'Am '.$service->trueDate()->isoFormat('dddd, DD. MMMM YYYY').', '.$service->timeText().
            (($service->location && $service->location->at_text) ? ' '.$service->location->at_text : ', '.$service->locationText(
            ));
        $bezeichnung = $liturgy['Bezeichnung'] ?? null;
        if (is_scalar($bezeichnung) && $bezeichnung) {
            $wochenspruch = $liturgy['Wochenspruch'] ?? [];
            $wochenspruch = is_array($wochenspruch) ? $wochenspruch : [];
            $spruchText = $wochenspruch['Text'] ?? '';
            $spruchStelle = $wochenspruch['Bibelstelle'] ?? '';
            $prompt .= ' ist '.(string) $bezeichnung.'.'.PHP_EOL.'Wochenspruch ist: '
                .(is_scalar($spruchText) ? (string) $spruchText : '')
                .' ('.(is_scalar($spruchStelle) ? (string) $spruchStelle : '').').';
        } else {
            $prompt .= ' feiern wir Gottesdienst.';
        }
        $prompt .= PHP_EOL.PHP_EOL.'Folgender Ablauf ist vorgesehen: '.PHP_EOL.PHP_EOL;
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (($item->data_type == 'song') && (isset($item->data['song']))) {
                    $helper = new SongItemHelper($item);
                    $verseCount = $helper->getActiveVerseCount(true, true);

                    $songData = $item->data['song'] ?? null;
                    $songData = is_array($songData) ? $songData : [];
                    $code = $songData['code'] ?? null;
                    if ($code === null) {
                        $songbook = $songData['songbook'] ?? null;
                        $code = is_array($songbook) ? ($songbook['name'] ?? '') : '';
                    }
                    $code = is_scalar($code) ? (string) $code : '';
                    $reference = $songData['reference'] ?? '';
                    $reference = is_scalar($reference) ? (string) $reference : '';
                    $altEG = $songData['altEG'] ?? null;
                    $altEGText = isset($songData['altEG']) && is_scalar($altEG) ? '(EG '.(string) $altEG.') ' : '';
                    $songDetails = $songData['song'] ?? null;
                    $songDetails = is_array($songDetails) ? $songDetails : [];
                    $songTitle = $songDetails['title'] ?? '';
                    $songTitle = is_scalar($songTitle) ? (string) $songTitle : '';

                    $prompt .= '  -> '.$item->title.': '.$code.' '.$reference.' '.$altEGText.$songTitle
                        .$helper->forceVerseString(', ')
                        .($verseCount ? ' ('.$verseCount.')' : '')
                        .PHP_EOL;
                } elseif ($item->data_type == 'psalm') {
                    if (isset($item->data['psalm'])) {
                        $psalmData = $item->data['psalm'];
                        $psalmData = is_array($psalmData) ? $psalmData : [];
                        $book = $psalmData['songbook_abbreviation'] ?? $psalmData['songbook'] ?? '';
                        $book = is_scalar($book) ? (string) $book : '';
                        $reference = $psalmData['reference'] ?? '';
                        $reference = is_scalar($reference) ? (string) $reference : '';
                        $title = $psalmData['title'] ?? '';
                        $title = is_scalar($title) ? (string) $title : '';
                        $verses = $item->data['verses'] ?? '';
                        $versesText = isset($item->data['verses']) && $verses != '' && is_scalar($verses)
                            ? ', '.(string) $verses
                            : '';
                        $prompt .= '  -> '.$item->title.': '.$book.' '.$reference.' '.$title.$versesText.PHP_EOL;
                    }
                } elseif ($item->data_type == 'reading') {
                    $reference = $item->data['reference'] ?? '--';
                    $prompt .= $item->title.': '.(is_scalar($reference) ? (string) $reference : '--').PHP_EOL;
                } else {
                    $prompt .= $item->title.PHP_EOL;
                }
            }
        }

        $predigt = $liturgy['Predigt'] ?? null;
        $predigt = is_array($predigt) ? $predigt : [];
        $bibelstelle = $predigt['Bibelstelle'] ?? null;
        if (is_scalar($bibelstelle) && $bibelstelle !== '') {
            $prompt .= PHP_EOL.PHP_EOL.'Predigttext ist '.(string) $bibelstelle.'.';
        }

        return $prompt.PHP_EOL.PHP_EOL;
    }
}
