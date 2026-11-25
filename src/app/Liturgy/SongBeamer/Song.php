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

namespace App\Liturgy\SongBeamer;

use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Liturgy\Item;

class Song extends AbstractSngFile
{
    /** @var Item */
    protected $item;

    /** @var SongItemHelper */
    protected $helper;

    public function __construct(Item $item)
    {
        $this->item = $item;
        $this->helper = new SongItemHelper($item);
        parent::__construct($this->helper->getTitleText());
    }

    /**
     * Erzeugt den SNG-Text des Liedes aus den Item-Daten.
     *
     * @return string SNG-Text des Liedes
     */
    public function toText()
    {
        $songDetails = $this->songDetails();
        $songTitle = $songDetails['title'] ?? '';
        $songTitle = is_string($songTitle) ? $songTitle : '';
        $this->setHeaders([
            'LangCount' => 1,
            'Title' => $songTitle,
            'ChurchSongID' => $this->helper->getCodeText(),
            'Songbook' => $this->helper->getCodeText(),
            'CCLI' => '-',
        ]);

        if ($author = $this->helper->getTextAuthor()) {
            $this->addHeader('Author', $author);
        }

        if ($composer = $this->helper->getComposer()) {
            $this->addHeader('Melody', $composer);
        }

        if (empty($author.$composer)) {
            $this->addHeader('Rights', $this->helper->getRights());
        }

        $text = $this->headersToText();

        $refrain = $songDetails['refrain'] ?? '';
        $refrain = is_string($refrain) ? $refrain : '';
        if ($refrain) {
            $text .= $this->line('---')
                .$this->line('Refrain')
                .$this->wordWrap($refrain);
        }

        $verses = $songDetails['verses'] ?? [];
        $verses = is_array($verses) ? $verses : [];
        foreach ($verses as $verse) {
            if (! is_array($verse)) {
                continue;
            }
            $number = $verse['number'] ?? '';
            $number = is_scalar($number) ? (string) $number : '';
            $verseText = $verse['text'] ?? '';
            $verseText = is_string($verseText) ? $verseText : '';
            $text .= $this->line('---')
                .$this->line('Vers '.$number)
                .$this->wordWrap($verseText);
        }

        return utf8_decode($text);
    }

    /**
     * Get the data for the schedule file
     */
    public function toScheduleItem(): array
    {
        $scheduleItem = parent::toScheduleItem();
        $scheduleItem['VerseOrder'] = $this->getVerseOrder();

        return $scheduleItem;
    }

    /**
     * Ermittelt die Reihenfolge der aktiven Strophen inklusive Refrains.
     *
     * @return string Komma-getrennte Strophenreihenfolge
     */
    public function getVerseOrder(): string
    {
        $order = [];
        foreach ($this->helper->getActiveVerses() as $verse) {
            if ($verse['refrain_before']) {
                $order[] = 'Refrain';
            }
            $number = $verse['number'] ?? '';
            $order[] = 'Vers '.(is_scalar($number) ? (string) $number : '');
            if ($verse['refrain_after']) {
                $order[] = 'Refrain';
            }
        }

        return implode(',', $order);
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    /**
     * Gibt die Beschriftung des Liedes mit Strophenangabe zurück.
     *
     * @return string Beschriftung des Liedes
     */
    public function getCaption()
    {
        $verses = $this->item->data['verses'] ?? '';
        $verses = is_scalar($verses) ? (string) $verses : '';

        return $this->item->title.': '.$this->getTitle().($verses ? ', '.$verses : '');
    }

    /**
     * Liest die Lieddetails aus den serialisierten Item-Daten.
     *
     * @return array<string, mixed> Lieddetails oder leeres Array bei fehlenden Daten
     */
    private function songDetails(): array
    {
        $songData = $this->item->data['song'] ?? null;
        if (! is_array($songData)) {
            return [];
        }
        $songDetails = $songData['song'] ?? null;
        if (! is_array($songDetails)) {
            return [];
        }

        /** @var array<string, mixed> $songDetails */
        return $songDetails;
    }
}
