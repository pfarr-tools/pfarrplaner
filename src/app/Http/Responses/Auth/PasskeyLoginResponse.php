<?php

namespace App\Http\Responses\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/** Redirect successful passkey logins through mandatory account security gates. */
class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    /** Build a response that preserves required password and TOTP recovery flows.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = Auth::guard(config('passkeys.guard'))->user();

        if ($user?->must_change_password) {
            $target = route('password.edit');
        } elseif ($user?->two_factor_recovery_required_at) {
            $target = route('user.two-factor.recovery');
        } else {
            $target = redirect()->intended(config('passkeys.redirect', '/'))->getTargetUrl();
        }

        if ($request->wantsJson()) {
            return response()->json(['redirect' => $target]);
        }

        return redirect()->to($target);
    }
}
