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

namespace App\Reports;

use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServicesByCitiesCSVReport extends AbstractCSVReport
{
    public const FILE_TITLE = 'Gottesdienste';

    public const FILE_SIGNATURE = '50.0';

    /**
     * @var string
     */
    public $title = 'Gottesdienste nach Kirchengemeinden';

    /**
     * @var string
     */
    public $description = 'Gibt alle Gottesdienste in einem bestimmten Kirchengemeinden für einen bestimmten Zeitraum aus.';

    protected $inertia = true;

    /**
     * @return Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->cities;

        return Inertia::render('Report/ServicesByCitiesCSV/Setup', compact('cities'));
    }

    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'start' => 'required|date',
                'end' => 'required|date',
                'cities.*' => 'exists:cities,id',
                'full_location' => 'boolean',
            ]
        );
        /** @var array{start: string, end: string, cities: array<int, int>, full_location?: bool} $data */
        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);
        $cities = City::whereIn('id', $data['cities'])->get();

        Carbon::setLocale('de');
        /** @var string $cityNames */
        $cityNames = $cities->pluck('name')->join(' ');

        $services = Service::between($start, $end)
            ->notHidden()
            ->inCities($cities)
            ->displayable(Carbon::now())
            ->ordered()
            ->get()
            ->groupBy(function ($service) {
                return $service->trueDate()->format('Y-m-d');
            });

        return $this->csv(
            FileNameService::make(
                self::FILE_TITLE.' '.$cityNames,
                null,
                static::FILE_SIGNATURE,
                [$start, $end]
            ),
            $services,
            [
                'Datum' => function ($item, $key): string {
                    /** @var array<int, Service> $item */
                    /** @var Carbon $date */
                    $date = $item[0]->date;

                    return $date->setTimezone('Europe/Berlin')->isoFormat('dddd, DD. MMMM');
                },
                'Gottesdienste' => function ($item, $key) use ($data): string {
                    /** @var array<int, Service> $item */
                    $records = [];
                    foreach ($item as $service) {
                        /** @var string $location */
                        $location = ($data['full_location'] ?? false)
                            ? $service->locationTextWithCity
                            : $service->locationText();
                        $records[] = $service->timeText(true, ':', false, false, true)
                            .'  '.$location
                            .($service->titleText() != 'GD' ? "\n                ".$service->titleText(false, true) : '');
                    }

                    return implode("\n", $records);
                },
            ]
        );
    }
}
