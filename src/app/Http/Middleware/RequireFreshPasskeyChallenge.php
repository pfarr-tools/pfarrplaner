<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Reject missing, expired, or wrong-purpose passkey ceremony challenges. */
class RequireFreshPasskeyChallenge
{
    /** Enforce a one-minute, session-bound ceremony for the requested purpose. */
    public function handle(Request $request, Closure $next, string $purpose): Response
    {
        $challengeKey = 'passkey.'.$purpose.'.challenge';
        $optionsKey = match ($purpose) {
            'login' => 'passkey.verification_options',
            'registration' => 'passkey.registration_options',
            default => null,
        };
        if ($optionsKey === null) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $challenge = $request->session()->get($challengeKey);

        if (! is_array($challenge)
            || ($challenge['purpose'] ?? null) !== $purpose
            || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $this->forgetChallenge($request, $challengeKey, $optionsKey);

            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Passkey-Anfrage abgelaufen. Bitte erneut versuchen.');
        }

        try {
            return $next($request);
        } finally {
            $this->forgetChallenge($request, $challengeKey, $optionsKey);
        }
    }

    /** Remove both metadata and the package's one-use WebAuthn options. */
    private function forgetChallenge(Request $request, string $challengeKey, string $optionsKey): void
    {
        $request->session()->forget([$challengeKey, $optionsKey]);
    }
}
