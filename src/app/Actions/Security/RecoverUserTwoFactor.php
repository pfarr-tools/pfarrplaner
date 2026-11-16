<?php

namespace App\Actions\Security;

use App\Models\People\TwoFactorRecoveryAudit;
use App\Models\People\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/** Revoke a target account's lost factor and record the recovery atomically. */
class RecoverUserTwoFactor
{
    /** Revoke target factors, sessions and tokens after fresh actor authentication. */
    public function execute(
        User $actor,
        User $target,
        string $currentPassword,
        string $supportReference,
        string $ipAddress,
    ): TwoFactorRecoveryAudit {
        if ($actor->getKey() === $target->getKey()) {
            throw new AuthorizationException('Ein Super-Admin kann die eigene Zwei-Faktor-Authentifizierung nicht wiederherstellen.');
        }

        Gate::forUser($actor)->authorize('recoverTwoFactor', $target);

        if (config('session.driver') !== 'database') {
            throw new ServiceUnavailableHttpException(
                null,
                'Die Wiederherstellung ist nur mit dem Datenbank-Sessiontreiber verfügbar.',
            );
        }

        $sessionConnection = config('session.connection');
        if ($sessionConnection && $sessionConnection !== config('database.default')) {
            throw new ServiceUnavailableHttpException(
                null,
                'Die Session- und Anwendungsdatenbank müssen für eine atomare Wiederherstellung dieselbe Verbindung verwenden.',
            );
        }

        $rateLimitKey = 'two-factor-recovery:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw new TooManyRequestsHttpException(null, 'Zu viele Versuche. Bitte später erneut versuchen.');
        }

        try {
            $audit = DB::transaction(function () use (
                $actor,
                $target,
                $currentPassword,
                $supportReference,
                $ipAddress,
            ): TwoFactorRecoveryAudit {
                /** @var User $lockedActor */
                $lockedActor = User::query()->lockForUpdate()->findOrFail($actor->getKey());

                if (! Hash::check($currentPassword, (string) $lockedActor->password)) {
                    throw ValidationException::withMessages([
                        'current_password' => 'Die Anmeldedaten konnten nicht bestätigt werden.',
                    ]);
                }

                /** @var User $lockedTarget */
                $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());

                if (! $lockedTarget->hasTwoFactorEnabled() && ! $lockedTarget->hasPendingTwoFactor()) {
                    throw ValidationException::withMessages([
                        'recovery' => 'Für dieses Konto ist kein wiederherstellbarer Zwei-Faktor-Status vorhanden.',
                    ]);
                }

                $lockedTarget->disableTwoFactor();
                $lockedTarget->forceFill(['two_factor_recovery_required_at' => now()])->save();

                DB::table('two_factor_login_challenges')
                    ->where('user_id', $lockedTarget->getKey())
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now(), 'updated_at' => now()]);

                DB::connection(config('database.default'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedTarget->getKey())
                    ->delete();

                $lockedTarget->tokens()->delete();

                return TwoFactorRecoveryAudit::query()->create([
                    'actor_user_id' => $lockedActor->getKey(),
                    'target_user_id' => $lockedTarget->getKey(),
                    'support_reference' => $supportReference,
                    'ip_address' => $ipAddress,
                ]);
            }, 3);
        } catch (ValidationException $exception) {
            if (array_key_exists('current_password', $exception->errors())) {
                RateLimiter::hit($rateLimitKey, 60);
            }

            throw $exception;
        }

        RateLimiter::clear($rateLimitKey);

        return $audit;
    }
}
