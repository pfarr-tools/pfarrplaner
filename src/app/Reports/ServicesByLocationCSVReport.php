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

use App\Models\Location;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServicesByLocationCSVReport extends AbstractCSVReport
{
    public const FILE_TITLE = 'Gottesdienste';

    public const FILE_SIGNATURE = '50.0';

    /**
     * @var string
     */
    public $title = 'Gottesdienste nach Ort';

    /**
     * @var string
     */
    public $description = 'Gibt alle Gottesdienste an einem bestimmten Ort für einen bestimmten Zeitraum aus.';

    protected $inertia = true;

    /**
     * @return Response
     */
    public function setup()
    {
        $locations = Location::inCities($this->authenticatedUser()->cities->pluck('id'))->get();

        return Inertia::render('Report/ServicesByLocationCSV/Setup', compact('locations'));
    }

    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'start' => 'required|date',
                'end' => 'required|date',
                'location_id' => 'required|exists:locations,id',
            ]
        );

        if (! is_array($data)) {
            abort(400, 'Ungültige Angaben für den Zeitraum oder den Ort.');
        }

        $start = $data['start'] ?? null;
        $end = $data['end'] ?? null;
        $locationId = $data['location_id'] ?? null;
        if (! is_string($start) || ! is_string($end) || ! is_scalar($locationId)) {
            abort(400, 'Ungültige Angaben für den Zeitraum oder den Ort.');
        }

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $location = Location::findOrFail((int) $locationId);

        $services = Service::between($start, $end)->where('location_id', $location->id)->ordered()->get();

        Carbon::setLocale('de');

        $locationName = $location->name;
        $locationName = is_scalar($locationName) ? (string) $locationName : '';

        return $this->csv(
            FileNameService::make(
                self::FILE_TITLE.' '.$locationName,
                null,
                static::FILE_SIGNATURE,
                [$start, $end]
            ),
            $services,
            [
                'Datum' => function ($item, $key) {
                    if (! $item instanceof Service) {
                        return '';
                    }
                    $date = $item->date;
                    if (! $date instanceof Carbon) {
                        return '';
                    }

                    return $date->setTimezone('Europe/Berlin')->isoFormat('dddd, DD. MMMM');
                },
                'Uhrzeit' => function ($item, $key) {
                    if (! $item instanceof Service) {
                        return '';
                    }
                    $date = $item->date;
                    if (! $date instanceof Carbon) {
                        return '';
                    }

                    return $date->setTimezone('Europe/Berlin')->format('H:i').' Uhr';
                },
                'Titel' => function ($item, $key) {
                    if (! $item instanceof Service) {
                        return '';
                    }
                    if ($item->titleText() == 'GD') {
                        return '';
                    }

                    return $item->titleText(false);
                },
            ]
        );
    }
}
