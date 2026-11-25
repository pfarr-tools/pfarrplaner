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

namespace App\Liturgy\ItemHelpers;

use Illuminate\Support\Str;

class SongItemHelper extends AbstractItemHelper
{
    /**
     * Extrahierte Liedinformationen wie Urheberrechte, Text- und Melodieautoren.
     *
     * @var array<string, string>
     */
    protected $info = [];

    /**
     * Extrahiert Urheberrechte sowie Text- und Melodieautoren aus den Lieddaten.
     */
    public function extractInfo(): void
    {
        $rights = $this->item->data['song']['song']['copyrights'] ?? '';
        if (str_contains($rights, '/')) {
            $parts = explode('/', $rights);
            foreach ($parts as $part) {
                $part = trim($part);
                if (Str::startsWith($part, 'Text:')) {
                    $this->info['text'] = trim(substr($part, 5));
                }
                if (Str::startsWith($part, 'Melodie:')) {
                    $this->info['melodie'] = trim(substr($part, 8));
                }
            }
        }
        $this->info['rights'] = (string) $rights;
    }

    /**
     * Gibt die Urheberrechtsangaben des Liedes zurück.
     */
    public function getRights(): string
    {
        if (! count($this->info)) {
            $this->extractInfo();
        }

        return $this->info['rights'] ?? '';
    }

    /**
     * Gibt den Textautor des Liedes zurück.
     */
    public function getTextAuthor(): string
    {
        if (! count($this->info)) {
            $this->extractInfo();
        }

        return $this->info['text'] ?? '';
    }

    /**
     * Gibt den Komponisten beziehungsweise Melodieautor des Liedes zurück.
     */
    public function getComposer(): string
    {
        if (! count($this->info)) {
            $this->extractInfo();
        }

        return $this->info['melodie'] ?? '';
    }

    /**
     * Gibt den Liedcode mit Referenz und gegebenenfalls alternativer EG-Nummer zurück.
     */
    public function getCodeText(): string
    {
        if (! isset($this->item->data['song'])) {
            return '';
        }
        $title = $this->item->data['song']['code'] ?? $this->getItem()->data['song']['songbook']['name'] ?? '';
        $title .= ' '.($this->item->data['song']['reference'] ?? '')
            .(isset($this->item->data['song']['song']) && isset($this->item->data['song']['song']['alt_eg']) ? ' (EG '.$this->item->data['song']['song']['alt_eg'].')' : '');

        return $title;
    }

    /**
     * Gibt den vollständigen Liedtitel zurück.
     */
    public function getTitleText(): string
    {
        if (! isset($this->item->data['song'])) {
            return '';
        }
        if (! isset($this->item->data['song']['song'])) {
            return '';
        }
        if (! isset($this->item->data['song']['song']['title'])) {
            return '';
        }
        $code = $this->getCodeText();
        $title = ($code ? $code.' ' : '').$this->item->data['song']['song']['title'];

        return trim(str_replace('  ', ' ', $title));
    }

    /**
     * Ermittelt die Nummern der aktiven Strophen aus den Lieddaten.
     *
     * @return list<int|string> Aktive Strophennummern oder -bereiche
     */
    public function getActiveVerseNumbers(): array
    {
        $verseRefs = [];
        if (($this->item->data['verses'] ?? '') == '') {
            if (! isset($this->item->data['song'])) {
                return [];
            }
            if (! isset($this->item->data['song']['song'])) {
                return [];
            }
            foreach ($this->item->data['song']['song']['verses'] as $verse) {
                $verseRefs[] = $verse['number'];
            }
        } else {
            foreach (explode('+', $this->item->data['verses']) as $range) {
                $subRange = explode('-', $range);
                if (count($subRange) == 1) {
                    $verseRefs[] = $subRange[0];
                } else {
                    if (is_numeric($subRange[0]) && is_numeric($subRange[1]) && ($subRange[0] < $subRange[1])) {
                        for ($i = $subRange[0]; $i <= $subRange[1]; $i++) {
                            $verseRefs[] = $i;
                        }
                    } else {
                        $verseRefs[] = $range;
                    }
                }
            }
        }

        return $verseRefs;
    }

    /**
     * Gibt die aktiven Strophen des Liedes zurück.
     *
     * @return list<array<string, mixed>>
     */
    public function getActiveVerses(): array
    {
        if (! isset($this->item->data['song'])) {
            return [];
        }
        if (! isset($this->item->data['song']['song'])) {
            return [];
        }
        $verses = [];
        foreach ($this->getActiveVerseNumbers() as $number) {
            foreach ($this->item->data['song']['song']['verses'] ?? [] as $verse) {
                if ($verse['number'] == $number) {
                    $verses[] = $verse;
                }
            }
        }

        return $verses;
    }

    /**
     * Gibt die Anzahl der aktiven Strophen zurück.
     *
     * @param  bool  $skipIfZero  Leere Strophenlisten als leeren String melden
     * @param  bool  $stropheText  Anzahl als Text mit "Strophe(n)" ausgeben
     */
    public function getActiveVerseCount($skipIfZero = false, $stropheText = false): int|string
    {
        if (! isset($this->item->data['song'])) {
            return 0;
        }
        if (! isset($this->item->data['song']['song'])) {
            return 0;
        }
        $count = count($this->getActiveVerseNumbers());
        if ($skipIfZero && ($count == 0)) {
            return '';
        }

        return $count.($stropheText ? ($count == 1 ? ' Strophe' : ' Strophen') : '');
    }

    /**
     * Erzeugt eine kompakte Strophenangabe für das Lied.
     *
     * @param  string  $prefix  Vorzustellender Text
     */
    public function forceVerseString($prefix = ''): string
    {
        if (! isset($this->item->data['song'])) {
            return '';
        }
        if (! isset($this->item->data['song']['song'])) {
            return '';
        }
        if (isset($this->item->data['verses'])) {
            return $prefix.$this->item->data['verses'];
        }
        $verseRefs = [];
        foreach ($this->item->data['song']['song']['verses'] as $verse) {
            $verseRefs[] = $verse['number'];
        }
        if (count($verseRefs) == 0) {
            return '';
        }
        if ((count($verseRefs) == 1) && ($verseRefs[0] == 1)) {
            return '';
        }
        if (count($verseRefs) == 1) {
            return $prefix.$verseRefs[0];
        }

        return $prefix.min($verseRefs).'-'.max($verseRefs);
    }
}
