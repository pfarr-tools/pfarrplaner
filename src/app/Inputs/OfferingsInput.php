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

namespace App\Inputs;

use App\Models\Location;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class OfferingsInput
 */
class OfferingsInput extends AbstractInput
{
    /**
     * @var string
     */
    public $title = 'Opferplan';

    public $description = 'Opferzwecke für ein ganzes Jahr bearbeiten';

    /**
     * @var string
     */
    protected $setupView = 'inputs.offerings.setup';

    /**
     * Determine whether the current user may edit offering plans.
     *
     * @return bool Whether the permission is granted.
     */
    public function canEdit(): bool
    {
        return Auth::user()->can('gd-opfer-bearbeiten');
    }

    /**
     * @return Response
     */
    public function setup(Request $request)
    {
        $cities = Auth::user()->cities;
        $locations = Location::inCities($cities->pluck('id'))->get();

        return Inertia::render('Inputs/Offerings/Setup', compact('cities', 'locations'));
    }

    /**
     * @return Response
     */
    public function input(Request $request)
    {
        $setup = $request->validate(
            [
                'from' => 'required|date_format:d.m.Y',
                'to' => 'required|date_format:d.m.Y',
                'cities.*' => 'required|exists:cities,id',
                'locations.*' => 'nullable|exists:locations,id',
            ]
        );

        $cityIds = collect();
        foreach (City::query()->whereIn('id', $setup['cities'])->get() as $city) {
            $cityIds->push($city->id);
            if ($city->is_org) {
                $cityIds = $cityIds->merge($city->children->pluck('id'));
            }
        }

        $query = Service::setEagerLoads([])
            ->with(['location:id,name', 'city:id,name'])
            ->select([
                'services.id',
                'services.slug',
                'services.date',
                'services.time',
                'services.city_id',
                'services.location_id',
                'services.special_location',
                'services.offering_goal',
                'services.offering_type',
                'services.offering_text',
                'services.offering_description',
                'services.offerings_counter1',
                'services.offerings_counter2',
                'services.offering_amount',
            ])
            ->between(
                Carbon::createFromFormat('d.m.Y', $setup['from']),
                Carbon::createFromFormat('d.m.Y', $setup['to'])
            )
            ->inCities($setup['cities'])
            ->whereIn('services.city_id', $cityIds->unique()->values())
            ->ordered();

        if (count($setup['locations'] ?? [])) {
            $query->whereIn('services.location_id', $setup['locations']);
        }

        $services = $query->get();
        $services->each->setAppends(['locationText', 'timeText']);

        return Inertia::render('Inputs/Offerings/Form', compact('services'));
    }

    /**
     * @return JsonResponse|void
     */
    public function save(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:services,id',
            'offering_goal' => 'nullable|string',
            'offering_type' => 'nullable|string',
            'offering_text' => 'nullable|string',
            'offering_description' => 'nullable|string',
            'offerings_counter1' => 'nullable|string',
            'offerings_counter2' => 'nullable|string',
            'offering_amount' => 'nullable|string',
        ]);
        $service = Service::find($data['id']);
        if (! $service) {
            abort(404);
        }
        unset($data['id']);
        $service->update($data);

        return response()->json($service->slug);
    }
}
