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


use App\Day;
use App\Liturgy\Agenda;
use App\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Service::class, 'template');
    }


    public function index()
    {
        $templates = Service::orderBy('title')->isTemplate()->get();
        return Inertia::render('Liturgy/Template/Index', compact('templates'));
    }

    public function create()
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

    public function store(Request $request)
    {
        $template = Service::create($this->validateRequest($request));
        $template->update(['slug' => $template->createSlug()]);
        return redirect()->route('liturgy.editor', $template->slug);
    }

    public function update(Request $request, Service $template)
    {
        $template->update($this->validateRequest($request));
        return redirect()->route('template.index');
    }

    public function delete(Service $template)
    {
        $template->delete();
        return redirect()->route('template.index')->with('success', 'Die Vorlage wurde gelöscht.');
    }

    protected function validateRequest(Request $request): array
    {
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
