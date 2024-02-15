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

use App\Http\Resources\CalendarAbsenceResource;
use App\Http\Resources\CalendarDayCollectionResource;
use App\Http\Resources\CalendarDayResource;
use App\Http\Resources\CalendarServiceResource;
use App\Models\Leave\Absence;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\CalendarService;
use App\Services\LiturgyService;
use App\Services\RedirectorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarController extends \App\Http\Controllers\Controller
{

    public function __construct()
    {
        $this->middleware('auth:api')->except('navigate');
    }

    /**
     * Provide basic calendar data for a given date
     * @param $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function month($date)
    {
        $date = Carbon::parse($date . '-01 0:00:00');

        $returnRoute = route('calendar', $date->format('Y-m'));
        RedirectorService::setReturnRoute(route('calendar', $date->format('Y-m')));
        $returnRoute = RedirectorService::backRoute();

        $dates = Service::setEagerLoads([])->with([])
            ->select(DB::raw('DISTINCT DATE(services.date) as day'))
            ->inCities(Auth::user()->visibleCities)
            ->inMonthByDate($date)
            ->orderBy('day', 'ASC')
            ->get()->pluck('day');

        $dates = CalendarService::addMissingDefaultDays($date, $dates);

        return new CalendarDayCollectionResource($dates);
    }

    public function service(Service $service)
    {
        return new CalendarServiceResource($service);
    }


    /**
     * Get services for a specific city and month
     * @param City $city
     * @param string $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function city(City $city, $date)
    {
        $date = Carbon::parse($date . '-01 0:00:00');
        if ($date->format('Ym') < 201801) {
            abort(404);
        }

        $services = Service::setEagerLoads([])
            ->with(['baptisms', 'funerals', 'weddings', 'participants'])
            ->inMonthByDate($date)
            ->inCities([$city->id])
            ->ordered()
            ->get()
            ->groupBy('keyDate');

        return response()->json($services);
    }

    /**
     * Get services for the quick-pick calendar
     * @param Carbon $date
     * @return JsonResponse
     */
    public function quickPick($date)
    {
        $date = Carbon::createFromFormat('d.m.Y', $date)->setTime(0,0,0);
        $services = Service::setEagerLoads([])->without(['city', 'location', 'participants'])
            ->select(['id', 'slug', 'title', 'date', 'location_id', 'city_id', 'special_location'])
            ->whereIn('city_id', Auth::user()->writableCities->pluck('id'))
            ->startingFrom($date)->endingAt($date->copy()->endOfDay())->ordered()
            ->get();
        return response()->json($services ?? []);
    }


}
