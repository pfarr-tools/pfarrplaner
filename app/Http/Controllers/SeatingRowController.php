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

use App\Models\Location;
use App\Models\Seating\SeatingRow;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeatingRowController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Location $location)
    {
        $seatingRow = (new SeatingRow([
                                          'title' => '',
                                          'seats' => '',
                                          'split' => '',
                                          'seating_section_id' => $location->seatingSections->first()->id
                                      ]))->load('seatingSection');
        return Inertia::render('Admin/Location/SeatingRowEditor', compact('seatingRow', 'location'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $seatingRow = SeatingRow::create($this->validateRequest($request));
        return redirect()->route(
            'location.edit',
            ['location' => $seatingRow->seatingSection->location, 'tab' => 'seating']
        );
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Seating\SeatingRow $seatingRow
     * @return \Illuminate\Http\Response
     */
    public function show(SeatingRow $seatingRow)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Seating\SeatingRow $seatingRow
     * @return \Inertia\Response
     */
    public function edit(SeatingRow $seatingRow)
    {
        $seatingRow->load('seatingSection');
        $location = $seatingRow->seatingSection->location;
        return Inertia::render('Admin/Location/SeatingRowEditor', compact('seatingRow', 'location'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Seating\SeatingRow $seatingRow
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SeatingRow $seatingRow)
    {
        $seatingRow->update($this->validateRequest($request));
        return redirect()->route(
            'location.edit',
            ['location' => $seatingRow->seatingSection->location, 'tab' => 'seating']
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Seating\SeatingRow $seatingRow
     * @return \Illuminate\Http\Response
     */
    public function destroy(SeatingRow $seatingRow)
    {
        $seatingSection = $seatingRow->seatingSection;
        $seatingRow->delete();
        return redirect()->route('location.edit', ['location' => $seatingSection->location, 'tab' => 'seating']);
    }

    /**
     * Validate and add sensible defaults
     * @param Request $request
     * @return array
     */
    protected function validateRequest(Request $request)
    {
        $data = $request->validate(
            [
                'seating_section_id' => 'required|int|exists:seating_sections,id',
                'title' => 'required|regex:/[0-9]+/i',
                'divides_into' => 'nullable|int',
                'seats' => 'nullable|int',
                'spacing' => 'nullable|int',
                'split' => 'nullable|string|regex:/^((\d+)(,\s*\d+)+)$/i',
                'color' => 'nullable|string',
            ]
        );
        if (is_numeric($data['title'])) {
            $data['title'] = str_pad($data['title'], 2, 0, STR_PAD_LEFT);
        }
        $data['divides_into'] = $data['divides_into'] ?? 1;
        $data['seats'] = $data['seats'] ?? 1;
        $data['spacing'] = $data['spacing'] ?? 0;
        $data['split'] = str_replace(' ', '', $data['split']);
        return $data;
    }
}
