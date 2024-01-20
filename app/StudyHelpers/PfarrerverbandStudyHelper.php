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
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class PfarrerverbandStudyHelper extends AbstractStudyHelper
{

    public $title = 'Predigtimpulse des Pfarrerverbands';

    protected $records = [];

    function read(): void
    {
        $pages = $this->getPageIndexFrom('https://www.pfarrerverband.de/pfarrerverand-predigtimpulse');
        if (!count($pages)) return;
        $pages = array_unique(array_merge($pages, $this->getPageIndexFrom($pages[0])));
        sort($pages);
        foreach ($pages as $url) {
            $this->records = array_merge($this->records ?? [], $this->getPageLinks($url));
        }
    }

    protected function getPageIndexFrom($url): array
    {
        $pages = [];
        if ('' == ($content = Str::between($this->getContent($url), '<ul class="f3-widget-paginator pagination">', '</ul>'))) return [];
        $doc = new \DOMDocument();
        $doc->loadHTML($content);

        foreach ($doc->getElementsByTagName('a') as $a) $pages[] = 'https://www.pfarrerverband.de'.$a->getAttribute('href');
        return array_unique($pages);
    }

    protected function getPageLinks($url): array {
        if ('' == ($content = $this->getContent($url))) return [];
        $content = Str::replace("\n", '', $content);
        //dd($url, $content);
        preg_match_all('/Item.html(?:.*?)<h2(?:.*?)<b>(.*?)<\/b>(?:.*?)<br>\s+(.*?)\s+<\/h2>(?:.*?)<p>(.*?), (.*?)<\/p>(?:.*?)<strong>(.*?)<\/strong>(?:.*?)von (.*?)<\/p>(?:.*?)href="(.*?)"/', $content, $matches);
        $records = [];
        foreach ($matches[0] as $index => $match) {
            $recordTitle = '[Pfarrerverband] "'.$matches[2][$index].'"'
                .(Str::contains($matches[4][$index], '-->') ? '' : ', '.$matches[4][$index])
                .' ('.trim($matches[6][$index]).')';
            $records[$this->translateDate($matches[3][$index])] = [
                $recordTitle => 'https://www.pfarrerverband.de'.$matches[7][$index],
            ];
        }
        return $records;
    }

    protected function translateDate($date) {
        $months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        if (Str::contains($date, '<strong>')) return Str::betweenFirst($date, '<strong>', '</strong>');
        foreach ($months as $index => $month) {
            $date = Str::replace(' '.$month.' ', Str::padLeft($index+1, 2, '0').'.', $date);
        }
        return Str::padLeft($date, 10, '0');
    }


    function getLinks(array $data): array
    {
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$data['date']] ?? []);
        return $data;
    }
}
