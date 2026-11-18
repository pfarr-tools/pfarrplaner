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
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\People\User;
use App\Services\SecurityOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Serve and defer the full-screen account-security onboarding flow. */
class SecurityOnboardingController extends Controller
{
    /**
     * Render the passwordless account's security-onboarding page.
     *
     * @param  Request  $request  Current authenticated request.
     * @return Response Inertia response for the onboarding page.
     */
    public function show(Request $request, SecurityOnboardingService $onboarding): Response
    {
        /** @var User $user */
        $user = $request->user();
        if ($onboarding->shouldShow($user)) {
            $onboarding->markShown($user);
        }
        $passkeys = $user->passkeys()->latest()->get()->map(static fn ($passkey): array => [
            'id' => $passkey->getKey(),
            'name' => $passkey->name,
            'authenticator' => $passkey->authenticator,
            'createdAt' => $passkey->created_at?->toIso8601String(),
            'lastUsedAt' => $passkey->last_used_at?->toIso8601String(),
        ])->all();

        return Inertia::render('Auth/SecurityOnboarding', [
            'user' => [
                'firstName' => $user->first_name,
                'email' => $user->email,
            ],
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'twoFactorPending' => $user->hasPendingTwoFactor(),
            'passkeys' => $passkeys,
        ]);
    }

    /**
     * Release the gate until the current session ends.
     *
     * @param  Request  $request  Current authenticated request.
     * @param  SecurityOnboardingService  $onboarding  Onboarding state service.
     * @return RedirectResponse Redirect to the application home page.
     */
    public function later(Request $request, SecurityOnboardingService $onboarding): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $onboarding->deferForSession($user);

        return redirect()->route('home');
    }
}
