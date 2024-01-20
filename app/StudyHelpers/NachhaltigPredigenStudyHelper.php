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

class NachhaltigPredigenStudyHelper extends AbstractStudyHelper
{

    public $title = 'Nachhaltig predigen';

    protected $records = [];

    function read(): void
    {
        if ('' == ($content = $this->getContent('http://www.nachhaltig-predigen.de/index.php/predigtanregungen/2023-24'))) return;
        $content = Str::replace("\n", '', $content);
        $content = Str::replace("\r", '', $content);
        preg_match_all('/<li><h4>(?:.*?)href="(.*?)">(?:<span class="mod-articles-category-title "(?: style="font-size: medium;")?>)?(.*?)(?:<\/span>)?<\/a>(?:.*?)<\/h4>/', $content, $matches);

        foreach ($matches[0] as $index => $match) {
            if (preg_match('/(.*?) \((\d+\.\d+\.\d+)\)/', $matches[2][$index], $parts)) {
                $date = Str::padLeft($parts[2], 2, '0');
                if (!isset($this->records[$date])) $this->records[$date] = [];
                $this->records[$date]['[Nachhaltig predigen] '.$parts[1].', '.$date] = 'http://www.nachhaltig-predigen.de'.$matches[1][$index];
            }
        }
    }

    function getLinks(array $data): array
    {
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$data['date']] ?? []);
        return $data;
    }
}
