<?php

namespace App\Http\Middleware;

use App\Models\People\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Authenticate CalDAV Basic credentials with the dedicated API token, never a legacy password. */
class AuthenticateDavApiToken
{
    /** Require a valid API token for a calendar-enabled official account. */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->getUser();
        $token = $request->getPassword();
        $user = is_string($email) && is_string($token)
            ? User::query()->where('email', $email)
                ->where('is_official_user', true)
                ->whereHas('calendarConnections')
                ->first()
            : null;

        if (! $user || ! $this->matchesApiToken((string) $user->api_token, $token)) {
            return response('Unauthorized', Response::HTTP_UNAUTHORIZED)
                ->header('WWW-Authenticate', 'Basic realm="'.config('app.name').' DAV"');
        }

        Auth::login($user);

        return $next($request);
    }

    /** Compare a supplied token against hashed or legacy raw API-token storage. */
    private function matchesApiToken(string $storedToken, string $providedToken): bool
    {
        if ($storedToken === '') {
            return false;
        }

        return hash_equals($storedToken, hash('sha256', $providedToken))
            || hash_equals($storedToken, $providedToken);
    }
}
