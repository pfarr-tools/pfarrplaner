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

use App\Http\Controllers\AbstractCRUDController;
use App\Models\AbstractModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

/**
 * Provides authenticated API CRUD endpoints for one concrete model type.
 *
 * @template TModel of AbstractModel
 *
 * @extends AbstractCRUDController<TModel>
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Pfarrplaner API',
    description: 'REST API for collaborative church service planning.',
)]
#[OA\SecurityScheme(
    securityScheme: 'legacyApiToken',
    type: 'apiKey',
    in: 'query',
    name: 'api_token',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctumBearer',
    type: 'http',
    scheme: 'bearer',
)]
class AbstractApiCRUDController extends AbstractCRUDController
{
    /** Return a JSON representation of the controller action result. */
    protected function returnResult($action, $result = null)
    {
        return response()->json($result);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Collection<int, TModel>|JsonResponse Authorized models, optionally serialized by API subclasses.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', $this->modelClass);

        return $this->addRightsToModelCollection($this->getModelsForIndex());
    }
}
