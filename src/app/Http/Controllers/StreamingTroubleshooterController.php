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

namespace App\Http\Controllers;

use App\Helpers\YoutubeHelper;
use App\Integrations\Youtube\YoutubeIntegration;
use App\Models\Places\City;
use App\Models\Service;
use App\Models\Streaming\Broadcast;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/** Controller for diagnosing and fixing live stream settings. */
class StreamingTroubleshooterController extends Controller
{
    /**
     * Show the streaming troubleshooting page with a city's broadcasts and upcoming services.
     *
     * @param  Request  $request  Signed request protecting the troubleshooting page.
     * @param  string  $city  Name of the city whose streaming setup is checked.
     * @return Response Inertia response with the city's broadcasts and services.
     */
    public function index(Request $request, string $city): Response
    {
        $selectedCity = City::where('name', $city)->first();
        if (! $selectedCity) {
            abort(404);
        }

        if (! $request->hasValidSignature()) {
            abort(401);
        }

        $activeBroadcasts = Broadcast::getActive($selectedCity);
        $inactiveBroadcasts = Broadcast::getInactive($selectedCity);

        $nextServicesWithStream = Service::inCity($selectedCity)
            ->startingFrom(Carbon::now()->setTime(0, 0, 0))
            ->where('youtube_url', '!=', '')
            ->ordered()
            ->get();

        $nextServicesWithoutStream = Service::inCity($selectedCity)
            ->startingFrom(Carbon::now()->setTime(0, 0, 0))
            ->where(function ($query) {
                $query->where('youtube_url', '')
                    ->orWhereNull('youtube_url');
            })
            ->ordered()
            ->limit(10)
            ->get();

        return Inertia::render('streaming/troubleshooter', [
            'city' => $selectedCity,
            'activeBroadcasts' => $activeBroadcasts,
            'inactiveBroadcasts' => $inactiveBroadcasts,
            'nextServicesWithStream' => $nextServicesWithStream,
            'nextServicesWithoutStream' => $nextServicesWithoutStream,
        ]);
    }

    /**
     * Activate the broadcast linked to a service and return to the troubleshooting page.
     *
     * @param  Service  $service  Service whose YouTube broadcast should be activated.
     * @return RedirectResponse Redirect to the signed troubleshooting page of the service's city.
     */
    public function activateService(Service $service): RedirectResponse
    {
        $broadcast = Broadcast::get($service);
        if ($broadcast === null) {
            $broadcast = Broadcast::create($service);
            $service->update(['youtube_url' => $broadcast->getSharerUrl()]);
        }
        abort_unless($broadcast !== null, 404);
        abort_unless($service->city !== null, 404);
        $broadcast->activate();

        return redirect(URL::signedRoute('streaming.troubleshooter', strtolower($service->city->name)));
    }

    /**
     * Activate a YouTube broadcast that is not linked to a service.
     *
     * @param  string  $city  Name of the city whose YouTube account owns the broadcast.
     * @param  string  $broadcast  YouTube broadcast ID to activate.
     * @return RedirectResponse Redirect to the signed troubleshooting page of the city.
     */
    public function activateBroadcast(string $city, string $broadcast): RedirectResponse
    {
        $selectedCity = City::where('name', $city)->first();
        if (! $selectedCity) {
            abort(404);
        }

        $selectedBroadcast = Broadcast::getFromId($broadcast, $selectedCity);
        abort_unless($selectedBroadcast !== null, 404);
        $selectedBroadcast->activate();

        return redirect(URL::signedRoute('streaming.troubleshooter', strtolower($selectedCity->name)));
    }

    /**
     * Replace the YouTube broadcast of a service with a newly created one.
     *
     * @param  Service  $service  Service whose YouTube broadcast should be reset.
     * @return RedirectResponse Redirect to the signed troubleshooting page of the service's city.
     */
    public function resetService(Service $service): RedirectResponse
    {
        $city = $service->city;
        abort_unless($city !== null, 404);
        $youtubeUrl = $service->youtube_url;
        abort_unless(is_string($youtubeUrl), 422);
        $youtube = YoutubeIntegration::get($city)->getYoutube();
        $liveBroadcasts = $youtube->liveBroadcasts;
        if (! is_object($liveBroadcasts) || ! method_exists($liveBroadcasts, 'delete')) {
            abort(500);
        }
        $liveBroadcasts->delete(YoutubeHelper::getCode($youtubeUrl));
        $broadcast = Broadcast::create($service);
        $service->update(['youtube_url' => $broadcast->getSharerUrl()]);

        return redirect(URL::signedRoute('streaming.troubleshooter', strtolower($city->name)));
    }
}
