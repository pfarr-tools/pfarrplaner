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

namespace App\Liturgy\ItemHelpers;


class PsalmItemHelper extends AbstractItemHelper
{

    public function getCodeText()
    {
        if (!isset($this->item->data['psalm'])) return '';
        $codeText = $this->item->data['psalm']['code'] ?? $this->item->data['psalm']['songbook_abbreviation'] ?? '';
        $codeText .= ' '.($this->item->data['psalm']['reference'] ?? '')
            .(isset($this->item->data['psalm']['alt_eg']) ? ' (EG '.$this->item->data['psalm']['alt_eg'].')' : '');
        return $codeText;
    }

    public function getTitleText()
    {
        if (!isset($this->item->data['psalm'])) return '';
        return $this->item->data['psalm']['name'] ?? '';
    }

    public function getVerses()
    {
        if (!isset($this->item->data['psalm'])) return [];
        $verses = [];
        $verse = [];
        $indent = false;
        foreach(explode("\n", $this->item->data['psalm']['text']) as $line) {
            if ($indent && (substr($line, 0, 1) != "\t")) {
                $indent = false;
                $verses[] = $verse;
                $verse = [$line];
            } else {
                $verse[] = $line;
                $indent = (substr($line, 0, 1) == "\t");
            }
        }
        if (count($verse)) $verses[] = $verse;
        return $verses;
    }

    public function getFinalVerses($number = 2)
    {
        $verses = $this->getVerses();
        $stop = min($number, count($verses));
        $text = '';
        for ($i=count($verses)-1; $i>=count($verses)-$stop; $i--) {
            $thisVerse = '';
            foreach ($verses[$i] as $line) $thisVerse .= $line.PHP_EOL;
            $text = $thisVerse.$text;
        }
        return $text;
    }

}
