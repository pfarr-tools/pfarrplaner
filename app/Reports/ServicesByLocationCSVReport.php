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
use App\Models\Service;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ServicesByLocationCSVReport extends AbstractCSVReport
{

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
     * @return \Inertia\Response
     */
    public function setup()
    {
        $locations = Location::whereIn('city_id', Auth::user()->cities->pluck('id'))->get();
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

        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);
        $location = Location::findOrFail($data['location_id']);

        $services = Service::between($start, $end)->where('location_id', $location->id)->ordered()->get();

        Carbon::setLocale('de');

        return $this->csv(
            $start->format('Ymd') . '-' . $end->format('Ymd') . ' ' . $location->name . ' Gottesdienste.csv',
            $services,
            [
                'Datum' => function ($item, $key) {
                    return $item->date->formatLocalized('%A, %d. %B');
                },
                'Uhrzeit' => function ($item, $key) {
                    return $item->date->format('H:i') . ' Uhr';
                },
                'Titel' => function ($item, $key) {
                    if ($item->titleText() == 'GD') {
                        return '';
                    }
                    return $item->titleText(false);
                },
            ]
        );
    }

}
