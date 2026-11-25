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

use App\Facades\Settings;
use App\Models\People\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Controller for application settings. */
class SettingsController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Speichert eine Einstellung des Benutzers und antwortet je nach Inertia-Header mit Redirect oder JSON.
     *
     * @param  Request  $request  Anfrage mit dem zu speichernden Wert.
     * @param  User  $user  Benutzer, dessen Einstellung gespeichert wird.
     * @param  string  $key  Schlüssel der zu speichernden Einstellung.
     * @return JsonResponse|RedirectResponse JSON-Antwort mit der gespeicherten Einstellung oder Redirect zurück.
     */
    public function set(Request $request, User $user, string $key): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $user);
        /** @var array<string, mixed> $data */
        $data = $request->validate(['value' => 'nullable']);
        $userSetting = Settings::set($user, $key, $data['value']);

        if ($request->header('x-inertia')) {
            return redirect()->back();
        } else {
            return response()->json($userSetting->only(['key', 'value']), 200);
        }
    }
}
