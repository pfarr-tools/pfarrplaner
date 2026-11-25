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

namespace App\Http\Controllers\Extranet;

use App\Http\Controllers\Controller;
use App\Models\People\User;
use App\Models\Sermon;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Controller for public sermon pages. */
class SermonController extends Controller
{
    /**
     * Attach public service metadata and the rite privacy flag to a sermon.
     *
     * @param  Sermon  $sermon  Sermon whose services are summarized.
     * @param  bool  $includePreview  Whether future services are included.
     * @return Sermon The enriched sermon instance.
     */
    protected function transformSermon(Sermon $sermon, $includePreview = false)
    {
        $services = [];
        $isPrivate = false;
        foreach ($sermon->services as $service) {
            $isPrivate = $isPrivate || (count($service->funerals) || count($service->weddings));
            if (($service->date <= Carbon::now()) || ($includePreview)) {
                $services[] = [
                    /** @var Service $service */
                    'date' => $service->datetime,
                    'time' => $service->timeText(),
                    'title' => $service->titleText(false),
                    'location' => $service->locationText(),
                    'liturgy' => $service->liturgicalInfo,
                    'video' => $service->youtube_url,
                ];
            }
        }
        // Diese Properties werden vom Modell zur Laufzeit dynamisch ergänzt.
        /** @var Sermon&object{events: array<int, array<string, mixed>>, isPrivate: bool} $sermon */
        $sermon->setAttribute('events', $services);
        $sermon->setAttribute('isPrivate', $isPrivate);

        return $sermon;
    }

    /**
     * Headers.
     *
     * @return mixed
     */
    public function headers()
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $services = Service::with('sermon')->userParticipates($user, 'P')->whereHas('sermon')->orderedDesc()->get();
        $sermons = [];
        foreach ($services as $service) {
            if (! isset($sermons[$service->sermon_id])) {
                $sermon = $service->sermon;
                if ($sermon === null || $service->date === null) {
                    continue;
                }
                $sermons[$service->sermon_id] = [
                    'title' => $sermon->fullTitle,
                    'date' => $service->date->format('c'),
                    'id' => $sermon->id,
                    'slug' => $sermon->slug,
                    'location' => $service->locationText(),
                    'time' => $service->timeText(),
                ];
            }
        }

        return response()->json(array_values($sermons));
    }

    /**
     * Latest.
     *
     * @return mixed
     */
    public function latest(Request $request)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $events = [];
        $includeText = $request->get('includeText', false);

        $tmpSermons = Sermon::with('services')
            ->whereHas(
                'services',
                function ($query) use ($user) {
                    $query->userParticipates($user, 'P');
                    $query->endingAt(Carbon::now());
                }
            )->get();

        $sermons = [];
        foreach ($tmpSermons as $sermon) {
            $key = $sermon->latestService();
            $sermon = $this->transformSermon($sermon, false);
            if (! $includeText) {
                $sermon->text = '';
            }
            if (count($sermon->services)) {
                $sermons[$key] = $sermon;
            }
            unset($sermon->services);
        }
        krsort($sermons);

        return response()->json(compact('user', 'sermons'));
    }

    /**
     * Details.
     *
     * @param  int|string  $sermonId  Numerische ID oder Slug der Predigt.
     * @return mixed
     */
    public function details(int|string $sermonId)
    {
        if (is_numeric($sermonId)) {
            $sermon = Sermon::findOrFail($sermonId);
        } else {
            $sermon = Sermon::where('slug', $sermonId)->first() or abort(404);
        }

        return response()->json($this->transformSermon($sermon, true));
    }
}
