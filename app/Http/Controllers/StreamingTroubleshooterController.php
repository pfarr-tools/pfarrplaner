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

use App\Helpers\YoutubeHelper;
use App\Integrations\Youtube\YoutubeIntegration;
use App\Models\Places\City;
use App\Models\Service;
use App\Models\Streaming\Broadcast;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class StreamingTroubleshooterController extends Controller
{

    public function index(Request $request, $city)
    {
        $city = City::where('name', $city)->first();
        if(!$city) abort(404);

        if (! $request->hasValidSignature()) {
            abort(401);
        }

        $activeBroadcasts = Broadcast::getActive($city);
        $inactiveBroadcasts = Broadcast::getInactive($city);

        $nextServicesWithStream = Service::inCity($city)
            ->startingFrom(Carbon::now()->setTime(0,0,0))
            ->where('youtube_url', '!=', '')
            ->ordered()
            ->get();

        $nextServicesWithoutStream = Service::inCity($city)
            ->startingFrom(Carbon::now()->setTime(0,0,0))
            ->where(function ($query) {
                $query->where('youtube_url', '')
                    ->orWhereNull('youtube_url');
            })
            ->ordered()
            ->limit(10)
            ->get();



        return Inertia::render('streaming/troubleshooter', compact('city', 'activeBroadcasts', 'inactiveBroadcasts', 'nextServicesWithStream', 'nextServicesWithoutStream'));
    }

    public function activateService(Service $service)
    {
        $broadcast = Broadcast::get($service);
        if (null === $broadcast) {
            $broadcast = Broadcast::create($service);
            $service->update(['youtube_url' => $broadcast->getSharerUrl()]);
        }
        $broadcast->activate();
        return redirect(URL::signedRoute('streaming.troubleshooter', strtolower($service->city->name)));
    }

    public function activateBroadcast($city, $broadcast)
    {
        $city = City::where('name', $city)->first();
        if(!$city) abort(404);

        $broadcast = Broadcast::getFromId($broadcast, $city);
        $broadcast->activate();
        return redirect(URL::signedRoute('streaming.troubleshooter', strtolower($city->name)));
    }

    public function resetService(Service $service)
    {
        $youtube = YoutubeIntegration::get($service->city)->getYoutube();
        $youtube->liveBroadcasts->delete(YoutubeHelper::getCode($service->youtube_url));
        $broadcast = Broadcast::create($service);
        $service->update(['youtube_url' => $broadcast->getSharerUrl()]);
        return redirect(URL::signedRoute('streaming.troubleshooter', strtolower($service->city->name)));
    }
}
