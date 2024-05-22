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

namespace App\Calendars\LocalEventCalendars;

use App\Calendars\LocalEventCalendars\AbstractLocalEventCalendar;
use App\Models\Location;
use App\Models\Places\City;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LocationLocalEventCalendar extends AbstractLocalEventCalendar
{

    protected static $group = 'Veranstaltungsorte';

    public static function list(): array
    {
        $calendars = [];
        foreach (Location::whereIn('city_id', Auth::user()->cities->pluck('id'))->get() as $location) {
            $calendars[] = static::getEntry($location->id, $location->name);
        }
        return $calendars;
    }

    public function adjustQuery(Builder $query): Builder
    {
        return $query->where('location_id', $this->id);
    }

    public function presetData(array $data): array
    {
        $data = parent::presetData($data);

        $location = Location::find($this->id);
        if (!$location) return $data;
        $data['city_id'] = $location->city_id;
        $data['location_id'] = $location->id;

        $date = $data['date'] ?? Carbon::parse('next Sunday');
        if ($location->default_time) {
            $data['date'] ??= Carbon::parse($date->format('Y-m-d') . ' ' . $location->default_time, 'Europe/Berlin');
        } else {
            $data['date'] ??= Carbon::parse($date->format('Y-m-d') . ' 10:00:00', 'Europe/Berlin');
        }

        return $data;
    }




}
