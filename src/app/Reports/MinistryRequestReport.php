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


use App\Mail\MinistryRequest;
use App\Services\MinistryService;
use App\Models\Location;
use App\Models\People\Team;
use App\Models\Service;
use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;


class MinistryRequestReport extends AbstractReport
{

    /**
     * @var string
     */
    public $title = 'Dienstanfrage senden';
    /**
     * @var string
     */
    public $group = 'Dienstplanung';
    /**
     * @var string
     */
    public $description = 'Sendet eine Anfrage mit zu belegenden Terminen an alle Teilnehmer eines Dienstes';
    /**
     * @var string
     */
    public $icon = 'fa fa-envelope';

    protected $inertia = true;


    /**
     * @return Application|Factory|View
     */
    public function setup()
    {
        $cities = Auth::user()->writableCities;
        $locations = Location::inCities(Auth::user()->writableCities->pluck('id'))->get();
        $ministries = MinistryService::all(true);
        $users = User::visibleFor(Auth::user())->get();

        $teams = Team::with('users')->inCities($cities->pluck('id'))->get()->groupBy('city_id');
        return Inertia::render('Report/MinistryRequest/Setup', compact('cities', 'locations', 'ministries', 'users', 'teams'));
    }


    /**
     * Retrieve list of services
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function services(Request $request)
    {
        $data = $request->validate([
            'start' => 'date',
            'end' => 'date',
            'locations.*' => 'int|exists:locations,id',
            'cities' => 'required|array|min:1',
            'cities.*' => 'int|exists:cities,id',
            'ministry' => 'nullable|string',
            'only_open' => 'sometimes|boolean',
                           ]);

        $services = Service::whereIn('city_id', $data['cities'])
            ->between(Carbon::parse($data['start']), Carbon::parse($data['end']));

        if (($data['only_open'] ?? false) && !empty($data['ministry'])) {
            $services->havingOpenMinistries([$data['ministry']]);
        }

        if (count($data['locations'] ?? [])) {
            $services->whereIn('location_id', ($data['locations'] ?? []));
        }

        return response()->json($services->ordered()->get());
    }

    public function recipients(Request $request)
    {
        $data = $request->validate([
                                       'ministry' => 'string',
                                       'cities' => 'required|array|min:1',
                                       'cities.*' => 'int|exists:cities,id',
                                   ]);

        $users = User::select('id')->whereHas(
            'services',
            function ($query) use ($data) {
                $query->where('service_user.category', $data['ministry']);
                $query->whereIn('city_id', $data['cities']);
            }
        )->get()->pluck('id')->unique();

        return response()->json($users);
    }

    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'ministry' => 'required',
                'services' => 'required|array|min:1',
                'services.*' => 'integer|exists:services,id',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'integer|exists:users,id',
                'address' => 'nullable',
                'address.*' => 'nullable|email',
                'text' => 'nullable|string',
            ]
        );


        foreach (($data['address'] ?? []) as $id => $address) {
            if (!$address) {
                unset($data['address'][$id]);
            } else {
                $user = User::find($id)->update(['email' => $address]);
            }
        }

        $data['recipients'] = User::whereIn('id', $data['recipients'])->get();

        foreach ($data['recipients'] as $user) {
            $address = $data['address'][$user->id] ?? $user->email;
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'recipients' => 'Für ' . $user->name . ' ist keine gültige E-Mailadresse hinterlegt.',
                ]);
            }
        }

        $data['services'] = Service::whereIn('id', $data['services'])
            ->ordered()
            ->get();

        $ministries = MinistryService::all();


        foreach ($data['recipients'] as $user) {
            $sender = Auth::user();
            Mail::to($data['address'][$user->id] ?? $user->email)->cc($sender)->queue(
                new MinistryRequest($user, $sender, $data['ministry'], $data['services'], $data['text'])
            );
        }

        return redirect()->route('home')->with('success', 'Deine Anfrage wurde gesendet.');
    }

}
