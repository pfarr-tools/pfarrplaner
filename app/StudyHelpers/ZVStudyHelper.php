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

namespace App\StudyHelpers;

use App\StudyHelpers\AbstractStudyHelper;
use Illuminate\Support\Str;

class ZVStudyHelper extends AbstractStudyHelper
{

    public $title = 'Zentrum Verkündigung, Predigtvorschläge';
    protected $records = [];

    function read(): void
    {
        if ('' == ($content = $this->getContent('https://www.zentrum-verkuendigung.de/service/predigten/aktuell/'))) return;
        $content = Str::replace("\n", '', $content);
        preg_match_all('/<li>(.*?)<\/li>/', $content, $matches);

        $lastMatch = null;

        foreach ($matches[1] as $index => $li) {
            if (Str::substr($li, 0, 2) == '<a') {
                if (preg_match('/href="(.*?)">(.*?)<\/a> \| (\d\d\.\d\d\.\d\d\d\d) \| (.*)/', $li, $parts)) {
                    if (!isset($this->records[$parts[3]])) $this->records[$parts[3]] = [];
                    $this->records[$parts[3]]['[Zentrum Verkündigung] '.$parts[2].', '.$parts[3].': '.$parts[4]] = 'https://www.zentrum-verkuendigung.de'.$parts[1];
                    $lastMatch = [
                            'date' => $parts[3],
                            'title' => $parts[2].', '.$parts[3].': '.$parts[4],
                    ];
                } else {
                    preg_match('/href="(.*?)">(.*?)</', $li, $newParts);
                    if (Str::substr($newParts[1], 0, 2) == '/f') {
                        if (!isset($this->records[$lastMatch['date']])) $this->records[$lastMatch['date']] = [];
                        $this->records[$lastMatch['date']]['[Zentrum Verkündigung] '.$lastMatch['title'].' '.$newParts[2]] = 'https://www.zentrum-verkuendigung.de'.$newParts[1];
                    }
                }

            } else {
                preg_match('/(.*?) \| (\d\d\.\d\d\.\d\d\d\d) \| (.*)<ul(?:.*?)href="(.*?)"/', $li, $parts);
                if ($parts[2]=='Pfingstmontag') dd('baz');
                if (!isset($this->records[$parts[2]])) $this->records[$parts[2]] = [];
                $this->records[$parts[2]]['[Zentrum Verkündigung] '.$parts[1].', '.$parts[2].': '.$parts[3]] = 'https://www.zentrum-verkuendigung.de'.$parts[4];
                $lastMatch = [
                    'date' => $parts[2],
                    'title' => $parts[1].', '.$parts[2].': '.$parts[3],
                ];
            }

        }
    }

    function getLinks(array $data): array
    {
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$data['date']] ?? []);
        return $data;
    }

}
