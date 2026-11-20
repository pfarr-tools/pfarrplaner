<?php

namespace Tests\Unit;

use App\Mail\User\LoginLink;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Http\JsonResponse;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\RequestWatcher;
use Tests\TestCase;

class TelescopeTwoFactorRedactionTest extends TestCase
{
    /** Telescope must not retain rendered one-time login links. */
    public function test_passwordless_login_mails_are_excluded_from_telescope(): void
    {
        $originalFilters = Telescope::$filterUsing;
        $originalParameters = Telescope::$hiddenRequestParameters;
        $originalResponses = Telescope::$hiddenResponseParameters;

        try {
            Telescope::$filterUsing = [];
            (new TelescopeServiceProvider($this->app))->register();
            $filter = Telescope::$filterUsing[array_key_last(Telescope::$filterUsing)];
            $entry = IncomingEntry::make(['mailable' => LoginLink::class])->type('mail');

            self::assertFalse($filter($entry));
            $tokenRequest = IncomingEntry::make(['uri' => '/login/link/'.str_repeat('a', 64)])->type('request');
            self::assertFalse($filter($tokenRequest));
            $confirmationRequest = IncomingEntry::make(['uri' => '/login/link/confirm'])->type('request');
            self::assertFalse($filter($confirmationRequest));
            $manualLinkRequest = IncomingEntry::make([
                'uri' => '/admin/benutzer/42/login-link',
                'response_status' => 500,
            ])->type('request');
            self::assertFalse($filter($manualLinkRequest));
            self::assertContains('code', Telescope::$hiddenRequestParameters);
            self::assertContains('password', Telescope::$hiddenRequestParameters);
            self::assertContains('_old_input.code', Telescope::$hiddenRequestParameters);
            self::assertContains('provisioning_uri', Telescope::$hiddenResponseParameters);
            self::assertContains('recovery_codes', Telescope::$hiddenResponseParameters);
            self::assertContains('manual_login_url', Telescope::$hiddenResponseParameters);

            $watcher = new RequestWatcher;
            $payload = new \ReflectionMethod($watcher, 'payload');
            $payload->setAccessible(true);
            $request = $payload->invoke($watcher, [
                'code' => '123456',
                'password' => 'secret-password',
                'visible' => 'ordinary-value',
            ]);
            self::assertSame('********', $request['code']);
            self::assertSame('********', $request['password']);
            self::assertSame('ordinary-value', $request['visible']);

            $session = $payload->invoke($watcher, ['_old_input' => ['code' => '123456']]);
            self::assertSame('********', $session['_old_input']['code']);

            $response = new \ReflectionMethod($watcher, 'response');
            $response->setAccessible(true);
            $recordedResponse = $response->invoke($watcher, new JsonResponse([
                'provisioning_uri' => 'otpauth://secret',
                'recovery_codes' => ['plain-recovery-code'],
                'manual_login_url' => 'https://example.test/login/link/plain-token',
                'enabled' => true,
            ]));
            self::assertSame('********', $recordedResponse['provisioning_uri']);
            self::assertSame('********', $recordedResponse['recovery_codes']);
            self::assertSame('********', $recordedResponse['manual_login_url']);
            self::assertTrue($recordedResponse['enabled']);
        } finally {
            Telescope::$filterUsing = $originalFilters;
            Telescope::$hiddenRequestParameters = $originalParameters;
            Telescope::$hiddenResponseParameters = $originalResponses;
        }
    }
}
