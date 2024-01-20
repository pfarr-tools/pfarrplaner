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

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class CalwerPredigtenStudyHelper extends AbstractStudyHelper
{

    public $title = 'Calwer Predigten';

    protected $records = [];

    function read(): void
    {
        for ($i=0; $i<=90; $i+=10) {
            $this->records = array_merge($this->records ?? [], $this->readSinglePage('https://www.calwer-stiftung.com/calwer-predigten-online.365730.202264.htm?id=365730&md=202264&skip='.$i));
        }
    }

    protected function readSinglePage($url) {
        if ('' == ($content = $this->getContent($url))) return [];
        $content = utf8_decode(Str::replace('–', '-', Str::betweenFirst($content, '<ul id="ajax_list_li" class="sermonlist resultlist">', '</ul>')));
        $doc = new \DOMDocument();
        $doc->loadHTML($content);


        $records = [];
        /** @var \DOMNode $li */
        foreach($doc->getElementsByTagName('li') as $li) {
            foreach ($li->getElementsByTagName('div') as $div) {
                if ($div->getAttribute('class') == 'list-headline') {
                    $recordTitle = Str::replace("\n", '', $div->nodeValue);
                    $recordTitle = Str::replace("(", ', ', $recordTitle);
                    $recordTitle = Str::replace(")", '', $recordTitle);
                    while (Str::contains($recordTitle, '  ')) $recordTitle = Str::replace('  ', ' ', $recordTitle);
                    $recordTitle = trim(Str::replace(' ,', ',', $recordTitle));
                    $recordDate = substr($recordTitle, 0, 10);
                    $recordTitle = '[Calwer Predigten] '.$recordTitle;
                }

                if ($div->getAttribute('class') == 'list-content') {
                    foreach ($div->getElementsByTagName('a') as $a) {
                        $recordUrl = $a->getAttribute('href');
                    }
                }
            }
            $records[$recordDate] = [$recordTitle => $recordUrl];
        }
        return $records;
    }

    function getLinks(array $data): array
    {
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$data['date']] ?? []);
        return $data;
    }
}
