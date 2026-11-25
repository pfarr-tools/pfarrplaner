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

use App\Models\Calendar\Occurence;
use App\Models\Scopes\ServicesOnlyScope;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdCentralController extends Controller
{
    /**
     * Show the story ads that are currently running.
     *
     * @param  Request  $request  Current HTTP request.
     */
    public function stories(Request $request): View
    {
        $userId = null;
        if (! $request->user()) {
            if ($request->has('user')) {
                $userId = $request->get('user');
            }
        } else {
            $userId = $request->user()->id;
        }

        $events = Occurence::with('service')
            ->withoutGlobalScope(ServicesOnlyScope::class)
            ->adRunningAt('story', Carbon::now())
            ->get();

        $events->map(function ($item) {
            $service = $item->service;
            if ($service === null) {
                return $item;
            }
            $adConfig = $service->adConfigs->first(fn ($config) => $config->slug == 'story');
            if ($adConfig === null) {
                return $item;
            }
            // Das Attribut wird zur Laufzeit dynamisch am Modell ergänzt und im View genutzt.
            /** @var Occurence&object{adStart: Carbon} $item */
            $item->setAttribute('adStart', $item->start->copy()->subDays($adConfig->offset)->startOfDay());

            return $item;
        });

        return view('adcentral.stories', compact('events'));
    }
}
