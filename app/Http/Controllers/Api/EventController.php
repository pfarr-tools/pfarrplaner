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

namespace App\Http\Controllers\Api;

use App\Calendars\LocalEventCalendars\LocalEventCalendarFactory;
use App\Http\Resources\OccurenceResource;
use App\Models\Calendar\Occurence;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController
{

    public function byRange(Request $request, $calendar, $start, $end)
    {
        $start = Carbon::parse(Str::beforeLast($start, '('));
        $end = Carbon::parse(Str::beforeLast($end, '('));

        $calendars = explode(',', $calendar);

        $occurences = Occurence::with('event')
            ->between($start, $end)
            ->whereHas('service', function ($query) use ($calendars) {
                $ct = 0;
                foreach ($calendars as $calendarReference) {
                    $calendar = LocalEventCalendarFactory::get($calendarReference);
                    if ($ct == 0) {
                        $query->where(function ($q) use ($calendar) {
                            $q = $calendar->adjustQuery($q);
                        });
                    } else {
                        $query->orWhere(function ($q) use ($calendar) {
                            $q = $calendar->adjustQuery($q);
                        });
                    }
                    $ct++;
                }
            })
            ->orderBy('start')
            ->get();

        return OccurenceResource::collection($occurences);
    }

}
