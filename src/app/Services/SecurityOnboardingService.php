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

namespace App\Services;

use App\Models\People\User;
use Carbon\Carbon;

/** Track email-link sign-ins and decide when an account-security prompt is due. */
class SecurityOnboardingService
{
    private const SESSION_DEFERRED_KEY = 'security_onboarding.deferred';

    private const INITIAL_REMINDER_COUNT = 4;

    private const REMINDER_INTERVAL_DAYS = 14;

    /**
     * Count a successful email-link login when no passkey or confirmed TOTP exists.
     *
     * @param  User  $user  Authenticated account.
     */
    public function recordEmailLinkLogin(User $user): void
    {
        if (! $user->isOfficialUser || $this->hasSecurityFactor($user)) {
            return;
        }

        $user->increment('security_onboarding_email_logins');
    }

    /**
     * Determine whether this account should be held at the security-onboarding page.
     *
     * @param  User  $user  Account to check.
     * @return bool Whether the user should see the security prompt.
     */
    public function shouldShow(User $user): bool
    {
        if ($this->isDeferredForSession($user)) {
            return false;
        }

        if (! $user->isOfficialUser || $this->hasSecurityFactor($user)) {
            return false;
        }

        $loginCount = (int) $user->security_onboarding_email_logins;
        if ($loginCount < 1) {
            return false;
        }

        if ($loginCount <= self::INITIAL_REMINDER_COUNT) {
            return true;
        }

        $lastShownAt = $user->security_onboarding_last_shown_at;

        return $lastShownAt === null || Carbon::parse($lastShownAt)->lte(now()->subDays(self::REMINDER_INTERVAL_DAYS));
    }

    /**
     * Persist when the onboarding prompt was last displayed.
     *
     * @param  User  $user  Account shown the prompt.
     */
    public function markShown(User $user): void
    {
        $user->forceFill(['security_onboarding_last_shown_at' => now()])->save();
    }

    /**
     * Defer the gate for this authenticated session only.
     *
     * @param  User  $user  Account whose prompt is deferred.
     */
    public function deferForSession(User $user): void
    {
        request()->session()->put(self::SESSION_DEFERRED_KEY, $user->getKey());
    }

    /**
     * Check whether the current session has deferred the security prompt for this account.
     *
     * @param  User  $user  Account being checked.
     * @return bool Whether this account is deferred in the current session.
     */
    public function isDeferredForSession(User $user): bool
    {
        if (! request()->hasSession()) {
            return false;
        }

        /** @var int|string|null $deferred Früher gespeicherte Benutzerkennung. */
        $deferred = request()->session()->get(self::SESSION_DEFERRED_KEY, 0);
        /** @var int|string|null $userId Kennung des angefragten Benutzers. */
        $userId = $user->getKey();

        return (int) $deferred === (int) $userId;
    }

    /**
     * Check whether the user has a passkey or a confirmed TOTP factor.
     *
     * @param  User  $user  Account being checked.
     * @return bool Whether a qualifying security factor exists.
     */
    private function hasSecurityFactor(User $user): bool
    {
        return $user->hasTwoFactorEnabled() || $user->passkeys()->exists();
    }
}
