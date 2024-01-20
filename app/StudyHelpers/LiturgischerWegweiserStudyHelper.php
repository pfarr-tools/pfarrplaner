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

class LiturgischerWegweiserStudyHelper extends AbstractStudyHelper
{

    public $title = 'Liturgischer Wegweiser';

    protected $records = [];

    function read(): void
    {
        // "Weitere Sonntage in der Trinitatiszeit
        if ('' == ($content = $this->getContent(
                'https://www.liturgischer-wegweiser.de/gebete-und-lieder/trinitatiszeit/weitere-sonntage-in-der-trinitatiszeit/'
            ))) {
            return;
        }
        $content = Str::replace("\n", '', $content);
        preg_match_all('/<li><a href="([^"<]+?)" class="c-mega-menu__sub-link">(.*?)<\/a><\/li>/', $content, $matches);

        foreach ($matches[0] as $index => $match) {
            $title = $matches[2][$index];
            if (preg_match('/(?:.*?)\– (.*)/', $title, $parts)) {
                $title = $parts[1];
            }
            $this->records[$title] = ['[Liturgischer Wegweiser] ' . $matches[2][$index] => 'https://www.liturgischer-wegweiser.de' . $matches[1][$index]];
        }

        $content = Str::betweenFirst(
            $content,
            '<div id="c6099" class="frame frame-default frame-type-text frame-layout-0"><p>',
            '</p>'
        );
        preg_match_all('/<a href="(.*?)">(.*?)<\/a>/', $content, $matches);
        foreach ($matches[0] as $index => $match) {
            $title = $matches[2][$index];
            if (preg_match('/^([^–]+?)+/', $title, $parts)) {
                $title = trim($parts[0]);
            }
            $this->records[$title] = ['[Liturgischer Wegweiser] ' . $matches[2][$index] => 'https://www.liturgischer-wegweiser.de' . $matches[1][$index]];
        }
    }

    protected function translateTitle($title)
    {
        $translations = [
            '10.' => 'Zehnter',
            '11.' => 'Elfter',
            '12.' => 'Zwölfter',
            '13.' => 'Dreizehnter',
            '14.' => 'Vierzehnter',
            '15.' => 'Fünfzehnter',
            '16.' => 'Sechzehnter',
            '17.' => 'Siebzehnter',
            '18.' => 'Achtzehnter',
            '19.' => 'Neunzehnter',
            '20.' => 'Neunzehnter',
            '21.' => 'Einundzwanzigster',
            '22.' => 'Zweiundzwanzigster',
            '23.' => 'Dreiundzwanzigster',
            '24.' => 'Vierundzwanzigster',
            '1.' => 'Erster',
            '2.' => 'Zweiter',
            '3.' => 'Dritter',
            '4.' => 'Vierter',
            '5.' => 'Fünfter',
            '6.' => 'Sechster',
            '7.' => 'Siebter',
            '8.' => 'Achter',
            '9.' => 'Neunter',
            ' Advent' => ' Sonntag im Advent',
            'Altjahresabend' => 'Altjahrsabend',
            'letzter So.' => 'Letzter Sonntag',
            'So. v. d.' => 'Sonntag vor der',
            'So. n. Christfest' => 'Sonntag nach dem Christfest',
            'So. n.' => 'Sonntag nach',
            'So n.' => 'Sonntag nach',
            'So.' => 'Sonntag',
            'Invocavit' => 'Invokavit',
            'Epiphanias (Erscheinungsfest)' => 'Epiphanias',
            'Palmarum / Palmsonntag' => 'Palmsonntag',
            'Misericordias' => 'Miserikordias',
            'Michaelistag' => 'Michaelis',
            'Erntedank' => 'Erntedankfest',
            'Reformationsfest' => 'Gedenktag der Reformation',
            'Drittl.S.d.Kj.' => 'Drittletzter Sonntag des Kirchenjahres',
            'Buß-und Bettag' => 'Buß- und Bettag',
            'Tag der Geburt Johannes des Täufers (Johannis)' => 'Johannis',
        ];

        $title = strtr($title, $translations);
        return $title;
    }

    protected function findSecondToLastSunday(string $date): string
    {
        $year = substr($date, -4);
        $list = json_decode(Storage::get('liturgy.json'), true)['content']['days'];
        $lastInTrinityPeriod = '';
        foreach ($list as $item) {
            if (substr($item['date'], -4) == $year) {
                if (Str::contains($item['title'], 'n. Trinitatis')) $lastInTrinityPeriod = $item['title'];
            }
        }

        return $this->translateTitle((((int)Str::before($lastInTrinityPeriod, '.'))+1).'. So. n. Trinitatis');
    }

    function getLinks(array $data): array
    {
        $title = $this->translateTitle($data['title']);
        if ($title == 'Vorletzter Sonntag d. Kj.') {
            $title = $this->findSecondToLastSunday($data['date']);
        }
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$title] ?? []);
        return $data;
    }

}
