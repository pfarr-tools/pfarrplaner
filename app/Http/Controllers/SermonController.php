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

use App\Models\Sermon;
use App\Models\Service;
use App\Traits\HandlesAttachedImageTrait;
use App\Traits\HandlesAttachmentsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SermonController extends Controller
{

    use HandlesAttachmentsTrait;
    use HandlesAttachedImageTrait;

    protected $model = Sermon::class;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function editorByService(Service $service)
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

    public function editor(Sermon $sermon)
    {
        $this->authorizeSermonUpdate($sermon);
        $services = $sermon->services;
        $service = $services->first();
        return Inertia::render('sermonEditor', compact('services', 'sermon', 'service'));
    }

    public function store(Request $request, Service $service)
    {
        Gate::authorize('update', $service);
        $data = $this->validateRequest($request);
        $sermon = Sermon::create($data);
        $this->handleIndividualAttachment($request, $sermon, 'image');
        if (null !== $sermon) {
            $service->update(['sermon_id' => $sermon->id]);
            return redirect()->route('sermon.editor', $sermon->id);
        }
        return redirect()->back();
    }

    public function update(Request $request, Sermon $sermon)
    {
        $this->authorizeSermonUpdate($sermon);
        $data = $this->validateRequest($request);
        $sermon->update($data);
        $this->handleIndividualAttachment($request, $sermon, 'image');
        return redirect()->route('sermon.editor', $sermon);
    }

    public function uncouple(Request $request, Service $service)
    {
        Gate::authorize('update', $service);
        if (null === $service->sermon) abort(404);
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

    public function reader(Sermon $sermon)
    {
        $this->authorizeSermonUpdate($sermon);
        return Inertia::render('Sermon/Reader', compact('sermon'));
    }

    public function readerByService(Service $service)
    {
        Gate::authorize('update', $service);
        if (!$service->sermon_id) abort(404);
        return $this->reader($service->sermon);
    }

    protected function handleCheckBoxes($data, $keys) {
        foreach ($keys as $key) {
            if (null !== $data[$key]) $data[$key] = (bool)$data[$key];
        }
        return $data;
    }

    protected function validateRequest(Request $request)
    {
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

    protected function authorizeSermonUpdate(Sermon $sermon): void
    {
        $sermon->loadMissing('services');
        abort_if($sermon->services->isEmpty(), 403);
        foreach ($sermon->services as $service) {
            Gate::authorize('update', $service);
        }
    }
}
