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
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/** Controller for public service pages. */
class ServiceController extends Controller
{
    /**
     * Return upcoming services the authenticated user participates in.
     *
     * @return JsonResponse Upcoming services and the authenticated user.
     */
    public function next(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $services = Service::startingFrom(Carbon::now())
            ->userParticipates($user, 'P')
            ->notHidden()
            ->ordered()
            ->get();

        return response()->json(compact('user', 'services'));
    }

    /**
     * Return past services the authenticated user participates in.
     *
     * @return JsonResponse Past services and the authenticated user.
     */
    public function past(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $services = Service::endingAt(Carbon::now())
            ->userParticipates($user, 'P')
            ->orderedDesc()
            ->get();

        return response()->json(compact('user', 'services'));
    }

    /**
     * Return the latest sermon-related services for the authenticated user.
     *
     * @return JsonResponse Services grouped by sermon, the user and the query.
     */
    public function lastWithSermon(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $query = Service::orderedDesc()
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->notHidden()
            ->whereHas('sermon');
        $services = $query->get()->groupBy('sermon_id');

        return response()->json(compact('user', 'services', 'query'));
    }
}
