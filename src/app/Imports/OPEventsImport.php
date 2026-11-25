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
 * Date: 22.11.2019
 * Time: 12:05
 */

namespace App\Imports;

use App\Models\Places\City;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

/**
 * Class OPEventsImport
 */
class OPEventsImport
{
    /** @var City city */
    protected $city = null;

    /**
     * OPEventsImport constructor.
     */
    public function __construct(City $city)
    {
        $this->city = $city;
    }

    /**
     * Merge events from the online-planned backend into an existing event list.
     *
     * @param  array<array-key, mixed>  $events  Events grouped by their start timestamp.
     * @param  Carbon  $start  First day of the period to include.
     * @param  Carbon  $end  Last day of the period to include.
     * @param  bool  $removeMatching  Whether existing events with a matching title are removed.
     * @return array<array-key, mixed> Events grouped by their start timestamp.
     */
    public function mix(array $events, Carbon $start, Carbon $end, bool $removeMatching = false): array
    {
        if (empty($this->city->op_customer_token.$this->city->op_customer_key)) {
            return $events;
        }

        $myEvents = $this->getEvents();
        if (! is_array($myEvents)) {
            return $events;
        }
        foreach ($myEvents as $myEvent) {
            if (! is_array($myEvent)) {
                continue;
            }
            $myEvent['record_type'] = 'OP_Event';
            $this->fixTimeAndDates($myEvent);
            $eventStart = $myEvent['start'] ?? null;
            if (! $eventStart instanceof Carbon) {
                continue;
            }
            if (($eventStart <= $end && ((! isset($myEvent['end']) || ($myEvent['end'] >= $eventStart))))) {
                if (isset($myEvent['event_dates']) && is_array($myEvent['event_dates'])) {
                    foreach ($myEvent['event_dates'] as $key => $date) {
                        if (! is_array($myEvent['event_dates'][$key])) {
                            continue;
                        }
                        $this->fixTimeAndDates($myEvent['event_dates'][$key]);
                    }
                }
                $locationTitle = $myEvent['locationtitle'] ?? '';
                $locationLocation = $myEvent['locationlocation'] ?? null;
                $myEvent['place'] = (is_scalar($locationTitle) ? (string) $locationTitle : '')
                    .(is_scalar($locationLocation) && $locationLocation ? ', '.(string) $locationLocation : '');

                // check for existing events
                $startKey = $eventStart->format('YmdHis');
                $existingGroup = $events[$startKey] ?? [];
                if (! is_array($existingGroup)) {
                    $existingGroup = [];
                }
                if ($removeMatching) {
                    $matches = [];
                    foreach ($existingGroup as $matchKey => $existingEvent) {
                        if (is_array($existingEvent) && ($existingEvent['title'] ?? null) == $myEvent['title']) {
                            $matches[] = $matchKey;
                        }
                    }
                    foreach ($matches as $match) {
                        unset($existingGroup[$match]);
                    }
                }
                $existingGroup[] = $myEvent;
                $events[$startKey] = $existingGroup;
            }
        }

        ksort($events);

        return $events;
    }

    /**
     * Load the events from the online-planned backend or the cache.
     *
     * @return mixed Events grouped by their start timestamp.
     */
    public function getEvents()
    {
        if (empty($this->city->op_customer_token.$this->city->op_customer_key)) {
            return [];
        }
        $url = 'https://backend.online-geplant.de/public/event/'.$this->city->op_customer_token.'/'.$this->city->op_customer_key;
        $cacheKey = 'OPEventsImport_'.$url;
        if (Cache::has($cacheKey)) {
            $events = Cache::get($cacheKey);
        } else {
            $events = $this->getUrl($url);
            Cache::put($cacheKey, $events, 900);
        }

        return $events;
    }

    /**
     * Fetch the event data from the given backend URL.
     *
     * The request is currently disabled, so an empty list is returned.
     *
     * @param  string  $url  Backend URL to request.
     * @return array<array-key, mixed> Fetched event data, currently always empty.
     */
    public function getUrl(string $url): array
    {
        // temporarily disabled:
        return [];
    }

    /**
     * Normalize the start and end timestamps of an event.
     *
     * @param  array<array-key, mixed>  $event  Event data to normalize in place.
     *
     * @throws Exception
     */
    protected function fixTimeAndDates(array &$event): void
    {
        /** @var string|int|float|\DateTimeInterface|null $startDate */
        $startDate = $event['startdate'] ?? null;
        $event['start'] = new Carbon($startDate, 'Europe/Berlin');
        $timeStart = $event['timestart'] ?? null;
        if (is_scalar($timeStart)) {
            $event['start']->setTimeFromTimeString((string) $timeStart);
        }
        $endDate = $event['enddate'] ?? null;
        if ($endDate && is_scalar($endDate)) {
            $event['end'] = new Carbon((string) $endDate, 'Europe/Berlin');
        }
        $timeEnd = $event['timeend'] ?? null;
        if (is_scalar($timeEnd) && isset($event['end']) && $event['end'] instanceof Carbon) {
            $event['end']->setTimeFromTimeString((string) $timeEnd);
        } else {
            unset($event['end']);
        }
        if (isset($event['end']) && ($event['end'] <= $event['start'])) {
            $event['end'] = $event['start']->copy()->addHour();
        }
    }
}
