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

class EFPStudyHelper extends AbstractStudyHelper
{
    public $title = 'Exegese für die Predigt';

    /** @var array<string, array<string, string>> */
    protected $records = [];

    /**
     * @param  array<string, mixed>  $data  Study helper input data.\n     * @return array<string, array<string, string>>
     */
    public function read(array $data): array
    {
        if ('' == ($content = $this->getContent('https://www.die-bibel.de/efp'))) {
            return [];
        }

        $content = preg_replace(
            '~</?\s*ibep-[a-z0-9-]+(?:\s+[^<>]*?)?\s*/?>~i',
            '',
            $content
        ) ?? $content;
        $content = preg_replace(
            '~<svg\b[^>]*>.*?</svg>~is',
            '',
            $content
        ) ?? $content;

        $table = preg_match('/<table(.*)<\/table>/m', $content, $matches);
        if ($table !== 1 || ! isset($matches[0])) {
            return [];
        }
        $doc = new \DOMDocument;
        try {
            $loaded = $doc->loadHTML($matches[0], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } catch (\Throwable $th) {
            return [];
        }
        if (! $loaded) {
            return [];
        }

        $records = [];
        foreach ($doc->getElementsByTagName('tr') as $tr) {
            $colIdx = 0;
            $record = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                switch ($colIdx) {
                    case 0:
                        $href = $td->firstChild?->attributes?->getNamedItem('href')?->nodeValue;
                        if (is_string($href)) {
                            $record['url'] = 'https://www.die-bibel.de'.$href;
                        }
                        break;
                    case 1:
                        $record['date'] = (string) $td->nodeValue;
                        break;
                    case 2:
                        $record['ref'] = (string) $td->nodeValue;
                        break;
                }
                $colIdx++;
            }
            if (isset($record['date'], $record['ref'], $record['url'])) {
                /** @var string $date Datum des Eintrags. */
                $date = $record['date'];
                /** @var string $reference Bibelreferenz des Eintrags. */
                $reference = $record['ref'];
                /** @var non-falsy-string $url URL des Eintrags. */
                $url = $record['url'];
                $records[$date] = ['[Exegese für die Predigt] '.$date.': '.$reference => $url];
            }
        }
        $this->records = $records;

        return $records;
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
