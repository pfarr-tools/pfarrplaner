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

use App\Events\ServiceUpdated;
use App\Models\Location;
use App\Models\People\Participant;
use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class PlanningInput
 */
class PlanningInput extends AbstractInput
{
    /**
     * @var string
     */
    public $title = 'Planungstabelle';

    public $description = 'Beliebige Dienste für einen bestimmten Zeitraum eintragen';

    /**
     * @return bool
     */
    public function canEdit(): bool
    {
        return Auth::user()?->can('gd-pfarrer-bearbeiten') ||
            Auth::user()?->can('gd-organist-bearbeiten') ||
            Auth::user()?->can('gd-mesner-bearbeiten') ||
            Auth::user()?->can('gd-allgemein-bearbeiten');
    }

    /**
     * @return Application|Factory|View|Response
     */
    public function setup(Request $request)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $cities = $user->writableCities;
        $locations = Location::inCities($cities->pluck('id'))->get();

        $ministries = $this->getAvailableMinistries(
            Participant::query()
                ->pluck('category')
                ->unique()
        );

        return Inertia::render('Inputs/Planning/PlanningInputSetup', compact('cities', 'ministries', 'locations'));
    }

    /**
     * @param  iterable<string>  $reqMinistries  Zu berücksichtigende Dienste.
     * @return array<string, string> Dienste mit ihren Anzeigenamen.
     */
    protected function getAvailableMinistries($reqMinistries)
    {
        $ministries = [];
        foreach ($reqMinistries as $ministry) {
            switch ($ministry) {
                case 'P':
                    if (Auth::user()?->can('gd-pfarrer-bearbeiten')) {
                        $ministries[$ministry] = config('labels.pastor');
                    }
                    break;
                case 'O':
                    if (Auth::user()?->can('gd-organist-bearbeiten')) {
                        $ministries[$ministry] = config('labels.organist');
                    }
                    break;
                case 'M':
                    if (Auth::user()?->can('gd-mesner-bearbeiten')) {
                        $ministries[$ministry] = config('labels.sacristan');
                    }
                    break;
                case 'A':
                    if (Auth::user()?->can('gd-allgemein-bearbeiten')) {
                        $ministries[$ministry] = 'Weitere Beteiligte';
                    }
                    break;
                default:
                    if (Auth::user()?->can('gd-allgemein-bearbeiten') || Auth::user()?->can(
                        'gd-freie-dienste-bearbeiten'
                    )) {
                        $ministries[$ministry] = $ministry;
                    }
            }
        }

        return $ministries;
    }

    /**
     * @return Application|Factory|View|Response|void
     */
    public function input(Request $request)
    {
        $setup = $request->validate(
            [
                'from' => 'required|date_format:d.m.Y',
                'to' => 'required|date_format:d.m.Y',
                'cities.*' => 'required|exists:cities,id',
                'locations.*' => 'nullable|exists:locations,id',
                'ministries.*' => 'required|string',
            ]
        );

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $locations = Location::inCities($setup['cities'])->get();

        $teams = Team::with('users')->inCities($setup['cities'])->get();

        $from = Carbon::createFromFormat('d.m.Y', $setup['from']);
        $to = Carbon::createFromFormat('d.m.Y', $setup['to']);
        abort_unless($from instanceof Carbon && $to instanceof Carbon, 400);

        $query = Service::with(['city'])
            ->select('services.slug')
            ->between($from, $to)
            ->inCities($setup['cities'])
            ->ordered();

        if (count($setup['locations'] ?? [])) {
            $query->whereIn('services.location_id', $setup['locations']);
        }

        $serviceSlugs = $query->pluck('slug');

        $users = User::visibleFor($user)->get();

        $ministries = $this->getAvailableMinistries($setup['ministries'] ?: []);

        $input = $this;

        return Inertia::render(
            'Inputs/Planning/PlanningInputForm',
            compact('serviceSlugs', 'users', 'ministries', 'teams')
        );
    }

    /**
     * @return RedirectResponse|JsonResponse|void
     */
    public function save(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string|exists:services,slug',
            'ministries' => 'required',
        ]);

        $service = Service::where('slug', $data['slug'])->first();
        abort_unless($service instanceof Service, 404);

        $service->trackChanges();
        $originalParticipants = $service->participants;

        // build existing participants table
        $participants = [];
        foreach ($service->participants as $participant) {
            $participants[$participant->pivot->category][$participant->id] = ['category' => $participant->pivot->category];
        }

        // replace with new ministry settings
        foreach ($data['ministries'] as $ministry => $people) {
            $participants[$ministry] = [];
            foreach ($people as $person) {
                $id = is_array($person) ? $person['id'] : $person;
                $participants[$ministry][$id] = ['category' => $ministry];
            }
        }

        $service->participants()->sync([]);
        if (count($participants)) {
            foreach ($participants as $category => $participant) {
                $service->participants()->attach($participant);
            }
        }

        if ($service->isChanged()) {
            $service->storeDiff();
            event(new ServiceUpdated($service, $originalParticipants));
        }

        return response()->json($service->slug);
    }
}
