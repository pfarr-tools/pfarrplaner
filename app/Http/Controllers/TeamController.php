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

class TeamController extends AbstractCRUDController
{
    protected string $modelClass = Team::class;

    /**
     * Prepare the collection of models for the index view.
     */
    protected function getModelsForIndex(): \Illuminate\Support\Collection
    {
        $writableCityIds = Auth::user()->writableCities->pluck('id');
        return Team::with(Team::$relationsForIndex)
            ->inCities(Auth::user()->cities->pluck('id'))
            ->orderBy('name')
            ->get()
            ->map(function ($item) use ($writableCityIds) {
                $item->writable = $writableCityIds->contains($item->city_id);
                return $item;
            });
    }

    /**
     * Get additional resources needed to display the editor component.
     */
    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        return [
            'cities' => Auth::user()->writableCities,
            'users' => User::visibleFor(Auth::user())->get(),
        ];
    }

    /**
     * Get data to pre-fill a new model with.
     */
    protected function preFillNewModel(Request $request): array
    {
        $counter = Team::inCities(Auth::user()->cities->pluck('id'))->count() + 1;

        return [
            'name' => 'Neues Team #' . $counter,
            'city_id' => Auth::user()->cities->pluck('id')->first(),
        ];
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
