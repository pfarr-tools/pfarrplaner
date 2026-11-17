<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Reject missing, expired, cross-session, or wrong-purpose passkey login challenges. */
class RequireFreshPasskeyLoginChallenge
{
    /** Enforce a one-minute, session-bound login ceremony. */
    public function handle(Request $request, Closure $next): Response
    {
        $challenge = $request->session()->get('passkey.login.challenge');

        if (! is_array($challenge)
            || ($challenge['purpose'] ?? null) !== 'login'
            || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $this->forgetChallenge($request);

            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Passkey-Anfrage abgelaufen. Bitte erneut versuchen.');
        }

        try {
            return $next($request);
        } finally {
            $this->forgetChallenge($request);
        }
    }

    /** Remove both metadata and the package's one-use WebAuthn options. */
    private function forgetChallenge(Request $request): void
    {
        $request->session()->forget([
            'passkey.login.challenge',
            'passkey.verification_options',
        ]);
    }
}
