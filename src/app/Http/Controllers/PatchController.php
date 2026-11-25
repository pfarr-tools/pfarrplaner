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

use App\Models\People\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Http\Request;

class PatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Führt einen Wartungspatch über seinen Routennamen aus.
     *
     * @param  Request  $request  Anfrage mit dem angemeldeten Benutzer.
     * @param  string  $patch  Patchname aus der Route, z. B. `beispiel` für `BeispielPatch`.
     */
    public function patch(Request $request, string $patch): void
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(
            $user->hasRole(AuthServiceProvider::ADMIN)
            || $user->hasRole(AuthServiceProvider::SUPER),
            403
        );

        $patchClass = '\\App\\Maintenance\\Patches\\'.ucfirst($patch).'Patch';
        if (class_exists($patchClass)) {
            $patch = new $patchClass;
            if (is_callable([$patch, 'patch'])) {
                call_user_func([$patch, 'patch'], $request);
            }
        }
    }
}
