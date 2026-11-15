<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

/** Apply the configured operator permissions to Horizon in every environment. */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Match a nonempty operator email against the normalized configuration allowlist.
     *
     * @param  array<string>  $allowedEmails
     */
    public static function isEmailAllowed(?string $email, array $allowedEmails): bool
    {
        $normalizedEmail = strtolower(trim((string) $email));
        $normalizedAllowedEmails = array_map(
            static fn (string $allowedEmail): string => strtolower(trim($allowedEmail)),
            $allowedEmails,
        );

        return $normalizedEmail !== '' && in_array($normalizedEmail, $normalizedAllowedEmails, true);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Require an authenticated, authorized operator without Horizon's local bypass.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(static function (Request $request): bool {
            $user = $request->user();

            return $user !== null && Gate::forUser($user)->check('viewHorizon');
        });
    }

    /**
     * Register the Horizon operator allowlist gate for all environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return self::isEmailAllowed(optional($user)->email, config('horizon.allowed_emails', []));
        });
    }
}
