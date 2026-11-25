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

use App\Models\Calendar\Day;
use App\Models\Leave\Absence;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Service;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * Class CalendarController
 */
class CalendarController extends Controller
{
    public const NAME_FORMAT_DEFAULT = 1;

    public const NAME_FORMAT_INITIAL_AND_LAST = 2;

    public const NAME_FORMAT_FIRST_AND_LAST = 3;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param  int|string|null  $year  Year or a year-month combination such as "2026-11".
     * @param  int|string|null  $month  Month to display.
     * @return bool|Application|Factory|RedirectResponse|View
     */
    public function month(Request $request, $year = null, $month = null)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        if (! is_null($year) && is_null($month)) {
            [$year, $month] = array_map('intval', explode('-', (string) $year));
        }

        $month ??= $user->getSetting('display-month') ?: date('m');
        $year ??= $user->getSetting('display-year') ?: date('Y');

        /** @var int|string $yearValue */
        $yearValue = $year;
        /** @var int|string $monthValue */
        $monthValue = $month;
        $year = (int) $yearValue;
        $month = (int) $monthValue;

        $monthStart = Carbon::create($year, $month, 1);
        abort_unless($monthStart instanceof Carbon, 500);
        $monthEnd = $monthStart->copy()->addMonth()->subSecond();

        if (! Session::has('showLimitedDays')) {
            Session::put('showLimitedDays', false);
        }

        // city sorting
        if ($request->has('sort')) {
            $user->setSetting('sorted_cities', $request->get('sort'));
        }
        /** @var Collection<int|string, mixed> $sortedCities */
        $sortedCities = $user->getSortedCities();
        $cities = $sortedCities;
        $unusedCities = $user->cities->whereNotIn('id', $sortedCities->pluck('id'));

        // filter days:
        $days = Day::inMonth($monthStart->copy())
            ->visibleForCities($cities)
            ->get();

        if (! count($days)) {
            $days = CalendarService::initializeMonth($year, $month);
        }

        $nextDay = Day::inMonth($monthStart->copy())
            ->whereBetween('date', [Carbon::now()->setTime(0, 0, 0), $monthEnd])
            ->first() ?? new Day(['date' => now()]);

        // name_format parameter
        $nameFormat = $request->has('name_format') ? $request->get('name_format') : $user->getSetting(
            'calendar_name_format',
            self::NAME_FORMAT_DEFAULT
        );
        $user->setSetting('calendar_name_format', $nameFormat);

        $yearExpression = DB::connection()->getDriverName() == 'sqlite'
            ? "strftime('%Y', days.date)"
            : 'YEAR(days.date)';
        $years = Day::select(DB::raw($yearExpression.' as year'))->orderBy('date')->get()->pluck('year')->unique()->sort();
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = strftime('%B', (int) mktime(0, 0, 0, $i, 1, (int) date('Y')));
        }

        // determine orientation and view name;
        $defaultOrientation = $user->getSetting(
            'calendar_view',
            'horizontal'
        ) == 'month_vertical' ? 'vertical' : 'horizontal';
        $orientation = $request->get('orientation', $defaultOrientation);
        $user->setSetting('calendar_view', $orientation);

        // all possible locations
        $possibleLocations = Location::inCities($user->cities->pluck('id'))->get();
        $filteredLocations = CalendarService::getLocationsFilter($request, $possibleLocations, $user);

        // services
        $serviceQuery = Service::with('location')
            ->inCities($cities->pluck('id'))
            ->orderBy('time');

        if (count($filteredLocations)) {
            $serviceQuery->whereIn('location_id', $filteredLocations);
        }

        $services = $serviceQuery->get()->groupBy(['city_id', 'day_id']);

        // absences
        $absences = Absence::byPeriod($monthStart, $monthEnd)
            ->visibleForUser($user)
            ->get();

        $slave = $request->get('slave', 0);
        $highlight = $request->get('highlight', 0);

        // save current display settings for slave displays to follow
        if (! $slave) {
            $currentDisplayMonth = $user->getSetting('display-month', 999);
            $currentDisplayYear = $user->getSetting('display-year', 999);
            if (($month != $currentDisplayMonth) || ($year != $currentDisplayYear)) {
                $user->setSetting('display-timestamp', time());
                $user->setSetting('display-month', $month);
                $user->setSetting('display-year', $year);
            }
        }

        return $this->renderView(
            'calendar.month',
            compact(
                'year',
                'month',
                'years',
                'months',
                'days',
                'cities',
                'services',
                'nextDay',
                'absences',
                'highlight',
                'slave',
                'orientation',
                'sortedCities',
                'unusedCities',
                'nameFormat',
                'possibleLocations',
                'filteredLocations'
            )
        );
    }

    /**
     * Redirect if the requested year or month is missing or invalid.
     *
     * @param  string  $route  Route name used for the redirect.
     * @param  int  $year  Requested year.
     * @param  int  $month  Requested month.
     * @return bool|RedirectResponse
     */
    protected function redirectIfMissingParameters(Request $request, $route, $year, $month)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $defaultMonth = $user->getSetting('display-month', date('m'));
        $defaultYear = $user->getSetting('display-year', date('Y'));

        $initialYear = $year;
        $initialMonth = $month;

        if ($month == 13) {
            $year++;
            $month = 1;
        }
        if (($year > 0) && ($month == 0)) {
            $year--;
            $month = 12;
        }

        if ((! $year) || (! $month) || (! is_numeric($month)) || (! is_numeric($year)) || (! checkdate($month, 1, $year))) {
            $year = $defaultYear;
            $month = $defaultMonth;
        }

        if (($year == $initialYear) && ($month == $initialMonth)) {
            return false;
        }

        $data = compact('month', 'year');
        $slave = $request->get('slave', 0);
        if ($slave) {
            $data = array_merge($data, compact('slave'));
        }

        return redirect()->route($route, $data);
    }

    /**
     * Render a Blade view whose name is provided as a plain string.
     *
     * @param  string  $view  Name of the view to render.
     * @param  array<string, mixed>  $data  Data passed to the view.
     * @return View Rendered month view.
     */
    private function renderView(string $view, array $data): View
    {
        /** @var view-string $view */
        return view($view, $data);
    }

    /**
     * @return false|string
     */
    public function showLimitedColumns(bool $switch)
    {
        Session::put('showLimitedDays', (bool) $switch);

        return json_encode(['showLimitedDays', Session::get('showLimitedDays')]);
    }
}
