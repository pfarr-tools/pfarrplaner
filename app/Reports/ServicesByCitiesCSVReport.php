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

namespace App\Reports;

use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Models\Location;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
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

        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);
        $cities = City::whereIn('id', $data['cities'])->get();

        Carbon::setLocale('de');

        $services = Service::between($start, $end)
            ->notHidden()
            ->inCities($cities)
            ->displayable(Carbon::now())
            ->ordered()
            ->get()
            ->groupBy(function ($service) {
                return $service->date->format('Y-m-d');
            });


        return $this->csv(
            FileNameService::make(
                static::FILE_TITLE . ' ' . $cities->pluck('name')->join(' '),
                null,
                static::FILE_SIGNATURE,
                [$start, $end]
            ),
            $services,
            [
                'Datum' => function ($item, $key) {
                    return $item[0]->date->setTimezone('Europe/Berlin')->isoFormat('dddd, DD. MMMM');
                },
                'Gottesdienste' => function ($item, $key) use ($data) {
                    $records = [];
                    /** @var Service $service */
                    foreach ($item as $service) {
                        $records[] = $service->timeText(true, ':', false, false, true)
                            .'  '.($data['full_location'] ? $service->locationTextWithCity : $service->locationText())
                            .($service->titleText() != 'GD' ? "\n                ".$service->titleText(false, true) : '');
                    }
                    return join("\n", $records);
                },
            ]
        );
    }

}
