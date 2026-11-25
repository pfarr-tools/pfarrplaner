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

namespace App\StudyHelpers;

use Illuminate\Support\Str;

class GPStudyHelper extends AbstractStudyHelper
{
    public $title = 'Göttinger Predigten im Internet';

    /** @var array<string, array<string, string>> */
    protected $records = [];

    /**
     * @param  array<string, mixed>  $data  Study helper input data.\n     * @return array<string, array<string, string>>
     */
    public function read(array $data): array
    {
        if ('' == ($content = $this->getContent('https://www.theologie.uzh.ch/predigten/category/aktuelle/'))) {
            return [];
        }
        $content = Str::replace("\n", '', (string) $content);
        preg_match_all('/<h3 class="entry-title no-date"><a href="(.*?)">(.*?)<\/a>(?:.*?)<p>(?:.*?)(\d\d.\d\d.\d\d)/', $content, $matches);

        foreach ($matches[0] as $index => $match) {
            $date = $this->translateDate($matches[3][$index]);
            if (! isset($this->records[$date])) {
                $this->records[$date] = [];
            }
            $this->records[$date]['[Göttinger Predigten] '.$date.': '.$matches[2][$index]] = $matches[1][$index];
        }

        return $this->records;
    }

    /**
     * Übersetzt ein Datum aus der Quelle in das im Kalender verwendete Format.
     *
     * @param  string  $date  Datum im Format der Quelle
     * @return string Übersetztes Datum
     */
    protected function translateDate(string $date): string
    {
        return Str::substr($date, 0, 8).'20'.substr($date, 8, 2);
    }

    /**
     * @param  array<string, mixed>  $data  Input data.\n     * @return array<string, string>
     */
    public function getLinks(array $data): array
    {
        /** @var array<string, string> $links Bereits vorhandene Links. */
        $links = $data['links'] ?? [];
        /** @var string $date Angefragtes Datum. */
        $date = $data['date'];
        $data['links'] = array_merge($links, $this->records[$date] ?? []);

        return $data;
    }
}
