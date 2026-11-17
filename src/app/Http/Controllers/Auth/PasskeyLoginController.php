<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/** Provides the discoverable, username-independent passkey login ceremony. */
class PasskeyLoginController extends Controller
{
    /** Return generic assertion options with no account-specific credential list. */
    public function options(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $options = $generate();
        $issuedAt = now()->timestamp;
        $request->session()->put('passkey.verification_options', WebAuthn::toJson($options));
        $request->session()->put('passkey.login.challenge', [
            'purpose' => 'login',
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt + 60,
        ]);

        return response()->json(['options' => WebAuthn::toBrowserArray($options)]);
    }

    /** Verify one assertion and complete passwordless login. */
    public function verify(
        PasskeyVerificationRequest $request,
        VerifyPasskey $verify,
    ): Response {
        $challenge = $request->session()->pull('passkey.login.challenge');
        if (! is_array($challenge) || ($challenge['purpose'] ?? null) !== 'login') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Passkey-Anfrage abgelaufen. Bitte erneut versuchen.');
        }

        try {
            $passkey = $verify($request->credential(), $request->verificationOptions());
        } catch (InvalidPasskeyException) {
            return response()->json(['message' => 'Passkey konnte nicht bestätigt werden. Bitte erneut versuchen.'], 422);
        }

        if (! Passkeys::allowsLogin($request, $passkey)) {
            return response()->json(['message' => 'Passkey konnte nicht bestätigt werden. Bitte erneut versuchen.'], 422);
        }

        $guard = Auth::guard(Config::string('passkeys.guard'));
        if (! $guard instanceof StatefulGuard) {
            throw new RuntimeException('Passkeys requires a stateful authentication guard.');
        }

        $guard->login($passkey->user);
        $request->session()->regenerate();

        return app(PasskeyLoginResponse::class)->toResponse($request);
    }
}
