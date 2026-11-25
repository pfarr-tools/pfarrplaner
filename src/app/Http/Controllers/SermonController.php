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

use App\Models\Sermon;
use App\Models\Service;
use App\Traits\HandlesAttachedImageTrait;
use App\Traits\HandlesAttachmentsTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Controller for sermon editing. */
class SermonController extends Controller
{
    use HandlesAttachedImageTrait;
    use HandlesAttachmentsTrait;

    /** @var class-string<Sermon> Model class used by the shared attachment traits. */
    protected string $model = Sermon::class;

    /**
     *   construct.
     *
     * @return mixed
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Render the sermon editor for a service and its associated sermons.
     */
    public function editorByService(Service $service): Response
    {
        Gate::authorize('update', $service);
        $sermon = $service->sermon;
        if ($sermon) {
            $services = $sermon->services;
        } else {
            $services = collect([$service]);
        }

        return Inertia::render('sermonEditor', compact('services', 'sermon', 'service'));
    }

    /**
     * Render the sermon editor for an existing sermon.
     */
    public function editor(Sermon $sermon): Response
    {
        $this->authorizeSermonUpdate($sermon);
        $services = $sermon->services;
        $service = $services->first();

        return Inertia::render('sermonEditor', compact('services', 'sermon', 'service'));
    }

    /**
     * Store a sermon from the validated request and link it to the service.
     */
    public function store(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);
        $data = $this->validateRequest($request);
        $sermon = Sermon::create($data);
        $this->handleIndividualAttachment($request, $sermon, 'image');
        if ($sermon !== null) {
            $service->update(['sermon_id' => $sermon->id]);

            return redirect()->route('sermon.editor', $sermon->id);
        }

        return redirect()->back();
    }

    /**
     * Update a sermon with the validated request data.
     */
    public function update(Request $request, Sermon $sermon): RedirectResponse
    {
        $this->authorizeSermonUpdate($sermon);
        $data = $this->validateRequest($request);
        $sermon->update($data);
        $this->handleIndividualAttachment($request, $sermon, 'image');

        return redirect()->route('sermon.editor', $sermon);
    }

    /**
     * Detach a sermon from a service and delete it when no services remain.
     */
    public function uncouple(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);
        if ($service->sermon === null) {
            abort(404);
        }
        /** @var Sermon $sermon */
        $sermon = $service->sermon;
        $service->update(['sermon_id' => null]);
        $sermon->refresh();
        if (count($sermon->services) == 0) {
            $sermon->delete();

            return redirect()->route('liturgy.editor', $service->slug);
        } else {
            return redirect()->route('sermon.editor', $sermon->id);
        }
    }

    /**
     * Render the reader view for a sermon.
     */
    public function reader(Sermon $sermon): Response
    {
        $this->authorizeSermonUpdate($sermon);

        return Inertia::render('Sermon/Reader', compact('sermon'));
    }

    /**
     * Render the reader view for the sermon attached to a service.
     */
    public function readerByService(Service $service): Response
    {
        Gate::authorize('update', $service);
        if (! $service->sermon_id) {
            abort(404);
        }

        $sermon = $service->sermon;
        abort_unless($sermon !== null, 404);

        return $this->reader($sermon);
    }

    /**
     * Normalize checkbox fields so that unchecked boxes are stored as false.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     * @param  list<string>  $keys  Request keys that represent checkboxes.
     * @return array<string, mixed> The request data with normalized checkbox values.
     */
    protected function handleCheckBoxes(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if ($data[$key] !== null) {
                $data[$key] = (bool) $data[$key];
            }
        }

        return $data;
    }

    /**
     * Validate the sermon form input and normalize checkbox values.
     *
     * @return array<string, mixed> The validated request data.
     */
    protected function validateRequest(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate(
            [
                'title' => 'nullable|string',
                'subtitle' => 'nullable|string',
                'reference' => 'nullable|string',
                'series' => 'nullable|string',
                'summary' => 'nullable|string',
                'text' => 'nullable|string',
                'notes_header' => 'nullable|string',
                'key_points' => 'nullable|string',
                'questions' => 'nullable|string',
                'literature' => 'nullable|string',
                'audio_recording' => 'nullable|string',
                'video_url' => 'nullable|string',
                'external_url' => 'nullable|string',
                'cc_license' => 'nullable',
                'permit_handouts' => 'nullable',
            ]
        );
        $data = $this->handleCheckBoxes($data, ['cc_license', 'permit_handouts']);

        return $data;
    }

    /**
     * Authorizesermonupdate.
     */
    protected function authorizeSermonUpdate(Sermon $sermon): void
    {
        $sermon->loadMissing('services');
        abort_if($sermon->services->isEmpty(), 403);
        foreach ($sermon->services as $service) {
            Gate::authorize('update', $service);
        }
    }
}
