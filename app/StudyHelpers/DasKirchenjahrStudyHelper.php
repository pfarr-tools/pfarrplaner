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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DasKirchenjahrStudyHelper extends AbstractStudyHelper
{

    public $title = 'Das Kirchenjahr';
    protected $records = [];



    function read(array $data): array
    {
        if ('' == ($content = $this->getContent(
                'https://www.daskirchenjahr.de/'
            ))) {
            return [];
        }
        preg_match_all('/<A href="tag.php\?name=&zeit=(.*?)&typ=Einfuehrung/', $content, $matches);
        $pages = collect($matches[1])->reject(function ($item) {
            return $item == 'Einleitung' || $item == 'Exkurs';
        });
        foreach ($pages as $page) {
            $this->records = array_merge($this->records, $this->readPage($page));
        }

        $this->records['Ewigkeitssonntag'] = ['[Das Kirchenjahr] Letzter Sonntag im Kirchenjahr' => 'https://www.daskirchenjahr.de/tag.php?name=letzter&zeit=Kirchenjahresende'];
        return $this->records;
    }
    function readPage(string $page): array
    {
        $records = [];
        $url = 'https://www.daskirchenjahr.de/tag.php?name=&zeit='.$page.'&typ=Einfuehrung';
        if ('' == ($content = $this->getContent($url))) {
            return [];
        }
        $content = Str::replace("\n", '', $content);
        preg_match_all('/<A href="(tag.php\?name=(?:.*?)&zeit=(?:.*?))"(?:.*?)>(.*?)<\/A>/', $content, $matches);
        foreach ($matches[0] as $index => $match) {
            if (!Str::contains($matches[1][$index], '&typ=')) {
                $title = $matches[2][$index];
                if (Str::contains($title, ' (')) $title = Str::before($title, ' (');
                $records[$title] = [ '[Das Kirchenjahr] '.$matches[2][$index] => 'https://www.daskirchenjahr.de/'.$matches[1][$index]];
            }
        }
        return $records;
    }

    protected function translateTitle($title)
    {
        $translations = [
            '. Advent' => '. Sonntag im Advent',
            'Altjahresabend' => 'Altjahrsabend',
            'letzter So.' => 'Letzter Sonntag',
            'So. v. d.' => 'Sonntag vor der',
            'So. n. Christfest' => 'Sonntag nach dem Christfest',
            'nach Christfest' => 'nach dem Christfest',
            'Passionszeit' => 'Fastenzeit',
            'So. n.' => 'Sonntag nach',
            'So n.' => 'Sonntag nach',
            'So.' => 'Sonntag',
            'Invocavit' => 'Invokavit',
            'Epiphanias (Erscheinungsfest)' => 'Epiphanias',
            'Palmarum / Palmsonntag' => 'Palmarum',
            'Misericordias' => 'Miserikordias',
            'Michaelistag' => 'Geburt Johannes des T&auml;ufers',
            'Martinstag' => 'St. Martin von Tours',
            'Nikolaustag' => 'Nikolaus von Myra',
            'Erntedank' => 'Erntedanktag',
            'Reformationsfest' => 'Reformationsgedenken',
            'Drittl.S.d.Kj.' => 'Drittletzter Sonntag im Kirchenjahr',
            'Vorletzter Sonntag d. Kj.' => 'Vorletzter Sonntag im Kirchenjahr',
            'Buß-und Bettag' => 'Bu&szlig;- und Bettag',
            'Tag der Geburt Johannes des Täufers (Johannis)' => 'Geburt Johannes des T&auml;ufers',
            'ä' => 'ae',
            'Estomihi' => 'Quinquagesimae',
            'Ä' => '&Auml;',
            'Ö' => '&Ouml;',
            'Ü' => '&Uuml;',
            'ö' => '&ouml;',
            'ü' => '&uuml;',
        ];

        $title = strtr($title, $translations);
        if ($title == 'Trinitatis') $title = 'Trinitatissonntag';
        return $title;
    }

    function getLinks(array $data): array
    {
        $title = $this->translateTitle($data['title']);
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$title] ?? []);
        return $data;
    }

}
