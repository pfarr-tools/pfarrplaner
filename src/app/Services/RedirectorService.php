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

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

/** Service for storing and restoring the current route. */
class RedirectorService
{
    /**
     * Store the current URL as the route to return to.
     *
     * @return void
     */
    public static function saveCurrentRoute(): void
    {
        self::setReturnRoute(Request::url());
    }

    /**
     * Store the URL to return to.
     *
     * @param  string  $route  URL that later redirects should use.
     * @return void
     */
    public static function setReturnRoute(string $route): void
    {
        Session::put('returnRoute', $route);
    }

    /**
     * Redirect to the stored return route.
     *
     * @return RedirectResponse Redirect response to the stored route.
     */
    public static function back(): RedirectResponse
    {
        return redirect(self::backRoute());
    }

    /**
     * Get the stored return route.
     *
     * @return string URL to return to, falling back to the home route.
     */
    public static function backRoute(): string
    {
        return Session::get('returnRoute', route('home'));
    }
}
