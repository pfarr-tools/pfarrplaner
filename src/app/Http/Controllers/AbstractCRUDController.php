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

namespace App\Http\Controllers;

use App\Actions\AbstractAction;
use App\Models\AbstractModel;
use App\Models\People\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Provides shared CRUD actions and response handling for one model type.
 *
 * @template TModel of AbstractModel
 */
class AbstractCRUDController extends Controller
{
    /** @var class-string<TModel> Concrete model handled by this controller. */
    protected string $modelClass;

    /**
     * Get the model name derived from the controller name
     */
    protected function modelName(): string
    {
        return str_replace('Controller', '', Str::afterLast(get_class($this), '\\'));
    }

    /**
     * Prepare the collection of models for the index view.
     *
     * @return Collection<int, TModel> Models with the configured relationships.
     */
    protected function getModelsForIndex(): Collection
    {
        return ($this->modelClass)::with(($this->modelClass)::$relationsForIndex)->get();
    }

    /**
     * Add authorization flags and editor routes to each model in place.
     *
     * @param  Collection<int, TModel>  $collection  Models to decorate for the index.
     * @return Collection<int, TModel> The decorated models with their existing keys.
     */
    protected function addRightsToModelCollection(Collection $collection): Collection
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $collection->map(function ($item) use ($user) {
            $item['canEdit'] = $user->can('update', $item);
            $item['editRoute'] = $item['canEdit'] ? route(
                ($this->modelClass)::getSingleRouteName('web', 'edit'),
                $item->id
            ) : null;
            $item['canDelete'] = $user->can('delete', $item);
            $item['deleteRoute'] = $item['canDelete'] ? route(
                ($this->modelClass)::getSingleRouteName('web', 'destroy'),
                $item->id
            ) : null;

            return $item;
        });
    }

    /**
     * Get a single model instance, optionally with relations
     *
     * @param  mixed  $modelId
     * @param  list<string>  $relations  Relationships to eager load.
     * @return TModel The requested model.
     */
    protected function getSingleModel(Request $request, $modelId = null, $relations = []): AbstractModel
    {
        if (! ($id = $modelId)) {
            if (! $request->has($this->modelName())) {
                abort(404);
            }
            $id = $request->get($this->modelName());
        }

        return ($this->modelClass)::with($relations)->whereKey($id)->firstOrFail();
    }

    /**
     * Redirect after an action and include status messages
     *
     * @param  AbstractAction  $action  Completed action providing a redirect and flash messages.
     * @param  mixed  $result
     * @return RedirectResponse|JsonResponse
     */
    protected function returnResult($action, $result = null)
    {
        return redirect($action->redirectTo())->with($action->messages);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|JsonResponse|Collection<int, TModel> Web page or API representation.
     */
    public function index(Request $request)
    {
        Gate::authorize('index', $this->modelClass);
        $records = $this->addRightsToModelCollection($this->getModelsForIndex());

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return Inertia::render(($this->modelClass)::getVuePath('index'), [
            ($this->modelClass)::pluralKey() => $records,
            'canCreate' => $user->can('create', $this->modelClass),
        ]);
    }

    /**
     * Get additional resources needed to display the editor component.
     *
     * @param  TModel|null  $model  Existing model, or null when creating one.
     * @return array<string, mixed> Named Inertia props for the editor.
     */
    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        return [];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|RedirectResponse Editor page or redirect to a newly created record.
     */
    public function create(Request $request)
    {
        Gate::authorize('create', $this->modelClass);
        $data = $this->getResourcesForEditor($request);
        $newModel = ($this->modelClass)::getEmptyModel();
        $newModel->fill($this->preFillNewModel($request));
        $data[($this->modelClass)::propKey()] = $newModel;
        if ($request->has('tab')) {
            $data['tab'] = $request->get('tab');
        }

        return Inertia::render(($this->modelClass)::getVuePath('editor'), $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request)
    {
        /** @var AbstractAction $creator */
        $creator = app(($this->modelClass)::getContractName('create'));
        if (! method_exists($creator, 'create')) {
            abort(500);
        }
        $model = $creator->create($request->user(), $request->all());

        return $this->returnResult($creator, $model);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int|string  $modelId  Route identifier of the model to edit.
     * @return \Inertia\Response
     */
    public function edit(Request $request, int|string $modelId)
    {
        $model = $this->getSingleModel($request, $modelId, ($this->modelClass)::$relationsForEditor);
        Gate::authorize('update', $model);
        $data = $this->getResourcesForEditor($request, $model);
        if ($request->has('tab')) {
            $data['tab'] = $request->get('tab');
        }

        return Inertia::render(($this->modelClass)::getVuePath('editor'),
            array_merge([($this->modelClass)::propKey() => $model], $data)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int|string  $modelId  Route identifier of the model to update.
     * @return Response|JsonResponse|RedirectResponse
     */
    public function update(Request $request, int|string $modelId)
    {
        $model = $this->getSingleModel($request, $modelId);
        /** @var AbstractAction $updater */
        $updater = $model->getContractedAction('update');
        if (! method_exists($updater, 'update')) {
            abort(500);
        }
        $model = $updater->update($request->user(), $model, $request->all());

        if ($request->boolean('_autosave')) {
            return response()->json(['saved' => true]);
        }

        return $this->returnResult($updater, $model);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int|string  $modelId  Route identifier of the model to remove.
     * @return \Symfony\Component\HttpFoundation\Response Redirect or API deletion response.
     */
    public function destroy(Request $request, int|string $modelId)
    {
        $model = $this->getSingleModel($request, $modelId);
        /** @var AbstractAction $deleter */
        $deleter = $model->getContractedAction('delete');
        if (! method_exists($deleter, 'delete')) {
            abort(500);
        }
        $result = $deleter->delete($request->user(), $model);

        return $this->returnResult($deleter, $result);
    }

    /**
     * Get request-derived defaults for a new model.
     *
     * @return array<string, mixed> Attribute values supplied to Model::fill().
     */
    protected function preFillNewModel(Request $request): array
    {
        return [];
    }
}
