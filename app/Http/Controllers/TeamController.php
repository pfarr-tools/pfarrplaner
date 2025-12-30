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

use App\Models\Places\City;
use App\Models\People\Team;
use App\Models\People\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $writableCityIds = Auth::user()->writableCities->pluck('id');
        $teams = Team::with('city', 'users')
            ->inCities(Auth::user()->cities->pluck('id'))
            ->orderBy('name')
            ->get()
            ->map(function ($item) use ($writableCityIds) {
                $item->writable = $writableCityIds->contains($item->city_id);
                return $item;
            });
        return Inertia::render('Teams/Index', compact('teams'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $counter = Team::inCities(Auth::user()->cities->pluck('id'))
                ->orderBy('name')
                ->count() + 1;
        $team = Team::create([
                                 'name' => 'Neues Team #' . $counter,
                                 'city_id' => Auth::user()->cities->pluck('id')->first(),
                             ]);
        return redirect()->route('team.edit', $team->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Team $team
     * @return \Inertia\Response
     */
    public function edit(Team $team)
    {
        $team->load(['city', 'users']);
        $cities = Auth::user()->writableCities;
        $users = User::visibleFor(Auth::user())->get();
        return Inertia::render('Teams/TeamEditor', compact('team', 'cities', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Team $team;
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Team $team)
    {
        $data = $this->validateRequest($request);
        $team->update($data);
        if (isset($data['users'])) $team->users()->sync(collect($data['users'])->pluck('id'));
        return redirect()->route('teams.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Team $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('teams.index');
    }

    protected function validateRequest(Request $request)
    {
        return $request->validate([
            'name' => 'required|string',
            'city_id' => 'required|int|exists:cities,id',
            'users' => 'nullable',
                                  ]);
    }

    /**
     * @param City $city
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCity(City $city)
    {
        $teams = Team::with('users')->where('city_id', $city->id)->orderBy('name')->get();
        return response()->json($teams);
    }
}
