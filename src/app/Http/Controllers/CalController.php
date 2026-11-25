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

namespace App\Http\Controllers;

use App\Calendars\LocalEventCalendars\LocalEventCalendarFactory;
use App\Models\Calendar\Day;
use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\CalendarService;
use App\Services\RedirectorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Controller for the calendar views. */
class CalController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Render the calendar page for a single day.
     *
     * @param  string  $day  Day to display in Y-m-d format.
     * @param  City  $city  City whose services and absences are shown.
     * @return Response Inertia response for the single-day calendar.
     */
    public function singleDay(string $day, City $city): Response
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $day = Carbon::parse($day)->setTime(0, 0, 0);
        $services = Service::setEagerLoads([])
            ->with(['day', 'baptisms', 'funerals', 'weddings', 'participants'])
            ->whereDate('date', $day)
            ->inCities([$city->id])
            ->orderBy('time')
            ->get();

        $canCreate = $user->can('create', Service::class);

        // absences
        $absences = Absence::with('user')->byPeriod($day, $day)
            ->visibleForUser($user)
            ->get();

        return Inertia::render('Calendar/SingleDay', compact('day', 'city', 'services', 'absences', 'canCreate'));

    }

    /**
     * Render the monthly calendar overview.
     *
     * @param  Request  $request  Current request.
     * @param  string|null  $date  Year or year-month selector from the route.
     * @param  string|null  $month  Month from the route, appended to the date when present.
     * @return Response Inertia response for the calendar overview.
     */
    public function index(Request $request, ?string $date = null, ?string $month = null): Response
    {
        RedirectorService::saveCurrentRoute();
        if ($month) {
            $date .= '-'.$month;
        }
        $date = ($date ? new Carbon($date.'-01') : Carbon::now())->setTime(0, 0, 0);
        if ($date->format('Ym') < 201801) {
            abort(404);
        }
        $monthEnd = $date->copy()->addMonth()->subSecond();

        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $yearExpression = DB::connection()->getDriverName() == 'sqlite'
            ? "strftime('%Y', services.date)"
            : 'YEAR(DATE(services.date))';
        $years = Service::select(DB::raw('DISTINCT '.$yearExpression.' as year'))
            ->inCities($user->visibleCities)
            ->orderBy('year', 'asc')
            ->get()->pluck('year');

        $writableCities = $user->writableCities;
        /** @var Collection<int, City> $sortedCities */
        $sortedCities = $user->getSortedCities();
        $cities = array_values($sortedCities->map(function (City $city) {
            $city->setAttribute('childIds', $city->is_org ? $city->children->pluck('id')->toArray() : []);

            return $city;
        })->all());

        $canCreate = $user->can('create', Service::class);
        $calendars = LocalEventCalendarFactory::list();
        $initialCalendarData = CalendarService::buildMonthPayload($date->copy(), $user);

        return Inertia::render(
            'Calendar/Calendar',
            compact('date', 'cities', 'years', 'canCreate', 'writableCities', 'calendars', 'initialCalendarData')
        );
    }

    /**
     * Return the services of a calendar day as JSON.
     *
     * @param  Day  $day  Day whose services are returned.
     * @param  City  $city  City whose services are returned.
     * @return JsonResponse JSON response containing the services.
     */
    public function day(Day $day, City $city): JsonResponse
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
