<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Redirect authenticated users with a forced password change to the form. */
class RequirePasswordChange
{
    /**
     * Enforce the password-change interstitial on authenticated web routes.
     *
     * @param  Closure(Request): Response  $next  Next middleware in the pipeline.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password || $this->isExempt($request)) {
            return $next($request);
        }

        return redirect()->route('password.edit', ['from' => $request->route()?->getName()]);
    }

    /** Determine whether the request is part of the password or proxy flow. */
    protected function isExempt(Request $request): bool
    {
        if ($request->session()->has('adminUserSwitchBack')) {
            return true;
        }

        return in_array($request->route()?->getName(), [
            'password.edit',
            'password.change',
            'logout',
            'user.switchback',
            'csrf.keepalive',
        ], true);
    }
}
