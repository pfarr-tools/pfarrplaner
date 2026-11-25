<?php

namespace App\Http\Middleware;

use App\Models\People\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restrict recovered accounts to TOTP reenrolment routes. */
class RequireTwoFactorRecovery
{
    /** Enforce the recovery gate for authenticated web and API requests. */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()
            ?? $request->user('api')
            ?? $request->user('sanctum');
        if (! $user instanceof User || ! $user->two_factor_recovery_required_at) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        if ($this->isAllowedRoute($request->route()?->getName())) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        if ($request->expectsJson() || $request->is('api/*') || $request->is('extranet/*')) {
            abort(Response::HTTP_FORBIDDEN, 'TOTP muss nach der Kontowiederherstellung neu eingerichtet werden.');
        }

        return redirect()->route('user.two-factor.recovery');
    }

    /** Allow only the dedicated setup, TOTP operations, and logout. */
    private function isAllowedRoute(?string $routeName): bool
    {
        return in_array($routeName, [
            'user.two-factor.recovery',
            'user.two-factor.start',
            'user.two-factor.confirm',
            'logout',
            'user.switchback',
            'csrf.keepalive',
        ], true);
    }
}
