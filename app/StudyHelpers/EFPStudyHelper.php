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

class EFPStudyHelper extends AbstractStudyHelper
{
    public $title = 'Exegese für die Predigt';

    protected $records = [];


    function read(array $data): array
    {
        if ('' == ($content = $this->getContent('https://www.die-bibel.de/efp'))) return [];

        $content = preg_replace(
            '~</?\s*ibep-[a-z0-9-]+(?:\s+[^<>]*?)?\s*/?>~i',
            '',
            $content
        );
        $content = preg_replace(
            '~<svg\b[^>]*>.*?</svg>~is',
            '',
            $content
        );

        $table = preg_match('/<table(.*)<\/table>/m', $content, $matches);
        try {
            $doc = new \DOMDocument();
            $doc->loadHTML('$matches[0]', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } catch (\Throwable $th) {}

        $records = [];
        foreach ($doc->getElementsByTagName('tr') as $tr) {
            $colIdx = 0;
            $record = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                switch ($colIdx) {
                    case 0:
                        $record['url'] = 'https://www.die-bibel.de' . $td->childNodes[0]->getAttribute('href');
                        break;
                    case 1:
                        $record['date'] = $td->nodeValue;
                        break;
                    case 2:
                        $record['ref'] = $td->nodeValue;
                        break;
                }
                $colIdx++;
            }
            if ($record['date']) {
                $records[$record['date']] = ['[Exegese für die Predigt] '.$record['date'].': '. $record['ref'] => $record['url']];
            }
        }
        $this->records = $records;
        return $records;
    }

    function getLinks(array $data): array
    {
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$data['date']] ?? []);
        return $data;
    }
}
