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

use App\Models\Calendar\Occurence;
use App\Models\Location;
use App\Models\Places\City;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class OfferingPlanReport
 */
class OfferingPlanReport extends AbstractPDFDocumentReport
{
    public const FILE_SIGNATURE = '77';

    public const FILE_TITLE = 'Opferplan';

    /**
     * @var string
     */
    public $title = 'Opferplan';

    /**
     * @var string
     */
    public $group = 'Opfer';

    /**
     * @var string
     */
    public $description = 'Übersicht aller Opferzwecke für einen Zeitraum';

    protected $inertia = true;

    /**
     * @return Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->cities;
        $locations = Location::inCities($cities->pluck('id'))->get();

        return Inertia::render('Report/OfferingPlan/Setup', compact('cities', 'locations'));
    }

    /**
     * @return mixed|string
     */
    public function render(Request $request)
    {

        $data = $request->validate(
            [
                'cities.*' => 'required|int|exists:cities,id',
                'locations' => 'nullable|array',
                'locations.*' => 'nullable|int|exists:locations,id',
                'start' => 'required|date_format:Y-m-d',
                'end' => 'required|date_format:Y-m-d|after_or_equal:start',
                'includeOfferingCounters' => 'bool',
                'highlightEmpty' => 'bool',
            ]
        );
        /** @var array{cities: array<int, int>, locations?: array<int, int>|null, start: string, end: string, includeOfferingCounters?: bool, highlightEmpty?: bool} $data */
        $start = Carbon::parse($data['start'])->startOfDay();
        $end = Carbon::parse($data['end'])->endOfDay();
        $data['start'] = $start;
        $data['end'] = $end;
        $data['occurences'] = Occurence::with('event')->whereHas('event', function ($query) use ($data, $start, $end) {
            $query->servicesOnly()
                ->inCitiesAndLocations($data['cities'], $data['locations'] ?? null)
                ->whereDoesntHave('funerals')
                ->between($start, $end)
                ->ordered();
        })->orderBy('start')->get();
        $cities = City::whereIn('id', $data['cities'])->get();
        $data['cities'] = $cities;
        /** @var string $cityNames */
        $cityNames = $cities->pluck('name')->join(' ');

        $this->setHeader('');
        $this->setFooter(
            '<div style="width: 100%; font-size: 8px; color: #666; padding: 0 10mm;">'
            .'<span style="float: left;">Stand: '.Carbon::now()->setTimezone('Europe/Berlin')->format('d.m.Y H:i').' Uhr</span>'
            .'<span style="float: right;">Seite <span class="pageNumber"></span> / <span class="totalPages"></span></span>'
            .'</div>'
        );

        return $this->sendToFile(
            FileNameService::make(
                self::FILE_TITLE.' '.$cityNames,
                'pdf',
                static::FILE_SIGNATURE,
                [$start, $end],
                false,
                null,
                'Ymd'
            ),
            $data,
            ['format' => 'A4']
        );
    }
}
