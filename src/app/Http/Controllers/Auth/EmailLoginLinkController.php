<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\People\User;
use App\Services\SecurityOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Manage passwordless email login links and their one-time nonce. */
class EmailLoginLinkController extends Controller
{
    private const GENERIC_STATUS = 'Wenn die Adresse zu einem Konto gehört, das sich anmelden kann, senden wir einen Anmeldelink. Bitte prüfen Sie Ihr Postfach.';

    /**
     * Send a generic passwordless sign-in response for known and unknown addresses.
     *
     * @param  Request  $request  Current login-link request.
     * @return RedirectResponse Redirect to the login page with a generic status message.
     */
    public function request(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'string', 'email', 'max:254']]);
        $email = mb_strtolower(trim($validated['email']));
        $emailKey = 'login-link-email:'.hash_hmac('sha256', $email, (string) config('app.key'));

        if (! RateLimiter::tooManyAttempts($emailKey, 3)) {
            RateLimiter::hit($emailKey, 300);
            $request->attributes->set('passwordless_login_link_email', $email);
        }

        return redirect()->route('login')->with('status', self::GENERIC_STATUS);
    }

    /**
     * Validate a nonce without consuming it, then redirect away from its URL.
     *
     * @param  Request  $request  Current login-link request.
     * @param  string  $token  One-time nonce from the email link.
     * @return RedirectResponse Redirect to the login page or the confirmation form.
     */
    public function open(Request $request, string $token): RedirectResponse
    {
        $link = strlen($token) === 64
            ? DB::table('passwordless_login_links')->where('token_hash', hash('sha256', $token))
                ->whereNull('consumed_at')->where('expires_at', '>', now())->first()
            : null;

        if (! $link) {
            return redirect()->route('login')->with('status', 'Dieser Anmeldelink ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.');
        }

        $request->session()->put('login.link.confirmation_id', $link->id);

        return redirect()->route('login.link.confirm-form');
    }

    /** Show a token-free confirmation page after an email link was opened. */
    public function showConfirmation(Request $request): HttpResponse
    {
        $linkId = $request->session()->get('login.link.confirmation_id');
        $valid = $linkId && DB::table('passwordless_login_links')->where('id', $linkId)
            ->whereNull('consumed_at')->where('expires_at', '>', now())->exists();
        if (! $valid) {
            $request->session()->forget('login.link.confirmation_id');

            return redirect()->route('login')->with('status', 'Dieser Anmeldelink ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.');
        }

        $response = Inertia::render('Auth/ConfirmLoginLink')->toResponse($request);
        $response->headers->add([
            'Cache-Control' => 'must-revalidate, no-cache, no-store, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
        ]);

        return $response;
    }

    /**
     * Atomically consume the confirmed nonce and start login or the TOTP challenge.
     *
     * @param  Request  $request  Current login-link confirmation request.
     * @param  SecurityOnboardingService  $onboarding  Login reminder tracker.
     * @return RedirectResponse Redirect to the TOTP challenge or application destination.
     */
    public function confirm(Request $request, SecurityOnboardingService $onboarding): RedirectResponse
    {
        $linkId = $request->session()->get('login.link.confirmation_id');
        $result = DB::transaction(function () use ($linkId): array {
            $link = $linkId ? DB::table('passwordless_login_links')->where('id', $linkId)->lockForUpdate()->first() : null;
            if (! $link || $link->consumed_at || now()->greaterThanOrEqualTo($link->expires_at)) {
                return ['state' => 'invalid'];
            }

            $user = User::query()->lockForUpdate()->whereKey($link->user_id)->first();
            if (! $user || ! $user->isOfficialUser || $user->isLocked) {
                DB::table('passwordless_login_links')->where('id', $link->id)->update([
                    'consumed_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['state' => 'invalid'];
            }

            DB::table('passwordless_login_links')->where('id', $link->id)->update([
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);

            return ['state' => $user->hasTwoFactorEnabled() ? 'challenge' : 'authenticated', 'user' => $user];
        });

        $request->session()->forget('login.link.confirmation_id');
        if ($result['state'] === 'invalid') {
            return back()->withErrors(['link' => 'Dieser Anmeldelink ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.']);
        }

        /** @var User $user */
        $user = $result['user'];
        if ($result['state'] === 'challenge') {
            $request->session()->put('login.challenge', [
                'user_id' => $user->getKey(),
                'remember' => false,
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]);
            $request->session()->regenerate();

            return redirect()->route('login.challenge');
        }

        $request->session()->regenerate();
        $onboarding->recordEmailLinkLogin($user);
        Auth::guard()->login($user);

        if ($user->two_factor_recovery_required_at) {
            return redirect()->route('user.two-factor.recovery');
        }

        return redirect()->intended('/');
    }
}
