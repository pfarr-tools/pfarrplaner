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
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\User\TwoFactorLoginCode;
use App\Models\People\User;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Timebox;
use Inertia\Inertia;
use RoundlyConsulting\TwoFactor\Exceptions\TwoFactorRateLimitedException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Class LoginController
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Keep the pending-factor path outside Laravel's completed-login branch,
     * which clears password attempts and marks the session password-confirmed.
     */
    public function login(Request $request)
    {
        $request->session()->forget('login.challenge');
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->credentials($request);
        /** @var SessionGuard $guard */
        $guard = $this->guard();
        $provider = $guard->getProvider();
        $loginState = app(Timebox::class)->call(function () use ($request, $credentials, $provider, $guard): string {
            return DB::transaction(function () use ($request, $credentials, $provider, $guard): string {
                $user = User::query()
                    ->where($this->username(), $credentials[$this->username()])
                    ->lockForUpdate()
                    ->first();

                Event::dispatch(new Attempting(
                    $guard->name,
                    $credentials,
                    $request->boolean('remember'),
                ));

                if (! $user || ! $provider->validateCredentials($user, $credentials)) {
                    Event::dispatch(new Failed($guard->name, $user, $credentials));

                    return 'failed';
                }

                if (method_exists($provider, 'rehashPasswordIfRequired')) {
                    $provider->rehashPasswordIfRequired($user, $credentials);
                }

                if ($user->hasTwoFactorEnabled()) {
                    $request->session()->put('login.challenge', [
                        'user_id' => $user->getKey(),
                        'remember' => $request->boolean('remember'),
                        'expires_at' => now()->addMinutes(5)->timestamp,
                        'throttle_key' => $this->throttleKey($request),
                    ]);
                    $request->session()->regenerate();

                    return 'challenge';
                }

                $guard->login($user, $request->boolean('remember'));

                return 'authenticated';
            });
        }, 200000);

        if ($loginState === 'challenge') {
            return redirect()->route('login.challenge');
        }

        if ($loginState === 'authenticated') {
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'keepTokenAlive']);
    }

    /**
     * @return Response
     */
    public function showLoginForm(Request $request)
    {
        $request->session()->regenerateToken();
        $demo = config('app.demo_mode');
        $users = $demo ? User::with('roles', 'homeCities')->where('password', '!=', '')->orderBy('id')->get() : [];

        if ($demo) {
            return Inertia::render('Auth/DemoLogin', compact('users'));
        } else {
            return Inertia::render('Auth/Login');
        }
    }

    /** Preserve the legacy initial-password endpoint without additional output. */
    public function setInitialPassword() {}

    /** Return a current CSRF token for the login client. */
    public function keepTokenAlive()
    {
        return response()->json(['token' => csrf_token()]);
    }

    /** Display the second-factor step only for a live pending login. */
    public function showChallenge(Request $request): HttpResponse
    {
        $challenge = $request->session()->get('login.challenge');
        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('login.challenge');
            abort(404);
        }

        $userId = $challenge['user_id'] ?? null;
        $user = $userId ? User::find($userId) : null;

        abort_unless($user && $user->hasTwoFactorEnabled(), 404);

        return Inertia::render('Auth/LoginChallenge')
            ->toResponse($request)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }

    /** Verify a TOTP, recovery code, or one-time email challenge. */
    public function verifyChallenge(Request $request)
    {
        $challenge = $this->activeChallenge($request);
        if (! $challenge) {
            return redirect()->route('login');
        }

        $validated = $request->validate(['code' => ['required', 'string', 'max:128']]);
        $result = DB::transaction(function () use ($challenge, $validated, $request): array {
            $user = User::query()->lockForUpdate()->find($challenge['user_id']);
            if (! $user || ! $user->hasTwoFactorEnabled()) {
                return ['state' => 'inactive'];
            }

            $key = 'two-factor-login:'.$user->getKey().':'.$request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return ['state' => 'limited', 'key' => $key, 'user' => $user];
            }

            $emailChallengeId = $challenge['email_challenge_id'] ?? null;
            $verified = $emailChallengeId !== null
                && $this->consumeEmailCode((int) $emailChallengeId, $user->getKey(), $validated['code']);

            if (! $verified) {
                try {
                    $verified = $user->attemptTwoFactorCode($validated['code'])->verified;
                } catch (TwoFactorRateLimitedException) {
                    return ['state' => 'limited', 'key' => $key, 'user' => $user];
                }
            }

            return ['state' => $verified ? 'verified' : 'invalid', 'key' => $key, 'user' => $user];
        });

        if ($result['state'] === 'inactive') {
            $request->session()->forget('login.challenge');

            return redirect()->route('login');
        }
        if ($result['state'] === 'limited') {
            return back()->withErrors(['code' => 'Zu viele Versuche. Bitte später erneut versuchen.']);
        }

        /** @var User $user */
        $user = $result['user'];
        $key = $result['key'];
        $verified = $result['state'] === 'verified';

        if (! $verified) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['code' => 'Der Sicherheitscode ist ungültig oder nicht mehr verfügbar.']);
        }

        RateLimiter::clear($key);
        if (isset($challenge['throttle_key'])) {
            RateLimiter::clear($challenge['throttle_key']);
        }
        $request->session()->forget('login.challenge');
        $this->guard()->login($user, (bool) ($challenge['remember'] ?? false));

        return $this->sendLoginResponse($request);
    }

    /** Send a time-limited, single-use email code to a pending login. */
    public function sendEmailChallenge(Request $request)
    {
        $challenge = $this->activeChallenge($request);
        if (! $challenge) {
            return redirect()->route('login');
        }

        $user = User::find($challenge['user_id']);
        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('login.challenge');

            return redirect()->route('login');
        }

        $key = 'two-factor-email-login:'.$user->getKey();
        $code = str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
        $challengeId = DB::transaction(function () use ($user, $code, $key): ?int {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if (! $lockedUser->hasTwoFactorEnabled() || RateLimiter::tooManyAttempts($key, 2)) {
                return null;
            }
            RateLimiter::hit($key, 300);

            DB::table('two_factor_login_challenges')
                ->where('user_id', $lockedUser->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            return (int) DB::table('two_factor_login_challenges')->insertGetId([
                'user_id' => $lockedUser->getKey(),
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($challengeId === null) {
            return back()->withErrors(['code' => 'Ein Code wurde bereits angefordert. Bitte später erneut versuchen.']);
        }

        try {
            Mail::to($user->email)->send(new TwoFactorLoginCode($code));
        } catch (\Throwable) {
            DB::table('two_factor_login_challenges')->where('id', $challengeId)->delete();

            return back()->withErrors(['code' => 'Der Code konnte nicht gesendet werden. Bitte erneut versuchen.']);
        }

        $request->session()->put('login.challenge.email_challenge_id', $challengeId);

        return back()->with('success', 'Ein Anmeldecode wurde per E-Mail versendet.');
    }

    /** Cancel a pending challenge and return to the regular login form. */
    public function cancelChallenge(Request $request)
    {
        $request->session()->forget('login.challenge');

        return redirect()->route('login');
    }

    /** Return pending login state only while its five-minute window is open. */
    private function activeChallenge(Request $request): ?array
    {
        $challenge = $request->session()->get('login.challenge');
        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('login.challenge');

            return null;
        }

        return $challenge;
    }

    /** Atomically consume a matching one-time email challenge. */
    private function consumeEmailCode(int $challengeId, int|string $userId, string $code): bool
    {
        return DB::transaction(function () use ($challengeId, $userId, $code): bool {
            $emailChallenge = DB::table('two_factor_login_challenges')
                ->where('id', $challengeId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $emailChallenge || $emailChallenge->consumed_at || now()->greaterThan($emailChallenge->expires_at) || $emailChallenge->attempts >= 5) {
                return false;
            }

            if (! Hash::check($code, $emailChallenge->code_hash)) {
                DB::table('two_factor_login_challenges')
                    ->where('id', $challengeId)
                    ->increment('attempts');

                return false;
            }

            DB::table('two_factor_login_challenges')
                ->where('id', $challengeId)
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            return true;
        });
    }
}
