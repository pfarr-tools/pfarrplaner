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

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Controller for reusable service templates. */
class TemplateController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Service::class, 'template');
    }

    /**
     * Render the list of available service templates.
     *
     * @return Response Inertia response with all templates ordered by title.
     */
    public function index(): Response
    {
        $templates = Service::orderBy('title')->isTemplate()->get();

        return Inertia::render('Liturgy/Template/Index', compact('templates'));
    }

    /**
     * Create a new template and open it in the liturgy editor.
     *
     * @return RedirectResponse Redirect to the liturgy editor for the new template.
     */
    public function create(): RedirectResponse
    {
        $template = Service::create([
            'date' => Carbon::parse('1978-03-05 0:00:00'),
            'title' => 'Neue Vorlage',
            'description' => '',
            'special_location' => '',
        ]
        );
        $template->update(['slug' => $template->createSlug()]);

        return redirect()->route('liturgy.editor', $template->slug);
    }

    /**
     * Store a template from the validated request and open it in the editor.
     *
     * @param  Request  $request  Request containing the template data.
     * @return RedirectResponse Redirect to the liturgy editor for the new template.
     */
    public function store(Request $request): RedirectResponse
    {
        $template = Service::create($this->validateRequest($request));
        $template->update(['slug' => $template->createSlug()]);

        return redirect()->route('liturgy.editor', $template->slug);
    }

    /**
     * Update an existing template from the validated request.
     *
     * @param  Request  $request  Request containing the updated template data.
     * @param  Service  $template  Template to update.
     * @return RedirectResponse Redirect to the template list.
     */
    public function update(Request $request, Service $template): RedirectResponse
    {
        $template->update($this->validateRequest($request));

        return redirect()->route('template.index');
    }

    /**
     * Copy a service including its liturgy blocks into a new template.
     *
     * @param  Service  $service  Service to save as a template.
     * @return RedirectResponse Redirect to the liturgy editor for the new template.
     */
    public function saveAsTemplate(Service $service): RedirectResponse
    {
        $this->authorize('create', Service::class);
        $template = Service::create([
            'date' => Carbon::parse('1978-03-05 0:00:00'),
            'title' => $service->title ?: 'Neue Vorlage',
            'description' => '',
            'special_location' => '',
        ]);
        $template->update(['slug' => $template->createSlug()]);

        $service->load('liturgyBlocks.items');
        $ct = 0;
        foreach ($service->liturgyBlocks as $sourceBlock) {
            $ct++;
            $newBlock = $sourceBlock->replicate();
            $newBlock->sortable = $ct;
            $newBlock->service_id = $template->id;
            $newBlock->save();
            $itemCtr = 0;
            foreach ($sourceBlock->items as $sourceItem) {
                $itemCtr++;
                $newItem = $sourceItem->replicate();
                $newItem->liturgy_block_id = $newBlock->id;
                $newItem->sortable = $itemCtr;
                $newItem->save();
            }
        }

        return redirect()->route('liturgy.editor', $template->slug);
    }

    /**
     * Duplicate a template including its liturgy blocks.
     *
     * @param  Service  $template  Template to duplicate.
     * @return RedirectResponse Redirect to the liturgy editor for the copy.
     */
    public function duplicate(Service $template): RedirectResponse
    {
        $this->authorize('create', Service::class);
        $newTemplate = Service::create([
            'date' => Carbon::parse('1978-03-05 0:00:00'),
            'title' => $template->title.' (Kopie)',
            'description' => $template->description,
            'special_location' => $template->special_location,
            'internal_remarks' => $template->internal_remarks,
        ]);
        $newTemplate->update(['slug' => $newTemplate->createSlug()]);

        $template->load('liturgyBlocks.items');
        $ct = 0;
        foreach ($template->liturgyBlocks as $sourceBlock) {
            $ct++;
            $newBlock = $sourceBlock->replicate();
            $newBlock->sortable = $ct;
            $newBlock->service_id = $newTemplate->id;
            $newBlock->save();
            $itemCtr = 0;
            foreach ($sourceBlock->items as $sourceItem) {
                $itemCtr++;
                $newItem = $sourceItem->replicate();
                $newItem->liturgy_block_id = $newBlock->id;
                $newItem->sortable = $itemCtr;
                $newItem->save();
            }
        }

        return redirect()->route('liturgy.editor', $newTemplate->slug);
    }

    /**
     * Delete a template.
     *
     * @param  Service  $template  Template to delete.
     * @return RedirectResponse Redirect to the template list with a success message.
     */
    public function delete(Service $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('template.index')->with('success', 'Die Vorlage wurde gelöscht.');
    }

    /**
     * Validate the template form input and assign the template reference date.
     *
     * @param  Request  $request  Request containing the template form data.
     * @return array<string, mixed> Validated template data including the reference date.
     */
    protected function validateRequest(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate(
            [
                'title' => 'required|string',
                'description' => 'nullable|string',
                'special_location' => 'nullable|string',
                'internal_remarks' => 'nullable|string',
            ]
        );
        $data['date'] = Carbon::parse('1978-03-05 0:00:00');

        return $data;
    }
}
