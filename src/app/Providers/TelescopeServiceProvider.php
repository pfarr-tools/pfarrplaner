<?php

namespace App\Providers;

use App\Mail\User\LoginLink;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/** Configure Telescope recording and redact authentication secrets. */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            $content = $entry->content;
            $uri = is_array($content) ? ($content['uri'] ?? '') : '';
            $requestPath = $entry->type === 'request'
                ? parse_url(is_string($uri) ? $uri : '', PHP_URL_PATH)
                : null;
            if (is_string($requestPath) && (
                preg_match('#^/login/link(?:/|$)#', $requestPath)
                || preg_match('#^/(?:user/passkeys|passkeys)(?:/|$)#', $requestPath)
                || preg_match('#^/admin/benutzer/\d+/login-link$#', $requestPath)
            )) {
                return false;
            }

            if ($entry->type === 'mail' && ($entry->content['mailable'] ?? null) === LoginLink::class) {
                return false;
            }

            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters(['_token', 'password', 'current_password', 'code']);
        Telescope::hideResponseParameters(['provisioning_uri', 'recovery_codes', 'manual_login_url']);
        Telescope::hideRequestParameters(['_old_input.code', '_old_input.password']);

        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            $email = is_object($user) && isset($user->email) && is_string($user->email) ? $user->email : null;

            return in_array($email, [
                //
            ]);
        });
    }
}
