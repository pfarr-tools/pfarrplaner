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

namespace App\Console\Commands\Liturgy;

use App\Models\LiturgyInfo;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Storage;

class GetLiturgyInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'liturgy:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get liturgical calendar from kirchenjahr-evangelisch.de';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->getOutput()->section('Verzeichnisse lesen');

        $this->processItem('Liturgische Informationen (kirchenjahr-evangelisch.de)', function() {
            Storage::put(
                'liturgy.json',
                file_get_contents(
                    'https://www.kirchenjahr-evangelisch.de/service.php?o=lcf&f=gaa&r=json&dl=user'
                )
            );
        });

        $efp = $this->processItem('Exegese für die Predigt (bibelwissenschaft.de)', [$this, 'getEFPLinks']);


        $this->getOutput()->section('Liturgiedatenbank aktualisieren');
        $ctr = 0;
        $list = json_decode(Storage::get('liturgy.json'), true)['content']['days'];
        foreach ($list as $id => $data) {

            $formattedDate = $data['date'];

            $data['links'] = [];
            if (isset($efp[$formattedDate])) {
                $data['links']['[Exegese für die Predigt] '.$efp[$formattedDate]['date'].': '.$efp[$formattedDate]['ref'].' (bibelwissenschaft.de)'] = $efp[$formattedDate]['url'];
            }
            $data['links'] = $this->processItem('Suche nach Predigthilfe für '.$data['title'], function() use ($data) {
                return array_merge($data['links'], $this->getPMWueLinks($this->getDaySlug($data)));
            });

            ksort($data['links']);

            $data['date'] = $data['dateSql'];
            unset($data['dateSql']);
            $data['id'] = $id;
            $data['currentPerikope'] = $data['litTextsPerikope' . $data['perikope']];
            $data['currentPerikopeLink'] = $data['litTextsPerikope' . $data['perikope'] . 'Link'];
            if (!LiturgyInfo::where('id', $id)->where('date', $data['date'])->count()) {
                $ctr++;
                $this->processItem('Liturgie anlegen für '.$formattedDate, function() use ($data) {
                    LiturgyInfo::create($data);
                });
            } else {
                $this->processItem('Liturgie aktualisieren für '.$formattedDate, function() use ($data, $id) {
                    $litInfo = LiturgyInfo::where('id', $id)->where('date', $data['date'])->first();
                    $data['links'] = array_merge($litInfo->links ?? [], $data['links']);
                    ksort($data['links']);
                    $litInfo->update($data);
                });
            }
        }

        $this->newLine(2);
        $this->line('Der liturgische Kalender wurde aktualisiert.');
        if ($ctr) {
            $this->line($ctr . ' neue Einträge wurden hinzugefügt.');
        }
    }

    protected function getEFPLinks(): array
    {
        $client = new Client();
        try {
            $result = $client->get('https://www.bibelwissenschaft.de/efp');
        } catch (\Exception $e) {
            return [];
        }
        if (!$result->getStatusCode() == 200) {
            return [];
        }

        $table = preg_match('/<table>(.*)<\/table>/m', $result->getBody()->getContents(), $matches);
        $doc = new \DOMDocument();
        $doc->loadHTML(utf8_decode($matches[0]));

        $records = [];
        foreach ($doc->getElementsByTagName('tr') as $tr) {
            $colIdx = 0;
            $record = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                switch ($colIdx) {
                    case 0:
                        $record['url'] = 'https://www.bibelwissenschaft.de' . $td->childNodes[0]->getAttribute('href');
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
                $records[$record['date']] = $record;
            }
        }
        return $records;
    }

    protected function getDaySlug($data): string
    {
        if (!isset($data['title'])) {
            return '';
        }
        $title = Str::contains($data['title'], '(') ? trim(Str::before($data['title'], '(')) : trim($data['title']);
        $title = Str::replace('So.', 'Sonntag', $title);
        $title = Str::replace('. Advent', '. Sonntag im Advent', $title);
        return Str::slug($title);
    }

    protected function getPMWueLinks($slug)
    {
        if (Cache::has('pmwue-'.$slug)) {
            return Cache::get('pmwue-'.$slug);
        } else {
            $client = new Client();
            try {
                $result = $client->get(
                    'https://www.fachstelle-gottesdienst.de/predigt/predigtmeditationen-aus-wuerttemberg/' . $slug
                );
            } catch (\Exception $e) {
                return [];
            }
            if (!$result->getStatusCode() == 200) {
                return [];
            }
            $links = [];

            $doc = new \DOMDocument();
            $doc->loadHTML(
                utf8_decode(Str::between($result->getBody()->getContents(), '<!--TYPO3SEARCH_begin-->', '<!--TYPO3SEARCH_end-->'))
            );
            $finder = new \DomXPath($doc);
            $sections = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' section ')]");
            $sectionIdx = 0;
            foreach ($sections as $section) {
                if ($sectionIdx > 0) {
                    foreach ($section->getElementsByTagName('h2') as $h2) {
                        $sectionTitle = (trim($h2->nodeValue));
                    }
                    foreach ($section->getElementsByTagName('a') as $link) {
                        $url = $link->getAttribute('href');
                        if (!Str::startsWith($url, 'http')) $url = 'https://www.fachstelle-gottesdienst.de/'.$url;
                        $links['[' . $sectionTitle . '] ' . trim($link->nodeValue)] = $url;
                    }
                }
                $sectionIdx++;
            }
            Cache::put('pmwue-'.$slug, $links);
            return $links;
        }
    }

    protected function processItem($title, $callback) {
        $this->getOutput()->write(Str::padRight($title, 75));
        $result = $callback();
        $this->getOutput()->writeln('[<info>OK</info>]');
        return $result;
    }
}
