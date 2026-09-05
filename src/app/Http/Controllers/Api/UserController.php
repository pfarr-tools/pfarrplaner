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

namespace App\Http\Controllers\Api;

use App\Models\People\ListedPerson;
use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Providers\AuthServiceProvider;

class UserController extends \App\Http\Controllers\Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get all people records for PeopleSelect
     * @return \Illuminate\Http\JsonResponse
     */
    public function select()
    {
        $users = ListedPerson::select(['id', 'first_name', 'last_name', 'title'])
            ->visibleFor(Auth::user())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        $teams = Team::with('users')->get();
        return response()->json(compact('users', 'teams'));
    }


    /**
     * Search for people to add from other cities
     * @param $searchString
     * @return \Illuminate\Http\JsonResponse
     */
    public function search($searchString)
    {
        Gate::authorize('create', User::class);
        if (empty($searchString)) return response()->json([]);
        $users = User::with('cityScopes')
            ->whereRaw(
                "TRIM(CONCAT(first_name, ' ', last_name)) LIKE ?",
                ['%' . $searchString . '%']
            )->whereDoesntHave('cityScopes', function($q) {
                return $q->whereIn('city_id', Auth::user()->cities->pluck('id'));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json($users);
    }

    /**
     * Activate a user within a certain city scope
     * @param User $user
     * @param City $city
     * @return void
     */
    public function activate(User $user, City $city)
    {
        Gate::authorize('create', User::class);
        abort_unless(
            $city->administeredBy(Auth::user())
            || Auth::user()->hasRole(AuthServiceProvider::ADMIN)
            || Auth::user()->hasRole(AuthServiceProvider::SUPER),
            403
        );
        $user->cityScopes()->attach($city->id);
        return response()->json(true);
    }
}
