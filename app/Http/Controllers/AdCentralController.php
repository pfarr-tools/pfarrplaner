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

use App\Http\Controllers\Controller;
use App\Models\Calendar\Occurence;
use App\Models\Scopes\ServicesOnlyScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdCentralController extends Controller
{

    public function stories(Request $request)
    {
        $userId = null;
        if (!$request->user()) {
            if ($request->has('user')) $userId = $request->get('user');
        } else {
            $userId = $request->user()->id;
        }

        $events = Occurence::with('service')
            ->withoutGlobalScope(ServicesOnlyScope::class)
            ->adRunningAt('story', Carbon::now())
            ->get();


        $events->map(function($item) {
            $item->service->adConfigs->filter(fn($item) => $item->slug == 'story');
            $item->adStart = $item->start->copy()->subDays($item->service->adConfigs[0]->offset)->startOfDay();
            return $item;
        });

        return view('adcentral.stories', compact('events'));
    }

}
