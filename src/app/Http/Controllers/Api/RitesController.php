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
use App\Models\People\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Class RitesController
 */
class RitesController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum,api');
    }

    /**
     * Get search results
     *
     * This is a bit complex, because all personal data is encrypted in the DB and cannot be directly queried.
     * In order to solve this, we need to load all available (i.e. in writable cities or active user is pastor)
     * rites and manually filter the relevant fields.
     *
     * @return Response|JsonResponse
     */
    public function query(Request $request, string $query)
    {
        $query = strtolower($query);
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $cityIds = $user->writableCities->pluck('id');

        $results = [];
        /** @var array<'Baptism'|'Funeral'|'Wedding', list<string>> $queryConfig */
        $queryConfig = [
            'Baptism' => ['candidate_name'],
            'Funeral' => ['buried_name', 'relative_name'],
            'Wedding' => ['spouse1_name', 'spouse2_name'],
        ];
        foreach ($queryConfig as $riteType => $queryFields) {
            $key = strtolower($riteType).'s';
            $riteType = 'App\\Models\\Rites\\'.$riteType;
            $ids = [];

            $selectFields = array_merge(['id'], $queryFields);

            foreach (
                $riteType::select($selectFields)->whereHas('service', function ($q) use ($cityIds, $user) {
                    $q->userParticipates($user, 'P')
                        ->orWhere(function ($q) use ($cityIds) {
                            $q->inCities($cityIds);
                        });
                })->get() as $riteRecord
            ) {
                foreach ($queryFields as $queryField) {
                    if (str_contains(strtolower($riteRecord->$queryField), $query)) {
                        $ids[] = $riteRecord->id;
                    }
                }
            }
            $results[$key] = $riteType::with('service')->whereIn('id', $ids)->get();
        }

        return response()->json($results);
    }
}
