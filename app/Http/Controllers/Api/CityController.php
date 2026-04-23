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

use App\Http\Controllers\AbstractCRUDController;
use App\Http\Controllers\Controller;
use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Models\Places\City;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Class CityController
 * @package App\Http\Controllers\Api
 */
class CityController extends AbstractApiCRUDController
{

    protected string $modelClass = City::class;


    public function index(Request $request)
    {
        Gate::authorize('viewAny', $this->modelClass);
        $user = $request->user();
        if ($user->isAdmin) {
            return response()->json(City::all());
        }
        return response()->json($user->cities()->get());
    }

    /**
     * @param City $city
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function konfiAppTypes(City $city)
    {
        $types = KonfiAppIntegration::isActive($city) ?
            KonfiAppIntegration::get($city)->listEventTypes() : [];
        return response()->json($types);
    }

}
