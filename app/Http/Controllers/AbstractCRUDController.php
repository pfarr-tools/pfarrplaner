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

use App\Models\AbstractModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AbstractCRUDController extends Controller
{

    /** @var string $modelClass */
    protected string $modelClass;

    protected function modelName(): string
    {
        return str_replace('Controller', '', Str::afterLast(get_class($this), '\\'));
    }

    protected function getModelsForIndex(): Collection
    {
        return ($this->modelClass)::all();
    }

    protected function addRightsToModelCollection(Collection $collection): Collection
    {
        return $collection->map(function ($item) {
            $item['canEdit'] = (Auth::user()->can('update', $item));
            $item['editRoute'] = $item['canEdit'] ? route(
                ($this->modelClass)::getSingleRouteName('web', 'edit'),
                $item->id
            ) : null;
            $item['canDelete'] = (Auth::user()->can('delete', $item));
            $item['deleteRoute'] = $item['canDelete'] ? route(
                ($this->modelClass)::getSingleRouteName('web', 'destroy'),
                $item->id
            ) : null;
            return $item;
        });
    }

    protected function getSingleModel(Request $request, $modelId = null): AbstractModel
    {
        if (!($id = $modelId)) {
            if (!$request->has($this->modelName())) {
                abort(404);
            }
            $id = $request->get($this->modelName());
        }
        return ($this->modelClass)::findOrFail($id);
    }

    protected function returnResult($action, $result = null)
    {
        return redirect($action->redirectTo())->with($action->messages);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', $this->modelClass);
        $records = $this->addRightsToModelCollection($this->getModelsForIndex());
        return Inertia::render(($this->modelClass)::getVuePath('index'), [
            ($this->modelClass)::pluralKey() => $records,
            'canCreate' => $request->user()->can('create', $this->modelClass),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        Gate::authorize('create', $this->modelClass);
        return Inertia::render(($this->modelClass)::getVuePath('editor'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $creator = app(($this->modelClass)::getContractName('create'));
        $model = $creator->create($request->user(), $request->all());
        return $this->returnResult($creator, $model);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Inertia\Response
     */
    public function edit(Request $request, $modelId)
    {
        $model = $this->getSingleModel($request, $modelId);
        Gate::authorize('update', $model);
        return Inertia::render(($this->modelClass)::getVuePath('editor'), [
            ($this->modelClass)::singularKey() => $model,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, $modelId)
    {
        $model = $this->getSingleModel($request, $modelId);
        $updater = $model->getContractedAction('update');
        $model = $updater->update($request->user(), $model, $request->all());
        return $this->returnResult($updater, $model);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Request $request, $modelId)
    {
        $model = $this->getSingleModel($request, $modelId);
        $deleter = $model->getContractedAction('delete');
        $result = $deleter->delete($request->user(), $model);
        return $this->returnResult($deleter, $result);
    }


}
