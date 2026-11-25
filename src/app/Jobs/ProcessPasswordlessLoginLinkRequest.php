<?php

namespace App\Jobs;

use App\Mail\User\LoginLink;
use App\Models\People\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** Process an email-link request after its generic HTTP response is sent. */
class ProcessPasswordlessLoginLinkRequest
{
    use Dispatchable;

    /** Create a deferred passwordless login-link request for the supplied email. */
    public function __construct(private readonly string $email) {}

    /** Create and queue a one-time nonce only when the address belongs to an account. */
    public function handle(): void
    {
        $user = User::query()->whereRaw('LOWER(email) = ?', [$this->email])
            ->where('is_official_user', true)
            ->where('is_locked', false)
            ->first();
        if (! $user) {
            return;
        }

        // Keep the nonce out of both database rows and serialized mail payloads.
        $nonce = bin2hex(random_bytes(32));
        $linkId = DB::transaction(function () use ($user, $nonce): ?int {
            $lockedUser = User::query()->lockForUpdate()->whereKey($user->getKey())->first();
            if ($lockedUser === null) {
                return null;
            }
            if (! $lockedUser->isOfficialUser || $lockedUser->isLocked) {
                return null;
            }
            DB::table('passwordless_login_links')
                ->where('user_id', $lockedUser->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            return (int) DB::table('passwordless_login_links')->insertGetId([
                'user_id' => $lockedUser->getKey(),
                'token_hash' => hash('sha256', $nonce),
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($linkId === null) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new LoginLink($nonce));
        } catch (\Throwable) {
            DB::table('passwordless_login_links')->where('id', $linkId)->delete();
        }
    }
}
