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
use Illuminate\Validation\ValidationException;

/** Create a one-use login URL for an eligible account without sending mail. */
class CreateManualLoginLink
{
    /**
     * Create a Super-Admin login URL and persist only its nonce hash.
     *
     * @param  User  $actor  Super-Admin creating the link.
     * @param  User  $target  Account that will receive the link.
     * @return array{manual_login_url: string, expires_at: string} One-time URL and UTC expiry.
     */
    public function execute(User $actor, User $target): array
    {
        Gate::forUser($actor)->authorize('manageAccount', $target);

        return DB::transaction(function () use ($actor, $target): array {
            /** @var User $lockedTarget */
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());
            Gate::forUser($actor)->authorize('manageAccount', $lockedTarget);

            if (! $lockedTarget->isOfficialUser || $lockedTarget->isLocked) {
                throw ValidationException::withMessages([
                    'account' => 'Für dieses Konto kann kein Anmeldelink erstellt werden.',
                ]);
            }

            $now = now('UTC');
            $expiresAt = $now->copy()->addHour();
            $nonce = bin2hex(random_bytes(32));

            DB::table('passwordless_login_links')
                ->where('user_id', $lockedTarget->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => $now, 'updated_at' => $now]);

            DB::table('passwordless_login_links')->insert([
                'user_id' => $lockedTarget->getKey(),
                'token_hash' => hash('sha256', $nonce),
                'expires_at' => $expiresAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'manual_login_url' => route('login.link.open', ['token' => $nonce]),
                'expires_at' => (string) $expiresAt->toISOString(),
            ];
        });
    }
}
