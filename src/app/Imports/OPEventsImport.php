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
        foreach ($myEvents as $myEvent) {
            $myEvent['record_type'] = 'OP_Event';
            $this->fixTimeAndDates($myEvent);
            if (($myEvent['start']) <= $end && ((! isset($myEvent['end']) || ($myEvent['end'] >= $start)))) {
                foreach ($myEvent['event_dates'] as $key => $date) {
                    $this->fixTimeAndDates($myEvent['event_dates'][$key]);
                }
                $myEvent['place'] = $myEvent['locationtitle'].($myEvent['locationlocation'] ? ', '.$myEvent['locationlocation'] : '');

                // check for existing events
                if ($removeMatching) {
                    $matches = [];
                    if (isset($events[$myEvent['start']->format('YmdHis')])) {
                        foreach ($events[$myEvent['start']->format('YmdHis')] as $matchKey => $existingEvent) {
                            if ($existingEvent['title'] == $myEvent['title']) {
                                $matches[] = $matchKey;
                            }
                        }
                        foreach ($matches as $match) {
                            unset($events[$myEvent['start']->format('YmdHis')][$match]);
                        }
                    }
                }
                $events[$myEvent['start']->format('YmdHis')][] = $myEvent;
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
     * @return void
     *
     * @throws Exception
     */
    protected function fixTimeAndDates(array &$event): void
    {
        $event['start'] = new Carbon($event['startdate'], 'Europe/Berlin');
        if ($event['timestart'] !== null) {
            $event['start']->setTimeFromTimeString($event['timestart']);
        }
        if ($event['enddate']) {
            $event['end'] = new Carbon($event['enddate'], 'Europe/Berlin');
        }
        if ($event['timeend'] !== null) {
            $event['end']->setTimeFromTimeString($event['timeend']);
        } else {
            unset($event['end']);
        }
        if (isset($event['end']) && ($event['end'] <= $event['start'])) {
            $event['end'] = $event['start']->copy()->addHour();
        }
    }
}
