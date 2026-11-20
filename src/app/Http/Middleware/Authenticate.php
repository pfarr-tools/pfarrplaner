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

namespace App\Http\Middleware;

use App\Models\People\User;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Authenticate
 */
class Authenticate extends Middleware
{
    /**
     * Authenticate first, then reject accounts that are locked or removed.
     *
     * @param  Request  $request  Current HTTP request.
     * @param  Closure  $next  Next middleware in the request pipeline.
     * @param  string  ...$guards  Authentication guards for this route.
     * @return Response Response from the next middleware or login redirect.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);
        $authenticatedUser = $request->user();
        $user = $authenticatedUser instanceof User
            ? User::query()->find($authenticatedUser->getKey())
            : null;
        $routeName = $request->route()?->getName();
        $mayExit = in_array($routeName, ['logout', 'user.switchback'], true);

        if ($authenticatedUser instanceof User
            && (! ($user instanceof User) || ! $user->isOfficialUser || $user->isLocked)
            && ! $mayExit) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('extranet/*')) {
                abort(Response::HTTP_FORBIDDEN, 'Dieses Benutzerkonto ist gesperrt oder nicht mehr aktiv.');
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Dieses Benutzerkonto ist gesperrt oder nicht mehr aktiv.');
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
