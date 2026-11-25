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

namespace App\Actions\Security;

use App\Models\People\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/** Apply Super-Admin-only account security changes to another user. */
class ManageUserAccount
{
    /** Persist whether the target account is blocked from web sign-in. */
    public function setLocked(User $actor, User $target, bool $locked): void
    {
        Gate::forUser($actor)->authorize('manageAccount', $target);

        DB::transaction(function () use ($target, $locked): void {
            /** @var User $lockedTarget */
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $lockedTarget->forceFill(['is_locked' => $locked])->save();
        });
    }

    /** Revoke every discoverable passkey associated with the target account. */
    public function revokePasskeys(User $actor, User $target): void
    {
        Gate::forUser($actor)->authorize('manageAccount', $target);

        DB::transaction(function () use ($target): void {
            /** @var User $lockedTarget */
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $lockedTarget->passkeys()->delete();
        });
    }

    /** Remove sign-in credentials while retaining the person's record and relationships. */
    public function resetToPerson(User $actor, User $target): void
    {
        Gate::forUser($actor)->authorize('manageAccount', $target);

        DB::transaction(function () use ($target): void {
            /** @var User $lockedTarget */
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $userId = $lockedTarget->getKey();
            $lockedTarget->disableTwoFactor();
            $lockedTarget->passkeys()->delete();
            $lockedTarget->tokens()->delete();
            $lockedTarget->forceFill([
                'is_official_user' => false,
                'is_locked' => false,
                'password' => null,
                'must_change_password' => false,
                'api_token' => null,
                'remember_token' => Str::random(60),
                'two_factor_recovery_required_at' => null,
                'two_factor_recovery_attempts' => 0,
                'two_factor_recovery_attempted_at' => null,
            ])->save();

            DB::table('passwordless_login_links')
                ->where('user_id', $userId)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);
            DB::table('two_factor_login_challenges')
                ->where('user_id', $userId)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            if (config('session.driver') === 'database') {
                /** @var string|null $sessionConnection */
                $sessionConnection = config('session.connection') ?: config('database.default');
                /** @var string $sessionTable */
                $sessionTable = config('session.table', 'sessions');
                DB::connection($sessionConnection)
                    ->table($sessionTable)
                    ->where('user_id', $userId)
                    ->delete();
            }
        });
    }
}
