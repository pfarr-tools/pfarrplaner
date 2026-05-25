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

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\BlockDevHelperRoutes;
use App\Http\Middleware\Cors;
use App\Http\Middleware\ForceDomain;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

setlocale(LC_ALL, 'de_DE.utf8');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(commands: __DIR__.'/../routes/console.php')
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware appended to every request
        $middleware->append([
            BlockDevHelperRoutes::class,
            ForceDomain::class,
            TrustProxies::class,
        ]);

        // CSRF exceptions (replaces App\Http\Middleware\VerifyCsrfToken::$except)
        $middleware->validateCsrfTokens(except: [
            '/livechat/message/*',
        ]);

        // Add Inertia to the web group
        $middleware->web(append: [HandleInertiaRequests::class]);

        // Add Sanctum stateful handling to api group
        $middleware->api(prepend: [EnsureFrontendRequestsAreStateful::class]);

        // Custom extranet middleware group (Sanctum-authenticated API)
        $middleware->group('extranet', [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:600,1',
            SubstituteBindings::class,
            'auth:sanctum',
        ]);

        // Middleware aliases
        $middleware->alias([
            'auth'     => Authenticate::class,
            'can'      => Authorize::class,
            'guest'    => RedirectIfAuthenticated::class,
            'verified' => EnsureEmailIsVerified::class,
            'cors'     => Cors::class,
            'csrf'     => VerifyCsrfToken::class,
        ]);

        // Middleware execution priority
        $middleware->priority([
            StartSession::class,
            ShareErrorsFromSession::class,
            Authenticate::class,
            AuthenticateSession::class,
            SubstituteBindings::class,
            Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport([
            NotFoundHttpException::class,
            AuthenticationException::class,
            ValidationException::class,
        ]);
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation']);
    })
    ->create();
