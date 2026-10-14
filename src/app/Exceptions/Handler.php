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

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        NotFoundHttpException::class,
        AuthenticationException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {

    }

    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        // Danach: 419 zuverlässig loggen (egal wie es entstanden ist)
        try {
            if ((int) $response->getStatusCode() === 419) {
                $route = $request->route();

                Log::channel('csrf419')->warning('HTTP 419 rendered', [
                    'time' => now()->toIso8601String(),

                    // Request basics
                    'method' => $request->method(),
                    'full_url' => $request->fullUrl(),
                    'path' => $request->path(),

                    // Route info (falls vorhanden)
                    'route_name' => optional($route)->getName(),
                    'route_uri' => optional($route)->uri(),

                    // Exception context
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'is_token_mismatch' => $e instanceof TokenMismatchException,

                    // Client context
                    'user_id' => optional($request->user())->id,
                    'ip' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'referer' => $request->headers->get('referer'),
                    'origin' => $request->headers->get('origin'),

                    // Cookie/Header presence (keine Werte!)
                    'cookie_session_present' => $request->cookies->has(config('session.cookie')),
                    'cookie_xsrf_present' => $request->cookies->has('XSRF-TOKEN'),
                    'xsrf_header_present' =>
                        $request->headers->has('X-XSRF-TOKEN') || $request->headers->has('X-CSRF-TOKEN'),

                    // HTTPS/Proxy hints
                    'is_secure' => $request->isSecure(),
                    'scheme' => $request->getScheme(),
                    'forwarded_proto' => $request->headers->get('x-forwarded-proto'),
                    'forwarded_for' => $request->headers->get('x-forwarded-for'),

                    // Inertia/JSON hints
                    'expects_json' => $request->expectsJson(),
                    'is_inertia' => $request->headers->has('X-Inertia'),
                ]);
            }
        } catch (Throwable $logError) {
            // Logging darf nie das Rendern kaputtmachen
            Log::error('Failed to write csrf419 log in Handler::render', [
                'logger_error' => get_class($logError) . ': ' . $logError->getMessage(),
            ]);
        }

        return $response;
    }

}
