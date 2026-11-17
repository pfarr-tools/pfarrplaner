<?php

namespace Tests\Unit;

use Tests\TestCase;

/** Verify session-cookie transport security follows APP_URL and explicit overrides. */
final class SessionCookieConfigurationTest extends TestCase
{
    /** Blank configuration follows HTTP and HTTPS application URLs respectively. */
    public function test_blank_session_cookie_setting_follows_application_url_scheme(): void
    {
        self::assertFalse($this->configuredSessionCookieSecurity('http://localhost:8180', ''));
        self::assertTrue($this->configuredSessionCookieSecurity('https://example.test', ''));
    }

    /** An explicit setting takes precedence over the application URL scheme. */
    public function test_explicit_session_cookie_setting_overrides_application_url_scheme(): void
    {
        self::assertTrue($this->configuredSessionCookieSecurity('http://localhost:8180', 'true'));
        self::assertFalse($this->configuredSessionCookieSecurity('https://example.test', 'false'));
    }

    /** Load the session configuration with temporary environment values. */
    private function configuredSessionCookieSecurity(string $appUrl, string $secureSetting): bool
    {
        $keys = ['APP_URL', 'SESSION_SECURE_COOKIE'];
        $original = [];
        foreach ($keys as $key) {
            $original[$key] = [
                'env_exists' => array_key_exists($key, $_ENV),
                'env_value' => $_ENV[$key] ?? null,
                'server_exists' => array_key_exists($key, $_SERVER),
                'server_value' => $_SERVER[$key] ?? null,
                'process_value' => getenv($key),
            ];
        }

        try {
            $this->setEnvironmentValue('APP_URL', $appUrl);
            $this->setEnvironmentValue('SESSION_SECURE_COOKIE', $secureSetting);
            $sessionConfiguration = require base_path('config/session.php');

            return (bool) $sessionConfiguration['secure'];
        } finally {
            foreach ($original as $key => $value) {
                if ($value['env_exists']) {
                    $_ENV[$key] = $value['env_value'];
                } else {
                    unset($_ENV[$key]);
                }
                if ($value['server_exists']) {
                    $_SERVER[$key] = $value['server_value'];
                } else {
                    unset($_SERVER[$key]);
                }
                if ($value['process_value'] === false) {
                    putenv($key);
                } else {
                    putenv($key.'='.$value['process_value']);
                }
            }
        }
    }

    /** Set a value in each environment adapter used by Laravel's env helper. */
    private function setEnvironmentValue(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }
}
