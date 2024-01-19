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

use App\Calendars\Exchange\ExchangeCalendar;
use App\Models\Calendar\External\CalendarConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalendarConnectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function exchangeCalendars(Request $request)
    {
        $data = $request->validate(['credentials1' => 'required|string', 'credentials2' => 'required|string']);

        $exchange = new ExchangeCalendar(
            'outlook.office365.com',
            $data['credentials1'],
            $data['credentials2'],
            '2010_SP2',
            ''
        );
        $folders = $exchange->getAllCalendars();
        return response()->json($folders);
    }

    /**
     * Display a listing of the resource.
     *
     * @return RedirectResponse
     */
    public function index()
    {
        return redirect(route('user.profile', ['tab' => 'calendars']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $calendarConnection = CalendarConnection::create(
            [
                'user_id' => Auth::user()->id,
                'title' => 'Neue Kalenderverbindung',
                'credentials1' => Auth::user()->email ?? '',
                'credentials2' => '',
                'connection_string' => '',
                'connection_type' => CalendarConnection::CONNECTION_TYPE_OWN,
            ]
        );
        $calendarConnection->update(['title' => 'Neue Kalenderverbindung #' . $calendarConnection->id]);
        return redirect()->route('calendarConnection.edit', $calendarConnection->id);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Calendar\External\CalendarConnection $calendarConnection
     * @return \Illuminate\Http\Response
     */
    public function show(CalendarConnection $calendarConnection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Calendar\External\CalendarConnection $calendarConnection
     * @return \Inertia\Response
     */
    public function edit(CalendarConnection $calendarConnection)
    {
        $cities = Auth::user()->cities;
        return Inertia::render('CalendarConnections/Setup', compact('calendarConnection', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Calendar\External\CalendarConnection $calendarConnection
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CalendarConnection $calendarConnection)
    {
        $data = $this->validateRequest($request);
        $calendarConnection->update($data);
        $calendarConnection->cities()->sync($request->get('cities') ?? []);

        return redirect()->route('calendarConnection.index');
    }

    /**
     * Resync entire calendar
     *
     * @param CalendarConnection $calendarConnection
     * @return \Illuminate\Http\RedirectResponse
     * @return void
     */
    public function resync(CalendarConnection $calendarConnection)
    {
        return redirect()->route('calendarConnection.index')->with(
            'info',
            'Der Kalender wird neu synchronisiert. Es kann eine Weile dauern, bis alle Daten zum externen Kalender übertragen sind. Dieser
            Prozess läuft im Hintergrund ab. Du kannst solange ganz normal weiterarbeiten.'
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Calendar\External\CalendarConnection $calendarConnection
     * @return \Illuminate\Http\Response
     */
    public function destroy(CalendarConnection $calendarConnection)
    {
        $calendarConnection->delete();
        return redirect()->route('calendarConnection.index');
    }

    protected function validateRequest(Request $request)
    {
        return $request->validate(
            [
                'user_id' => 'required|int|exists:users,id',
                'title' => 'required|string',
                'include_hidden' => 'nullable|int',
                'include_alternate' => 'nullable|int',
                'include_vacations' => 'nullable|int',
                'include_rite_anniversaries' => 'nullable|int',
            ]
        );
    }
}
