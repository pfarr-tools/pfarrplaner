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

use App\Mail\MinistryRequest;
use App\Models\Location;
use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Service;
use App\Services\MinistryService;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/** Report listing ministry requests. */
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
     * @return Application|Factory|View|Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->writableCities;
        $locations = Location::inCities($this->authenticatedUser()->writableCities->pluck('id'))->get();
        $ministries = MinistryService::all(true);
        $users = User::visibleFor($this->authenticatedUser())->get();

        $teams = Team::with('users')->inCities($cities->pluck('id'))->get()->groupBy('city_id');

        return Inertia::render('Report/MinistryRequest/Setup', compact('cities', 'locations', 'ministries', 'users', 'teams'));
    }

    /**
     * Retrieve list of services
     *
     * @return JsonResponse
     */
    public function services(Request $request)
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate([
            'start' => 'date',
            'end' => 'date',
            'locations.*' => 'int|exists:locations,id',
            'cities' => 'required|array|min:1',
            'cities.*' => 'int|exists:cities,id',
            'ministry' => 'nullable|string',
            'only_open' => 'sometimes|boolean',
        ]);

        /** @var array<int, int> $cities */
        $cities = $data['cities'];
        /** @var string $startDate */
        $startDate = $data['start'];
        /** @var string $endDate */
        $endDate = $data['end'];
        /** @var string|null $ministry */
        $ministry = $data['ministry'] ?? null;
        /** @var array<int, int> $locations */
        $locations = $data['locations'] ?? [];

        $services = Service::whereIn('city_id', $cities)
            ->between(Carbon::parse($startDate), Carbon::parse($endDate));

        if (($data['only_open'] ?? false) && ! empty($ministry)) {
            $services->havingOpenMinistries([$ministry]);
        }

        if (count($locations)) {
            $services->whereIn('location_id', $locations);
        }

        return response()->json($services->ordered()->get());
    }

    /**
     * @return JsonResponse
     */
    public function recipients(Request $request)
    {
        /** @var array<string, mixed> $data */
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
        )->pluck('id')->unique();

        return response()->json($users);
    }

    /**
     * @param  Request  $request  Current request.
     * @return mixed
     */
    public function render(Request $request)
    {
        /** @var array<string, mixed> $data */
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

        /** @var array<int|string, string|null> $addresses */
        $addresses = $data['address'] ?? [];
        foreach ($addresses as $id => $address) {
            if (! $address) {
                unset($addresses[$id]);
            } else {
                $user = User::findOrFail((int) $id)->update(['email' => $address]);
            }
        }
        $data['address'] = $addresses;

        /** @var array<int, int> $recipientIds */
        $recipientIds = $data['recipients'];
        $recipients = User::whereIn('id', $recipientIds)->get();

        foreach ($recipients as $user) {
            $address = $addresses[$user->id] ?? $user->email;
            if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'recipients' => 'Für '.$user->name.' ist keine gültige E-Mailadresse hinterlegt.',
                ]);
            }
        }

        /** @var array<int, int> $serviceIds */
        $serviceIds = $data['services'];
        $services = Service::whereIn('id', $serviceIds)
            ->ordered()
            ->get();

        /** @var string $ministry */
        $ministry = $data['ministry'];
        /** @var string|null $text */
        $text = $data['text'] ?? null;

        foreach ($recipients as $user) {
            $sender = $this->authenticatedUser();
            Mail::to($addresses[$user->id] ?? $user->email)->cc($sender)->queue(
                new MinistryRequest($user, $sender, $ministry, $services, $text)
            );
        }

        return redirect()->route('home')->with('success', 'Deine Anfrage wurde gesendet.');
    }
}
