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

namespace Tests\Builder;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityRouteAuditTest extends TestCase
{
    public function testDebugRoutesAreNotRegistered(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri())->values();

        foreach (['_debug/needs-csrf', '_debug/form', 'test-node', 'panic', 'dash'] as $uri) {
            $this->assertFalse($uris->contains($uri), sprintf('Debug route "%s" must not be registered.', $uri));
        }

        $this->assertFalse($uris->contains('dgm'));
        $this->assertFalse($uris->contains('dgm/auth'));
        $this->assertFalse($uris->contains('ping/update'));
    }

    public function testBoostAndTelescopeRoutesStayDisabledByDefault(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri())->values();

        $this->assertCount(0, $uris->filter(fn ($uri) => str_starts_with($uri, '_boost')));
        $this->assertCount(0, $uris->filter(fn ($uri) => str_starts_with($uri, 'telescope')));
    }

    public function testOnlyApprovedPublicMutationRoutesExist(): void
    {
        $allowed = collect([
            '.well-known/caldav',
            'csrf-cookie',
            'anfrage/{ministry}/{user}/{services}/{sender?}',
            'dimissoriale/{type}/{id}',
            'kontaktformular',
            'liturgie/{service:slug}/download/{key}',
            '_ignition/execute-solution',
            '_ignition/update-config',
        ])->sort()->values()->all();

        Artisan::call('route:list', ['--json' => true]);
        $routes = collect(json_decode(Artisan::output(), true));

        $actual = $routes
            ->filter(function ($route) {
                $methods = collect(explode('|', $route['method']));
                if ($methods->intersect(['POST', 'PUT', 'PATCH', 'DELETE'])->isEmpty()) {
                    return false;
                }

                return !$this->hasAuthenticationMiddleware(collect($route['middleware'] ?? []));
            })
            ->map(fn ($route) => $route['uri'])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame($allowed, $actual);
    }

    public function testSensitiveStateChangesAreNotExposedViaGetRoutes(): void
    {
        Artisan::call('route:list', ['--json' => true]);
        $routes = collect(json_decode(Artisan::output(), true));

        $sensitiveUris = collect([
            'apiToken',
            'logout',
            'patch/{patch}',
            'admin/benutzer/login-als/{user}',
            'benutzer/zurueck-zu-admin',
        ]);

        $violations = $routes
            ->filter(function ($route) use ($sensitiveUris) {
                return $sensitiveUris->contains($route['uri'])
                    && collect(explode('|', $route['method']))->contains('GET');
            })
            ->pluck('uri')
            ->values()
            ->all();

        $this->assertSame([], $violations);
    }

    public function testVacationEmbedRequiresAuthentication(): void
    {
        Artisan::call('route:list', ['--json' => true]);
        $routes = collect(json_decode(Artisan::output(), true));

        $route = $routes->firstWhere('uri', 'user/embed/vacations/{user}');

        $this->assertNotNull($route);
        $this->assertTrue($this->hasAuthenticationMiddleware(collect($route['middleware'] ?? [])));
    }

    public function testDavRoutesDoNotUseCsrfProtection(): void
    {
        $route = Route::getRoutes()->getByName('sabre.dav');

        $this->assertNotNull($route);
        $this->assertContains(
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            $route->excludedMiddleware()
        );
    }

    protected function hasAuthenticationMiddleware(Collection $middleware): bool
    {
        return $middleware->contains(function ($item) {
            return str_contains($item, 'Authenticate')
                || str_contains($item, 'auth')
                || str_contains($item, 'auth:api')
                || str_contains($item, 'auth:sanctum');
        });
    }
}
