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

namespace App\Http\Controllers;

use App\Absence;
use App\City;
use App\Day;
use App\Liturgy;
use App\Ministry;
use App\Service;
use App\Services\CalendarService;
use App\Services\RedirectorService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function singleDay($day, City $city)
    {
        $day = Carbon::parse($day)->setTime(0,0,0);
        $services = Service::setEagerLoads([])
            ->with(['day', 'baptisms', 'funerals', 'weddings', 'participants'])
            ->whereDate('date', $day)
            ->inCities([$city->id])
            ->orderBy('time')
            ->get();

        $canCreate = Auth::user()->can('create', Service::class);

        // absences
        $absences = Absence::with('user')->byPeriod($day, $day)
                ->visibleForUser(Auth::user())
                ->get();

        return Inertia::render('Calendar/SingleDay', compact('day', 'city', 'services', 'absences', 'canCreate'));

    }

    public function index(Request $request, $date = null, $month = null)
    {
        RedirectorService::saveCurrentRoute();
        if ($month) {
            $date .= '-' . $month;
        }
        $date = ($date ? new Carbon ($date . '-01') : Carbon::now())->setTime(0, 0, 0);
        if ($date->format('Ym') < 201801) abort(404);
        $monthEnd = $date->copy()->addMonth(1)->subSecond(1);

        $dates = Service::select(DB::raw('DISTINCT DATE(services.date) as day'))
            ->inCities(Auth::user()->visibleCities)
            ->inMonthByDate($date)
            ->orderBy('day', 'ASC')
            ->get()->pluck('day');

        $dates = CalendarService::addMissingDefaultDays($date, $dates);
        $days = [];
        foreach ($dates as $thisDate) {
            $days[$thisDate] = ['date' => $thisDate, 'liturgy' => Liturgy::getDayInfo($thisDate)];
        }

        $years = Service::select(DB::raw('DISTINCT YEAR(DATE(services.date)) as year'))
            ->inCities(Auth::user()->visibleCities)
            ->orderBy('year', 'ASC')
            ->get()->pluck('year');


        $user = Auth::user();
        $cities = $user->cities;
        $writableCities = $user->writableCities;

        $services = [];
        foreach ($cities as $city) {
            foreach ($dates as $day) {
                $services[$city->id][$day] = [];
            }
        }

        $cities = array_values($user->getSortedCities()->all());

        // absences
        $absences = Absence::getByDays(
            Absence::with('user')->byPeriod($date, $monthEnd)
                ->visibleForUser(Auth::user())
                ->showInCalendar()
                ->get(),
            $dates
        );

        $canCreate = $user->can('create', Service::class);

        return Inertia::render(
            'Calendar/Calendar',
            compact('date', 'days', 'cities', 'years', 'absences', 'canCreate', 'services', 'writableCities')
        );
    }

    public function day(Day $day, City $city)
    {
        $services = Service::setEagerLoads([])
            ->with(['day', 'baptisms', 'funerals', 'weddings', 'participants'])
            ->where('day_id', $day->id)
            ->inCities([$city->id])
            ->orderBy('time')
            ->get();

        return response()->json($services);
    }
}
