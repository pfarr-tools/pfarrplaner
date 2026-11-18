<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PasskeyLoginController;
use App\Http\Responses\Auth\PasskeyLoginResponse as AppPasskeyLoginResponse;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;
use Webauthn\AuthenticatorResponse;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

/** Covers the public discoverable passkey login ceremony. */
final class PasskeyLoginFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** Isolate ceremony assertions from the endpoint's separately configured IP throttle. */
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear(md5('passkey-loginpasskey-login|127.0.0.1'));
    }

    /** Login options are discoverable and bound to a short purpose-specific session. */
    public function test_login_options_create_a_short_lived_session_challenge_without_account_lookup(): void
    {
        $response = $this->getJson(route('passkey.login-options'));

        $response->assertOk()
            ->assertJsonPath('options.userVerification', 'required');

        $challenge = session('passkey.login.challenge');
        self::assertIsArray($challenge);
        self::assertSame('login', $challenge['purpose']);
        self::assertSame(60, $challenge['expires_at'] - $challenge['issued_at']);
        self::assertSame([], $response->json('options.allowCredentials'));
    }

    /** An expired assertion cannot consume a passkey or authenticate a user. */
    public function test_expired_login_challenge_is_rejected_and_removed(): void
    {
        $this->getJson(route('passkey.login-options'))->assertOk();
        $challenge = session('passkey.login.challenge');

        $this->travel(61)->seconds();
        $response = $this->postJson(route('passkey.login'), [
            'credential' => [
                'id' => 'credential',
                'rawId' => 'credential',
                'type' => 'public-key',
                'response' => [],
            ],
        ])->assertUnprocessable();

        self::assertNull(session('passkey.login.challenge'));
        self::assertNotNull($challenge);
        self::assertGuest();
    }

    /** A challenge for a different ceremony purpose cannot be used for login. */
    public function test_login_challenge_rejects_a_different_ceremony_purpose(): void
    {
        $this->getJson(route('passkey.login-options'))->assertOk();
        session()->put('passkey.login.challenge.purpose', 'registration');

        $this->postJson(route('passkey.login'), [])->assertUnprocessable();

        self::assertNull(session('passkey.login.challenge'));
        self::assertNull(session('passkey.verification_options'));
    }

    /** A malformed assertion consumes the ceremony so it cannot be replayed. */
    public function test_login_challenge_is_removed_after_invalid_request_payload(): void
    {
        $this->getJson(route('passkey.login-options'))->assertOk();

        $this->postJson(route('passkey.login'), [])->assertUnprocessable();

        self::assertNull(session('passkey.login.challenge'));
        self::assertNull(session('passkey.verification_options'));
    }

    /** The two ceremony endpoints share one six-request per-IP allowance. */
    public function test_all_login_ceremony_requests_share_the_ip_limit_and_consume_throttled_challenges(): void
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->getJson(route('passkey.login-options'))->assertOk();
            $this->postJson(route('passkey.login'), [])->assertUnprocessable();
        }

        $this->getJson(route('passkey.login-options'))->assertOk();
        $this->getJson(route('passkey.login-options'))->assertOk();
        $this->postJson(route('passkey.login'), [])->assertTooManyRequests();

        self::assertNull(session('passkey.login.challenge'));
        self::assertNull(session('passkey.verification_options'));
    }

    /** A verified discoverable credential completes login and rotates the session. */
    public function test_verified_passkey_authenticates_and_rotates_the_session(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = new Passkey;
        $passkey->setRelation('user', $user);
        /** @var Store $session */
        $session = app('session.store');
        $session->put('passkey.login.challenge', ['purpose' => 'login']);
        $session->put('passkey.verification_options', 'single-use-options');
        $sessionId = $session->getId();
        $credential = new PublicKeyCredential('public-key', 'opaque-id', new TestAuthenticatorResponse);
        $options = PublicKeyCredentialRequestOptions::create('challenge');
        $request = new TestPasskeyVerificationRequest($session, $credential, $options);
        $verify = new TestVerifyPasskey($passkey);

        Auth::guard('web')->logout();
        $response = app(PasskeyLoginController::class)->verify($request, $verify);

        $this->assertAuthenticatedAs($user);
        self::assertNotSame($sessionId, $session->getId());
        self::assertNull($session->get('passkey.login.challenge'));
        self::assertNull($session->get('passkey.verification_options'));
        self::assertSame(200, $response->getStatusCode());
    }

    /** Legacy forced password-change flags no longer affect passkey login. */
    public function test_passkey_response_ignores_legacy_password_change_flag(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['must_change_password' => true]);
        Auth::guard('web')->setUser($user);
        $request = Request::create('/passkeys/login', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = (new AppPasskeyLoginResponse)->toResponse($request);

        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(url('/'), $payload['redirect']);
    }

    /** Recovered accounts proceed directly to mandatory TOTP re-enrolment. */
    public function test_passkey_response_redirects_recovered_users_to_totp_enrolment(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['two_factor_recovery_required_at' => now()]);
        Auth::guard('web')->setUser($user);
        $request = Request::create('/passkeys/login', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = (new AppPasskeyLoginResponse)->toResponse($request);

        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(route('user.two-factor.recovery'), $payload['redirect']);
    }
}

/** Supplies a typed request while avoiding a browser assertion in controller tests. */
final class TestPasskeyVerificationRequest extends PasskeyVerificationRequest
{
    public function __construct(
        private readonly Store $sessionStore,
        private readonly PublicKeyCredential $assertion,
        private readonly PublicKeyCredentialRequestOptions $options,
    ) {}

    /** Return the isolated session used by the passkey controller. */
    public function session($driver = null): Store
    {
        return $this->sessionStore;
    }

    /** Return the fixture assertion. */
    public function credential(): PublicKeyCredential
    {
        return $this->assertion;
    }

    /** Consume the stored options as the package request does. */
    public function verificationOptions(): PublicKeyCredentialRequestOptions
    {
        $this->sessionStore->forget('passkey.verification_options');

        return $this->options;
    }

    /** Request a JSON response from the package response object. */
    public function wantsJson(): bool
    {
        return true;
    }
}

/** Returns a prepared passkey after controller-level verification plumbing. */
final class TestVerifyPasskey extends VerifyPasskey
{
    public function __construct(private readonly Passkey $passkey) {}

    /** Return the fixture passkey without performing WebAuthn cryptography. */
    public function __invoke(
        PublicKeyCredential $credential,
        PublicKeyCredentialRequestOptions $options,
        ?PasskeyUser $user = null,
    ): Passkey {
        return $this->passkey;
    }
}

/** Provides a response instance for a typed assertion fixture. */
final class TestAuthenticatorResponse extends AuthenticatorResponse
{
    public function __construct()
    {
        $raw = '{"type":"webauthn.get","challenge":"Y2hhbGxlbmdl","origin":"https://example.test"}';
        parent::__construct(new CollectedClientData($raw, json_decode($raw, true, flags: JSON_THROW_ON_ERROR)));
    }
}
