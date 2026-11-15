<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
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
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return self::isEmailAllowed(optional($user)->email, config('horizon.allowed_emails', []));
        });
    }
}
