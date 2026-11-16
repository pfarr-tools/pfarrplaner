<?php

namespace Tests\Unit;

use App\Mail\User\TwoFactorLoginCode;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Http\JsonResponse;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\RequestWatcher;
use Tests\TestCase;

class TelescopeTwoFactorRedactionTest extends TestCase
{
    /** Telescope must not retain rendered one-time login messages. */
    public function test_two_factor_login_mails_are_excluded_from_telescope(): void
    {
        $originalFilters = Telescope::$filterUsing;
        $originalParameters = Telescope::$hiddenRequestParameters;
        $originalResponses = Telescope::$hiddenResponseParameters;

        try {
            Telescope::$filterUsing = [];
            (new TelescopeServiceProvider($this->app))->register();
            $filter = Telescope::$filterUsing[array_key_last(Telescope::$filterUsing)];
            $entry = IncomingEntry::make(['mailable' => TwoFactorLoginCode::class])->type('mail');

            self::assertFalse($filter($entry));
            self::assertContains('code', Telescope::$hiddenRequestParameters);
            self::assertContains('password', Telescope::$hiddenRequestParameters);
            self::assertContains('_old_input.code', Telescope::$hiddenRequestParameters);
            self::assertContains('provisioning_uri', Telescope::$hiddenResponseParameters);
            self::assertContains('recovery_codes', Telescope::$hiddenResponseParameters);

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
                'enabled' => true,
            ]));
            self::assertSame('********', $recordedResponse['provisioning_uri']);
            self::assertSame('********', $recordedResponse['recovery_codes']);
            self::assertTrue($recordedResponse['enabled']);
        } finally {
            Telescope::$filterUsing = $originalFilters;
            Telescope::$hiddenRequestParameters = $originalParameters;
            Telescope::$hiddenResponseParameters = $originalResponses;
        }
    }
}
