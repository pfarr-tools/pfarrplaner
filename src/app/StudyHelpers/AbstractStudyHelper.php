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

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

abstract class AbstractStudyHelper
{
    /** @var string */
    public $title = '';

    /** @var Client */
    protected $client;

    /** @var Command */
    protected $command = null;

    /**
     * @param  Command  $command  Console command used for output.
     * @return void
     */
    public function __construct(Command $command)
    {
        $this->client = new Client;
        $this->command = $command;
    }

    /**
     * Lädt den Inhalt der angegebenen URL.
     *
     * @param  string  $url  URL des abzurufenden Inhalts
     * @return string Geladener Inhalt oder ein leerer String bei Fehlern
     */
    protected function getContent(string $url): string
    {
        try {
            $result = $this->client->get($url);
        } catch (\Exception $exception) {
            return '';
        }
        if ($result->getStatusCode() != 200) {
            return '';
        }

        return $result->getBody()->getContents();
    }

    /**
     * Gibt den Schlüssel dieses StudyHelpers zurück.
     */
    public function getKey(): string
    {
        return Str::replace('StudyHelper', '', Str::afterLast(static::class, '\\'));
    }

    /**
     * Ergänzt die Predigtlinks für alle gelesenen Daten.
     *
     * @param  array<array-key, mixed>  $data  Zu ergänzende Daten
     * @return array<array-key, mixed> Ergänzte Daten
     */
    public function assign(array $data): array
    {
        $studyData = $this->read($data);
        foreach ($studyData as $date => $record) {
            $date = Carbon::createFromFormat('d.m.Y', $date);
            if ($date === null) {
                continue;
            }
            if (isset($data[$date->year]['Tage'][$date->format('Y-m-d')])) {
                foreach ($data[$date->year]['Tage'][$date->format('Y-m-d')] as $key => $tmp) {
                    $data[$date->year]['Tage'][$date->format('Y-m-d')][$key]['Links'] = array_merge($data[$date->year]['Tage'][$date->format('Y-m-d')][$key]['Links'] ?? [], $record);
                }
            }
        }

        return $data;
    }

    /**
     * Liest die Predigtlinks aus der jeweiligen Quelle.
     *
     * @param  array<string, mixed>  $data  Anfrageparameter
     * @return array<string, array<string, string>> Predigtlinks, gruppiert nach Datum
     */
    abstract public function read(array $data): array;

    /**
     * Ergänzt die Predigtlinks für das angeforderte Datum.
     *
     * @param  array<string, mixed>  $data  Zu ergänzende Daten
     * @return array<string, mixed> Ergänzte Daten
     */
    abstract public function getLinks(array $data): array;
}
