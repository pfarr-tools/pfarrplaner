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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 01.11.2019
 * Time: 11:58
 */

namespace App\Imports;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use ErrorException;
use Illuminate\Support\Facades\Cache;

/**
 * Class EventCalendarImport
 */
class EventCalendarImport
{
    /**
     * @var string
     */
    public $timeZone = 'UTC';

    /**
     * @var string
     */
    protected $url = '';

    /**
     * Initialisiert den Import mit der URL des öffentlichen Veranstaltungskalenders.
     *
     * @param  string|null  $url  URL des Kalenders; ein leerer Wert deaktiviert den Import.
     */
    public function __construct(?string $url)
    {
        $this->url = $url ?? '';
    }

    /**
     * Platzhalter für das Zusammenführen gemischter Veranstaltungen.
     *
     * Die Methode ist derzeit leer und liefert kein Ergebnis.
     */
    public function getMixedEvents(): void {}

    /**
     * Fügt die Veranstaltungen des Kalenders in eine bestehende Veranstaltungsmap ein.
     *
     * @param  array<string, mixed>  $events  Veranstaltungsmap, die erweitert wird.
     * @param  Carbon  $weekStart  Beginn des zu berücksichtigenden Zeitraums.
     * @param  Carbon  $weekEnd  Ende des zu berücksichtigenden Zeitraums.
     * @param  bool  $removeServices  Gottesdienste aus dem Ergebnis entfernen.
     * @return array<string, mixed> Veranstaltungsmap nach dem Zusammenführen.
     */
    public function mix($events, Carbon $weekStart, Carbon $weekEnd, $removeServices = false): array
    {
        if (empty($this->url)) {
            return $events;
        }

        $theseEvents = $this->getEvents($weekStart, $weekEnd, $removeServices);
        foreach ($theseEvents as $dateCode => $subEvents) {
            foreach ($subEvents as $event) {
                $events[$dateCode][] = $event;
            }
        }

        ksort($events);

        return $events;
    }

    /**
     * Liest die Veranstaltungen des Kalenders für den Zeitraum aus Cache oder URL.
     *
     * @param  Carbon  $weekStart  Beginn des zu berücksichtigenden Zeitraums.
     * @param  Carbon  $weekEnd  Ende des zu berücksichtigenden Zeitraums.
     * @param  bool  $removeServices  Gottesdienste aus dem Ergebnis entfernen.
     * @return array<string, mixed> Nach Datum sortierte Veranstaltungsmap.
     */
    public function getEvents(Carbon $weekStart, Carbon $weekEnd, $removeServices = false): array
    {
        if (empty($this->url)) {
            return [];
        }

        $cacheKey = 'EventCalendarImport_'.$this->url;
        if (Cache::has($cacheKey)) {
            $events = Cache::get($cacheKey);
        } else {
            try {
                $events = json_decode((string) file_get_contents($this->url), true);
                Cache::put($cacheKey, $events, 900);
            } catch (ErrorException $e) {
                $events = [];
            }
        }

        $filteredEvents = [];
        if (! is_array($events)) {
            return [];
        }
        foreach ($events as $key => $event) {
            $event['record_type'] = 'Outlook_Event';
            $event['start'] = $this->sanitizeTimeString($event['start']);
            $event['end'] = $this->sanitizeTimeString($event['end']);

            // sanitize title
            $lines = explode("\r\n", $event['title']);
            $event['title'] = $this->sanitizeTitle($lines[0], $event);
            if (isset($lines[1])) {
                if (substr(trim($lines[1]), 0, 4) == 'Ort:') {
                    $event['place'] = $this->sanitizeLocation($lines[1]);
                }
            }

            if (! isset($event['place'])) {
                $event['place'] = '';
            }

            if (($event['start'] >= $weekStart) and ($event['start'] <= $weekEnd)) {
                if (! ($event['allDay'] && ($event['place'] == ''))) {
                    if ((! $removeServices) || ((strtoupper(substr($event['title'], 0, 2)) != 'GD')
                            && (strtoupper(substr($event['title'], 0, 13)) != 'GOTTESDIENST ')
                            && (strtoupper($event['title']) != 'GOTTESDIENST'))) {
                        $filteredEvents[$event['start']->format('YmdHis')][] = $event;
                    }
                }
            }
        }
        ksort($filteredEvents);

        return $filteredEvents;
    }

    /**
     * Wandelt die Zeitangabe in ein DateTime-Objekt um und korrigiert die Zeitzone
     */
    protected function sanitizeTimeString(string $time): DateTime
    {
        return Carbon::createFromTimeString(substr($time, 0, -1), new DateTimeZone($this->timeZone));
    }

    /**
     * Formatiert den Titel einer Veranstaltung und übernimmt Zusatzangaben in das Veranstaltungsarray.
     *
     * @param  string  $title  Zu bereinigender Titel.
     * @param  array<string, mixed>  $event  Veranstaltungsarray, das um Zusatzangaben ergänzt wird.
     * @return string Bereinigter Titel.
     */
    protected function sanitizeTitle(string $title, array &$event): string
    {
        $title = trim($title);
        // $title = strtr($title, $this->config['events']['sanitize']['title']);
        foreach (['P', 'O', 'M', 'L'] as $function) {
            $regex = '/'.$function.':\s?(\w*)/';
            preg_match($regex, $title, $tmp);
            if (count($tmp)) {
                $event[$function] = $tmp[1];
                $title = str_replace($tmp[0], '', $title);
            }
        }

        while (strpos($title, '  ') !== false) {
            $title = str_replace('  ', ' ', $title);
        }

        return $title;
    }

    /**
     * Formatiert die Ortsangabe einer Veranstaltung
     */
    protected function sanitizeLocation(string $location): string
    {
        $location = trim(str_replace('Ort:', '', $location));

        return $location;
    }
}
