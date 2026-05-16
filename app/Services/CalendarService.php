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

namespace App\Services;


use App\Http\Resources\Calendar\CalendarAbsenceResource;
use App\Http\Resources\Calendar\CalendarMonthServiceResource;
use App\Models\Leave\Absence;
use App\Models\Calendar\Day;
use App\Models\People\User;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarService
{

    /**
     * @param $year
     * @param $month
     * @return Collection
     */
    public static function initializeMonth($year, $month)
    {
        $days = collect();
        $today = Carbon::create($year, $month, 1, 0, 0, 0);
        while ($today->month == $month) {
            if ($today->dayOfWeek == 0) {
                $day = Day::create(
                    [
                        'date' => $today->format('d.m.Y'),
                        'name' => '',
                        'description' => '',
                    ]
                );
                $days->push($day);
            }
            $today->addDay(1);
        }
        return $days;
    }


    /**
     * Get the location filter either from request or from a user setting
     * @param Request $request
     * @param $possibleLocations
     * @param $user
     * @return array
     */
    public static function getLocationsFilter(Request $request, $possibleLocations, $user): array
    {
        if (!$user->hasSetting('calendar_filter_locations')) {
            $user->setSetting('calendar_filter_locations', '');
        }
        if ($request->has('filter_location')) {
            if ($request->get('filter_location') == '') {
                $filteredLocations = [];
            } else {
                $filteredLocations = (clone $possibleLocations)->filter(
                    function ($location) use ($request) {
                        return in_array($location->id, explode(',', $request->get('filter_location')));
                    }
                )->pluck('id')->toArray();
            }
            $user->setSetting('calendar_filter_locations', join(',', $filteredLocations));
        } else {
            if ('' === $user->getSetting('calendar_filter_locations', '')) {
                return [];
            }
            $filteredLocations = explode(',', $user->getSetting('calendar_filter_locations', []));
        }
        return $filteredLocations;
    }


    /**
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     * @throws Exception
     */
    public static function getHolidays(Carbon $start, Carbon $end)
    {
        try {
            $url = 'https://openholidaysapi.org/SchoolHolidays?countryIsoCode=DE&subdivisionCode=DE-'
                . config('calendar.vacation_state')
                . '&languageIsoCode=DE&validFrom='
                . $start->format('Y-m-d')
                . '&validTo='
                . $end->format('Y-m-d');

            $raw = json_decode(file_get_contents($url), true);
        } catch (\ErrorException $e) {
            return [];
        }
        $holidays = [];
        foreach ($raw as $holiday) {
            $holidays[] = [
                'start' => new Carbon($holiday['startDate']),
                'end' => (new Carbon($holiday['endDate']))->addDay(1)->subSecond(1),
                'name' => $holiday['name'][0]['text'],
            ];
        }
        return $holidays;
    }

    /**
     * Get the start of a year or month as Carbon object
     * @param Carbon|int $year Year
     * @param null|int $month Month
     * @return Carbon Start of year/month
     */
    public static function getStartOfPeriod($year, $month = null)
    {
        if (is_a($year, Carbon::class)) {
            return $year->setDay(1)->setTime(0, 0, 0);
        }
        if (!$month) {
            list($year, $month) = explode('-', $year);
        }
        return new Carbon($year . '-' . $month . '-01 0:00:00');
    }


    public static function addMissingDefaultDays($date, $days)
    {
        $currentDate = $date->copy()->firstOfMonth()->startOfDay();
        $litInfo = LiturgyService::getYear($currentDate->year);
        while ($currentDate <= $date->endOfMonth()) {
            if (isset($litInfo[$currentDate->format('Y-m-d')])) {
                $days->push($currentDate->format('Y-m-d'));
            }
            $currentDate->addDay(1);
        }
        return $days->unique()->sort();
    }

    /**
     * Build the compact month payload for the calendar view.
     *
     * @param Carbon $date
     * @param User $user
     * @return array<string, mixed>
     */
    public static function buildMonthPayload(Carbon $date, User $user): array
    {
        $monthStart = $date->copy()->firstOfMonth()->startOfDay();
        $monthEnd = $date->copy()->endOfMonth()->endOfDay();

        $dates = Service::setEagerLoads([])->with([])
            ->select(DB::raw('DISTINCT DATE(services.date) as day'))
            ->inCities($user->visibleCities)
            ->inMonthByDate($date)
            ->orderBy('day', 'ASC')
            ->get()->pluck('day');

        $dates = self::addMissingDefaultDays($date->copy(), $dates);
        $days = self::initializeMonthDayMap($dates, $date);

        $services = self::loadMonthServices($date, $user);
        self::addServicesToDays($days, $services);

        $absences = self::loadMonthAbsences($monthStart, $monthEnd, $user);
        self::addAbsencesToDays($days, $absences, $monthStart, $monthEnd);

        return [
            'data' => $days,
            'loadedDate' => $date->format('Y-m'),
            'returnRoute' => RedirectorService::backRoute(),
        ];
    }

    /**
     * Initialize the day map for a calendar month.
     *
     * @param Collection $dates
     * @param Carbon $date
     * @return array<string, array<string, mixed>>
     */
    protected static function initializeMonthDayMap(Collection $dates, Carbon $date): array
    {
        $days = [];
        $liturgyYear = LiturgyService::getYear($date->year);
        $liturgyDays = $liturgyYear['Tage'] ?? [];

        foreach ($dates as $rawDate) {
            $dayDate = Carbon::parse($rawDate)->startOfDay();
            $liturgy = $liturgyDays[$dayDate->format('Y-m-d')] ?? [];
            $primaryLiturgy = $liturgy[0] ?? [];
            $days[$dayDate->format('Y-m-d')] = [
                'date' => $dayDate->format('Y-m-d'),
                'liturgy' => isset($primaryLiturgy['Bezeichnung']) ? [
                    'title' => $primaryLiturgy['Bezeichnung'],
                    'litColor' => $primaryLiturgy['CSS-Farbe'],
                    'feastCircleName' => $primaryLiturgy['Festkreis'],
                    'perikope' => $primaryLiturgy['Predigt'],
                ] : [],
                'absences' => [],
                'services' => [],
            ];
        }

        ksort($days);

        return $days;
    }

    /**
     * Load the services needed for the month view in one batch.
     *
     * @param Carbon $date
     * @param User $user
     * @return Collection<int, Service>
     */
    protected static function loadMonthServices(Carbon $date, User $user): Collection
    {
        $services = Service::setEagerLoads([])->with([
            'location:id,name,default_time,city_id',
            'city:id,name,youtube_channel_url,default_ministries',
            'participants:id,first_name,last_name,title',
            'baptisms:id,service_id,candidate_name',
            'funerals:id,service_id,buried_name,type',
            'weddings:id,service_id,spouse1_name,spouse1_birth_name,spouse2_name,spouse2_birth_name',
            'relatedCities:id',
        ])->inMonthByDate($date)
            ->inCities($user->visibleCities)
            ->ordered()
            ->get();

        $services->each(function (Service $service) {
            $service->participantText = self::buildParticipantText($service);
        });

        return $services;
    }

    /**
     * Load the absences shown in the month header.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param User $user
     * @return Collection<int, Absence>
     */
    protected static function loadMonthAbsences(Carbon $start, Carbon $end, User $user): Collection
    {
        return Absence::setEagerLoads([])->with('user', function ($query) {
            $query->setEagerLoads([])->with([]);
        })->byPeriod($start, $end)
            ->visibleForUser($user)
            ->showInCalendar()
            ->get();
    }

    /**
     * Attach service data to the day map keyed by visible city.
     *
     * @param array<string, array<string, mixed>> $days
     * @param Collection<int, Service> $services
     * @return void
     */
    protected static function addServicesToDays(array &$days, Collection $services): void
    {
        foreach ($services as $service) {
            $dayKey = $service->date->format('Y-m-d');
            if (!isset($days[$dayKey])) {
                continue;
            }

            $cityIds = collect([$service->city_id])
                ->merge($service->relatedCities->pluck('id'))
                ->unique()
                ->values();

            $resource = (new CalendarMonthServiceResource($service))->resolve();

            foreach ($cityIds as $cityId) {
                if (!isset($days[$dayKey]['services'][$cityId])) {
                    $days[$dayKey]['services'][$cityId] = [];
                }
                $days[$dayKey]['services'][$cityId][] = $resource;
            }
        }
    }

    /**
     * Attach absences to all affected days inside the month.
     *
     * @param array<string, array<string, mixed>> $days
     * @param Collection<int, Absence> $absences
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
     * @return void
     */
    protected static function addAbsencesToDays(array &$days, Collection $absences, Carbon $monthStart, Carbon $monthEnd): void
    {
        foreach ($absences as $absence) {
            $currentDate = $absence->from->copy()->startOfDay()->max($monthStart->copy());
            $lastDate = $absence->to->copy()->endOfDay()->min($monthEnd->copy());
            $absenceResource = (new CalendarAbsenceResource($absence))->resolve();

            while ($currentDate <= $lastDate) {
                $dayKey = $currentDate->format('Y-m-d');
                if (isset($days[$dayKey])) {
                    $days[$dayKey]['absences'][] = $absenceResource;
                }
                $currentDate->addDay();
            }
        }
    }

    /**
     * Build compact participant strings for a month service card.
     *
     * @param Service $service
     * @return array<string, string>
     */
    protected static function buildParticipantText(Service $service): array
    {
        $result = [];

        foreach ($service->participants->groupBy('pivot.category') as $category => $participants) {
            $result[$category] = $participants->map(function ($participant) {
                return trim(join(' ', array_filter([
                    $participant->title,
                    $participant->first_name,
                    $participant->last_name,
                ]))) ?: $participant->name;
            })->join(' | ');
        }

        return $result;
    }

}
