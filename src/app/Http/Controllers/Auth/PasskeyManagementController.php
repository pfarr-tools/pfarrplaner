<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\People\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Http\Requests\PasskeyRegistrationRequest;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Symfony\Component\HttpFoundation\Response;

/** Handles authenticated passkey enrollment, renaming, and revocation. */
class PasskeyManagementController extends Controller
{
    /** List credentials using a safe representation that omits key material. */
    public function index(Request $request): JsonResponse
    {
        $passkeys = $this->authenticatedUser($request)->passkeys()->latest()->get()
            ->map(static fn (Passkey $passkey): array => [
                'id' => $passkey->getKey(),
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'createdAt' => $passkey->created_at?->toIso8601String(),
                'lastUsedAt' => $passkey->last_used_at?->toIso8601String(),
            ])
            ->all();

        return response()->json(['passkeys' => $passkeys]);
    }

    /** Return account-specific registration options after password confirmation. */
    public function options(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $options = $generate($user);
        $issuedAt = now()->timestamp;
        $request->session()->put('passkey.registration_options', WebAuthn::toJson($options));
        $request->session()->put('passkey.registration.challenge', [
            'purpose' => 'registration',
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt + 60,
        ]);

        return response()->json(['options' => WebAuthn::toBrowserArray($options)]);
    }

    /** Verify and store a new credential using the package's WebAuthn validator. */
    public function store(PasskeyRegistrationRequest $request, StorePasskey $store): JsonResponse
    {
        $challenge = $request->session()->pull('passkey.registration.challenge');
        if (! is_array($challenge) || ($challenge['purpose'] ?? null) !== 'registration') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Passkey-Einrichtung abgelaufen. Bitte erneut versuchen.');
        }

        try {
            $passkey = $store(
                $this->authenticatedUser($request),
                $request->validated('name'),
                $request->credential(),
                $request->registrationOptions(),
            );
        } catch (InvalidPasskeyException|ValidationException) {
            return response()->json(['message' => 'Passkey konnte nicht eingerichtet werden. Bitte erneut versuchen.'], 422);
        }

        return response()->json([
            'id' => $passkey->getKey(),
            'name' => $passkey->name,
        ], Response::HTTP_CREATED);
    }

    /** Rename an owned passkey without exposing its credential material. */
    public function rename(Request $request, Passkey $passkey): JsonResponse
    {
        $this->authorizeOwnedPasskey($request, $passkey);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $passkey->update(['name' => $validated['name']]);

        return response()->json(['id' => $passkey->getKey(), 'name' => $passkey->name]);
    }

    /** Revoke an owned passkey. */
    public function destroy(Request $request, Passkey $passkey): Response
    {
        $this->authorizeOwnedPasskey($request, $passkey);
        $passkey->delete();

        return response()->noContent();
    }

    /** Resolve the authenticated application user. */
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        return $user;
    }

    /** Hide whether a foreign credential exists while enforcing ownership. */
    private function authorizeOwnedPasskey(Request $request, Passkey $passkey): void
    {
        $user = $this->authenticatedUser($request);
        abort_unless((string) $passkey->user_id === (string) $user->getKey(), Response::HTTP_NOT_FOUND);
    }
}
