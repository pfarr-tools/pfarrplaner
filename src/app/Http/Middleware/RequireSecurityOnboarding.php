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
 */

namespace App\Http\Middleware;

use App\Models\People\User;
use App\Services\SecurityOnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restrict passwordless accounts without an extra factor to security onboarding. */
class RequireSecurityOnboarding
{
    /**
     * Redirect eligible accounts unless the request is for setup, logout, or deferral.
     *
     * @param  Request  $request  Current HTTP request.
     * @param  Closure  $next  Next middleware in the pipeline.
     * @return Response The gate redirect or the downstream response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $this->isAllowedRoute($request->route()?->getName())) {
            return $next($request);
        }

        $onboarding = app(SecurityOnboardingService::class);
        if (! $onboarding->shouldShow($user)) {
            return $next($request);
        }

        return redirect()->route('security.onboarding');
    }

    /**
     * Identify requests required to enroll a factor, defer, or leave the session.
     *
     * @param  string|null  $routeName  Current route name.
     * @return bool Whether the route must remain accessible.
     */
    private function isAllowedRoute(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        return in_array($routeName, [
            'security.onboarding',
            'security.onboarding.later',
            'logout',
            'user.two-factor.start',
            'user.two-factor.confirm',
            'user.two-factor.recovery',
        ], true) || str_starts_with($routeName, 'user.passkeys.');
    }
}
