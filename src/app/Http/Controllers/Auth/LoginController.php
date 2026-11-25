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
use App\Models\People\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
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

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'keepTokenAlive']);
    }

    /** Display the generic login page or the configured demo login page. */
    public function showLoginForm(Request $request): InertiaResponse
    {
        $request->session()->regenerateToken();
        $demo = config('app.demo_mode');
        $users = $demo ? User::with('roles', 'homeCities')->where('is_official_user', true)->orderBy('id')->get() : [];

        if ($demo) {
            return Inertia::render('Auth/DemoLogin', compact('users'));
        } else {
            return Inertia::render('Auth/Login');
        }
    }

    /**
     * Sign in as a selected sample account in demo mode only.
     *
     * @return RedirectResponse Redirect to the originally intended page.
     */
    public function demoLogin(Request $request): RedirectResponse
    {
        abort_unless(config('app.demo_mode'), 404);
        $validated = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', mb_strtolower(trim($validated['email'])))
            ->where('is_official_user', true)
            ->firstOrFail();
        Auth::guard()->login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /**
     * Return a current CSRF token for the login client.
     *
     * @return JsonResponse JSON response containing the CSRF token.
     */
    public function keepTokenAlive(): JsonResponse
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
        $user = $userId ? User::query()->whereKey($userId)->first() : null;

        abort_unless($user && $user->isOfficialUser && ! $user->isLocked && $user->hasTwoFactorEnabled(), 404);

        $response = Inertia::render('Auth/LoginChallenge')->toResponse($request);
        $response->headers->add([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

        return $response;
    }

    /**
     * Verify an authenticator or recovery code for a pending login.
     *
     * @return RedirectResponse Redirect to the challenge, login form or intended page.
     */
    public function verifyChallenge(Request $request): RedirectResponse
    {
        $challenge = $this->activeChallenge($request);
        if (! $challenge) {
            return redirect()->route('login');
        }

        $validated = $request->validate(['code' => ['required', 'string', 'max:128']]);
        $result = DB::transaction(function () use ($challenge, $validated, $request): array {
            $user = User::query()->lockForUpdate()->whereKey($challenge['user_id'])->first();
            if (! $user || ! $user->isOfficialUser || $user->isLocked || ! $user->hasTwoFactorEnabled()) {
                return ['state' => 'inactive'];
            }

            $key = 'two-factor-login:'.$user->getKey().':'.$request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return ['state' => 'limited', 'key' => $key, 'user' => $user];
            }

            try {
                $verified = $user->attemptTwoFactorCode($validated['code'])->verified;
            } catch (TwoFactorRateLimitedException) {
                return ['state' => 'limited', 'key' => $key, 'user' => $user];
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
        Auth::guard()->login($user, (bool) ($challenge['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /**
     * Cancel a pending challenge and return to the regular login form.
     *
     * @return RedirectResponse Redirect to the login form.
     */
    public function cancelChallenge(Request $request): RedirectResponse
    {
        $request->session()->forget('login.challenge');

        return redirect()->route('login');
    }

    /**
     * Return pending login state only while its five-minute window is open.
     *
     * @return array<string, mixed>|null Pending challenge data or null when expired.
     */
    private function activeChallenge(Request $request): ?array
    {
        /** @var array<string, mixed>|null $challenge */
        $challenge = $request->session()->get('login.challenge');
        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('login.challenge');

            return null;
        }

        return $challenge;
    }
}
