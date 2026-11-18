<?php

namespace Tests\Feature;

use App\Jobs\ProcessPasswordlessLoginLinkRequest;
use App\Mail\User\LoginLink;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Tests\TestCase;

final class EmailLoginLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_RESPONSE = 'Wenn die Adresse zu einem Konto gehört, das sich anmelden kann, senden wir einen Anmeldelink. Bitte prüfen Sie Ihr Postfach.';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_non_account_records_neither_receive_nor_redeem_email_login_links(): void
    {
        $user = User::factory()->create([
            'email' => 'person-only@example.test',
            'is_official_user' => false,
            'password' => null,
        ]);

        (new ProcessPasswordlessLoginLinkRequest($user->email))->handle();

        self::assertSame(0, DB::table('passwordless_login_links')->count());
        Mail::assertNothingQueued();

        $linkId = DB::table('passwordless_login_links')->insertGetId([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', 'stale-non-account-token'),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->withSession(['login.link.confirmation_id' => $linkId])
            ->from(route('login'))
            ->post(route('login.link.confirm'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        self::assertNotNull(DB::table('passwordless_login_links')->where('id', $linkId)->value('consumed_at'));
    }

    public function test_known_and_unknown_addresses_receive_the_same_generic_response(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test']);
        RateLimiter::clear('login-link-email:'.hash_hmac('sha256', mb_strtolower($user->email), config('app.key')));

        $knownResponse = $this->from(route('login'))->post(route('login.link.request'), [
            'email' => $user->email,
        ]);
        $knownResponse->assertRedirect(route('login'))
            ->assertSessionHas('status', self::GENERIC_RESPONSE);

        $unknownResponse = $this->from(route('login'))->post(route('login.link.request'), [
            'email' => 'unknown@example.test',
        ]);
        $unknownResponse->assertRedirect(route('login'))
            ->assertSessionHas('status', self::GENERIC_RESPONSE);

        Mail::assertQueued(LoginLink::class, 1);
    }

    public function test_link_token_is_hashed_and_opening_the_link_does_not_consume_it(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test']);
        $token = $this->requestLinkAndReadToken($user);
        $link = DB::table('passwordless_login_links')->first();

        self::assertSame(hash('sha256', $token), $link->token_hash);
        self::assertNotSame($token, $link->token_hash);
        Mail::assertQueued(LoginLink::class, function (LoginLink $mail) use ($token): bool {
            self::assertStringNotContainsString($token, serialize($mail));
            self::assertSame($token, Crypt::decryptString($mail->encryptedToken));
            self::assertStringContainsString($token, $mail->render());

            return true;
        });
        self::assertTrue(now()->addMinutes(9)->lessThanOrEqualTo($link->expires_at));
        self::assertTrue(now()->addMinutes(10)->greaterThanOrEqualTo($link->expires_at));

        $this->get(route('login.link.open', ['token' => $token]))
            ->assertRedirect(route('login.link.confirm-form'))
            ->assertSessionHas('login.link.confirmation_id', $link->id);

        self::assertNull(DB::table('passwordless_login_links')->where('id', $link->id)->value('consumed_at'));
        $this->get(route('login.link.confirm-form'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ConfirmLoginLink')
                ->missing('token'));
    }

    public function test_confirming_a_link_logs_in_once_and_rejects_replay(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test']);
        $token = $this->requestLinkAndReadToken($user);

        $this->get(route('login.link.open', ['token' => $token]))
            ->assertRedirect(route('login.link.confirm-form'));
        $this->from(route('login.link.confirm-form'))->post(route('login.link.confirm'))
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        self::assertNotNull(DB::table('passwordless_login_links')->value('consumed_at'));
        self::assertSame(1, $user->fresh()->security_onboarding_email_logins);

        auth()->logout();
        $this->withSession([])
            ->get(route('login.link.open', ['token' => $token]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');
        $this->assertGuest();
    }

    public function test_expired_links_cannot_be_opened_or_consumed(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test']);
        $token = $this->requestLinkAndReadToken($user);
        DB::table('passwordless_login_links')->update(['expires_at' => now()->subSecond()]);

        $this->get(route('login.link.open', ['token' => $token]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_link_is_expired_at_the_exact_expiration_instant(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test']);
        $token = $this->requestLinkAndReadToken($user);
        $expiresAt = DB::table('passwordless_login_links')->value('expires_at');

        $this->get(route('login.link.open', ['token' => $token]))
            ->assertRedirect(route('login.link.confirm-form'));
        $this->travelTo(now()->parse($expiresAt));

        $this->from(route('login.link.confirm-form'))->post(route('login.link.confirm'))
            ->assertRedirect(route('login.link.confirm-form'))
            ->assertSessionHasErrors('link');
        $this->assertGuest();
    }

    public function test_totp_enabled_accounts_still_require_the_authenticator_challenge(): void
    {
        $user = User::factory()->create(['email' => 'totp@example.test']);
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $this->travel(31)->seconds();
        RateLimiter::clear('two-factor-login:'.$user->getKey().':127.0.0.1');
        RateLimiter::clear('two-factor:'.User::class.':'.$user->getKey());
        $token = $this->requestLinkAndReadToken($user);

        $this->get(route('login.link.open', ['token' => $token]))
            ->assertRedirect(route('login.link.confirm-form'));
        $this->post(route('login.link.confirm'))
            ->assertRedirect(route('login.challenge'))
            ->assertSessionHas('login.challenge.user_id', $user->getKey());

        $this->assertGuest();
        self::assertSame(0, $user->fresh()->security_onboarding_email_logins);
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.verify'), [
                'code' => app(TwoFactorService::class)->currentCode($setup->secret),
            ])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_passwordless_link_requests_are_limited_by_normalized_email(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test']);
        RateLimiter::clear('login-link-email:'.hash_hmac('sha256', mb_strtolower($user->email), config('app.key')));

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->from(route('login'))
                ->post(route('login.link.request'), ['email' => strtoupper($user->email)])
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', self::GENERIC_RESPONSE);
        }

        Mail::assertQueued(LoginLink::class, 3);
    }

    private function requestLinkAndReadToken(User $user): string
    {
        RateLimiter::clear('login-link-email:'.hash_hmac('sha256', mb_strtolower($user->email), config('app.key')));

        $this->from(route('login'))->post(route('login.link.request'), [
            'email' => $user->email,
        ])->assertRedirect(route('login'));

        $token = null;
        Mail::assertQueued(LoginLink::class, function (LoginLink $mail) use (&$token): bool {
            $token = Crypt::decryptString($mail->encryptedToken);

            return true;
        });

        self::assertIsString($token);

        return $token;
    }
}
