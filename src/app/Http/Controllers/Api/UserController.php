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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\People\ListedPerson;
use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Places\City;
use App\Providers\AuthServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Provides API endpoints for user and people administration.
 */
class UserController extends Controller
{
    /**
     * Apply authentication middleware to all actions.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get all people records for PeopleSelect
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
     */
    public function search($searchString)
    {
        if (empty($searchString)) {
            return response()->json([]);
        }
        $nameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "TRIM(first_name || ' ' || last_name)"
            : "TRIM(CONCAT(first_name, ' ', last_name))";

        $users = User::with('cityScopes')
            ->whereRaw($nameExpression.' LIKE ?', ['%'.$searchString.'%'])
            ->whereDoesntHave('cityScopes', function ($q) {
                return $q->whereIn('city_id', Auth::user()->cities->pluck('id'));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json($users);
    }

    /**
     * Find a person with a matching email address.
     *
     * @param  Request  $request  Request containing the email address.
     * @return JsonResponse Matching person or null.
     */
    public function email(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $person = User::with('cityScopes')
            ->whereRaw('LOWER(email) = LOWER(?)', [$validated['email']])
            ->first();

        return response()->json(['person' => $person]);
    }

    /**
     * Activate a user within a certain city scope
     *
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
        $user->cityScopes()->syncWithoutDetaching([$city->id]);

        return response()->json(true);
    }
}
