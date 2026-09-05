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

namespace App\Http\Controllers;

use App\Liturgy\PronounSets\PronounSets;
use App\Models\Attachment;
use App\Models\Calendar\Day;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Wedding;
use App\Models\Service;
use App\Traits\HandlesAttachmentsTrait;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Class WeddingController
 * @package App\Http\Controllers
 */
class WeddingController extends AbstractCRUDController
{

    use HandlesAttachmentsTrait;

    protected string $modelClass = Wedding::class;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function create(Request $request): RedirectResponse
    {
        $service = Service::findOrFail($request->get('service') ?: $request->get('service_id'));
        Gate::authorize('create', [Wedding::class, $service]);
        $creator = app(Wedding::getContractName('create'));
        $wedding = $creator->create($request->user(), $request->all());

        return redirect()->route('weddings.edit', $wedding->id);
    }

    /**
     * @param Service $service
     * @return RedirectResponse
     */
    public function add(Service $service): RedirectResponse
    {
        Gate::authorize('create', [Wedding::class, $service]);
        return redirect()->route('weddings.create', ['service' => $service->id]);
    }

    /**
     * @param Request $request
     * @param Wedding|null $wedding
     * @return array
     */
    protected function getResourcesForEditor(Request $request, $wedding = null): array
    {
        if ($wedding?->service) {
            $wedding->service->setAppends(['pastors', 'locationText', 'timeText']);
        }
        $pronounSets = PronounSets::toArray();
        return compact('pronounSets');
    }


    /**
     * Wedding wizard, step 1: select city and date
     * @param Request $request
     * @return \Inertia\Response
     */
    public function wizard(Request $request)
    {
        Gate::authorize('create', Wedding::class);
        $cities = Auth::user()->writableCities;
        $locations = Location::inCities($cities->pluck('id'))->get();
        $people = User::visibleFor(Auth::user())->get();
        $user = Auth::user();
        return Inertia::render('Rites/WeddingWizard', compact('cities', 'locations', 'people', 'user'));
    }

    public function wizardSave(Request $request)
    {
        Gate::authorize('create', Wedding::class);
        $data = $request->validate(
            [
                'date' => 'required|date_format:d.m.Y H:i',
                'city' => 'required|exists:cities,id',
                'location' => 'required',
                'spouse1_name' => 'required|string',
                'spouse2_name' => 'required|string',
                'pastor' => 'nullable',
            ]
        );

        if (is_numeric($data['location'])) {
            $request->validate(['location' => 'exists:locations,id']);
        }
        $data['date'] = Carbon::createFromFormat('d.m.Y H:i', $data['date'], 'Europe/Berlin')->setTimezone('UTC');

        $city = City::find($data['city']);
        abort_unless(Auth::user()->writableCities->pluck('id')->contains($city?->id), 403);

        $location = $specialLocation = null;
        if ((!is_numeric($data['location'])) || (null === Location::find($data['location']))) {
            $specialLocation = $data['location'];
            $data['location'] = 0;
            $time = $data['date']->format('H:i');
            $ccLocation = $request->get('cc_location') ?: '';
        } else {
            if ($data['location']) {
                $location = Location::find($data['location']);
                $time = $data['date']->format('H:i');
                $ccLocation = $request->get('cc_location') ?: ($request->get(
                    'cc'
                ) ? $location->cc_default_location : '');
            } else {
                $time = $data['date']->format('H:i');
                $ccLocation = $request->get('cc_location') ?: '';
            }
        }

        $service = Service::create(
            [
                'date' => $data['date'],
                'location_id' => ($location ? $location->id : null),
                'special_location' => $specialLocation ?? null,
                'city_id' => $city->id,
                'others' => '',
                'description' => '',
                'need_predicant' => 0,
                'baptism' => 0,
                'eucharist' => 0,
                'offerings_counter1' => '',
                'offerings_counter2' => '',
                'offering_goal' => '',
                'offering_description' => '',
                'offering_type' => '',
                'cc' => 0,
                'cc_location' => '',
                'cc_lesson' => '',
                'cc_staff' => '',
                'wtc_category' => 'WG',
            ]
        );
        $service->update(['slug' => $service->createSlug()]);
        if (!is_array($data['pastor'])) {
            if (Auth::user()->hasRole('Pfarrer:in')) {
                $service->pastors()->sync([Auth::user()->id => ['category' => 'P']]);
            }
        } else {
            $sync = [];
            foreach ($data['pastor'] as $person) {
                if (null !== $person) $sync[(is_numeric($person) ? $person : $person['id'])] = ['category' => 'P'];

            }
            $service->pastors()->sync($sync);
        }

        $wedding = Wedding::create(
            [
                'service_id' => $service->id,
                'spouse1_name' => $data['spouse1_name'],
                'spouse2_name' => $data['spouse2_name'],
                'spouse1_birth_name' => '',
                'spouse2_birth_name' => '',
                'spouse1_email' => '',
                'spouse2_email' => '',
                'spouse1_phone' => '',
                'spouse2_phone' => '',
                'text' => '',
                'registered' => 0,
                'signed' => 0,
                'docs_ready' => 0,
                'docs_where' => '',
                'registration_document' => '',
            ]
        );

        return redirect()->route('weddings.edit', $wedding->id);


    }


    /**
     * @param Wedding $wedding
     * @return false|string
     */
    public function done(Wedding $wedding)
    {
        Gate::authorize('update', $wedding);
        $wedding->done = true;
        $wedding->save();
        return json_encode(true);
    }

    /**
     * @param Request $request
     * @param Wedding $wedding
     * @return \Illuminate\Http\JsonResponse
     */
    public function attach(Request $request, Wedding $wedding)
    {
        Gate::authorize('update', $wedding);
        $this->handleAttachments($request, $wedding);
        $wedding->refresh();
        return response()->json($wedding->attachments);
    }

    /**
     * @param Request $request
     * @param Wedding $wedding
     * @param Attachment $attachment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function detach(Request $request, Wedding $wedding, Attachment $attachment)
    {
        Gate::authorize('update', $wedding);
        $attachment = $wedding->attachments()->findOrFail($attachment->id);
        $file = $attachment->file;
        $wedding->attachments()->where('id', $attachment->id)->delete();
        Storage::delete($file);
        $attachment->delete();
        $wedding->refresh();
        return response()->json($wedding->attachments);
    }
}
