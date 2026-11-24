<?php

namespace Tests\Feature;

use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Log;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;

/** Covers authenticated passkey enrollment and credential management. */
final class PasskeyManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** Keep security-flow assertions independent from request-rate counters. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /** Guests cannot start registration ceremonies. */
    public function test_guest_cannot_request_registration_options(): void
    {
        $this->getJson(route('user.passkeys.options'))->assertUnauthorized();
    }

    /** Registration options are available in any authenticated session. */
    public function test_registration_options_do_not_require_password_confirmation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('user.passkeys.options'))
            ->assertOk()
            ->assertJsonPath('options.authenticatorSelection.userVerification', 'required')
            ->assertJsonPath('options.authenticatorSelection.residentKey', 'required');

        $challenge = session('passkey.registration.challenge');
        self::assertIsArray($challenge);
        self::assertSame('registration', $challenge['purpose']);
        self::assertSame(60, $challenge['expires_at'] - $challenge['issued_at']);
    }

    /** Browser options permit both attachment types without weakening discoverability or verification. */
    public function test_browser_options_allow_all_authenticator_types(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        config(['passkeys.relying_party_id' => 'plan.example.test']);

        $response = $this->actingAs($user)->getJson(route('user.passkeys.options'))->assertOk();
        $options = $response->json('options');
        self::assertArrayNotHasKey('authenticatorAttachment', $options['authenticatorSelection']);
        self::assertSame(true, $options['authenticatorSelection']['requireResidentKey']);
        self::assertSame('required', $options['authenticatorSelection']['residentKey']);
        self::assertSame('required', $options['authenticatorSelection']['userVerification']);
        self::assertSame('none', $options['attestation']);
        self::assertSame([-7, -257], array_column($options['pubKeyCredParams'], 'alg'));
        self::assertSame('plan.example.test', $options['rp']['id']);
        self::assertEmpty($options['extensions'] ?? []);
    }

    /** Deleted credentials disappear and every retry replaces the previous challenge. */
    public function test_registration_retry_reloads_credentials_and_challenge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $key = $this->createPasskey($user, 'Old key');
        $key->update(['credential_id' => 'Zml4dHVyZQ']);

        $first = $this->actingAs($user)->getJson(route('user.passkeys.options'))->assertOk();
        self::assertCount(1, $first->json('options.excludeCredentials'));

        $user->passkeys()->delete();
        $second = $this->getJson(route('user.passkeys.options'))->assertOk();
        self::assertSame([], $second->json('options.excludeCredentials'));
        self::assertNotSame($first->json('options.challenge'), $second->json('options.challenge'));

        $this->postJson(route('user.passkeys.store'), [])->assertUnprocessable();
        self::assertNull(session('passkey.registration_options'));
        self::assertNull(session('passkey.registration.challenge'));

        $this->getJson(route('user.passkeys.options'))->assertOk();
        $this->travel(61)->seconds();
        $this->postJson(route('user.passkeys.store'), [])->assertUnprocessable();
        self::assertNull(session('passkey.registration_options'));
    }

    /** Diagnosis accepts only bounded categories and never logs extra client data. */
    public function test_registration_diagnostics_exclude_secrets(): void
    {
        Log::spy();
        /** @var User $user */
        $user = User::factory()->create();
        $diagnosis = [
            'correlation_id' => 'a72667da-6eef-4e78-a0dd-d3810230a840',
            'phase' => 'authenticator',
            'error' => 'NotAllowedError',
            'browser' => 'Chrome',
            'platform' => 'Android',
            'status' => null,
        ];

        $this->postJson(route('user.passkeys.diagnostics'), $diagnosis)->assertUnauthorized();
        $this->actingAs($user)->postJson(route('user.passkeys.diagnostics'), $diagnosis + [
            'message' => 'SECRET',
            'credential' => ['id' => 'SECRET'],
        ])->assertNoContent();
        Log::shouldHaveReceived('notice')->once()->with('Passkey registration client failure', $diagnosis);

        $this->postJson(route('user.passkeys.diagnostics'), array_replace($diagnosis, [
            'error' => 'SECRET',
        ]))->assertUnprocessable();
    }

    /** Validation failures carry the same bounded ID as their server log entry. */
    public function test_registration_failure_has_server_correlation(): void
    {
        Log::spy();
        /** @var User $user */
        $user = User::factory()->create();
        $id = 'a72667da-6eef-4e78-a0dd-d3810230a840';

        $this->actingAs($user)->getJson(route('user.passkeys.options'))->assertOk();
        $this->postJson(route('user.passkeys.store'), [], ['X-Passkey-Request-ID' => $id])
            ->assertUnprocessable()->assertHeader('X-Passkey-Request-ID', $id);
        Log::shouldHaveReceived('warning')->once()->with('Passkey registration server failure', [
            'correlation_id' => $id,
            'phase' => 'store',
            'status' => 422,
        ]);
    }

    /** An impersonated administrator cannot enroll a credential for the target user. */
    public function test_impersonated_session_cannot_manage_passkeys(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = $this->createPasskey($user, 'Existing key');

        $this->actingAs($user)
            ->withSession(['adminUserSwitchBack' => 9001])
            ->getJson(route('user.passkeys.options'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['adminUserSwitchBack' => 9001])
            ->deleteJson(route('user.passkeys.destroy', $passkey))
            ->assertForbidden();

        self::assertDatabaseHas('passkeys', ['id' => $passkey->getKey()]);
    }

    /** Credential lists never return stored public key or credential data. */
    public function test_passkey_list_returns_only_safe_management_fields(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = $this->createPasskey($user, 'Office key');

        $response = $this->actingAs($user)
            ->getJson(route('user.passkeys.index'))
            ->assertOk()
            ->assertJsonPath('passkeys.0.name', 'Office key');

        self::assertSame($passkey->getKey(), $response->json('passkeys.0.id'));
        self::assertArrayNotHasKey('credential', $response->json('passkeys.0'));
        self::assertArrayNotHasKey('credential_id', $response->json('passkeys.0'));
    }

    /** An authenticated user may rename their own passkey. */
    public function test_user_can_rename_own_passkey_without_password_confirmation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = $this->createPasskey($user, 'Old name');

        $this->actingAs($user)
            ->patchJson(route('user.passkeys.rename', $passkey), ['name' => 'New name'])
            ->assertOk()
            ->assertJsonPath('name', 'New name');

        self::assertSame('New name', $passkey->fresh()->name);
    }

    /** A user cannot rename or revoke credentials belonging to another account. */
    public function test_user_cannot_manage_another_accounts_passkey(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        /** @var User $attacker */
        $attacker = User::factory()->create();
        $passkey = $this->createPasskey($owner, 'Private key');

        $this->actingAs($attacker)
            ->patchJson(route('user.passkeys.rename', $passkey), ['name' => 'Stolen'])
            ->assertNotFound();

        self::assertSame('Private key', $passkey->fresh()->name);
        self::assertDatabaseHas('passkeys', ['id' => $passkey->getKey()]);
    }

    /** An authenticated owner can revoke their own passkey. */
    public function test_user_can_revoke_own_passkey_without_password_confirmation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = $this->createPasskey($user, 'Retired key');

        $this->actingAs($user)
            ->deleteJson(route('user.passkeys.destroy', $passkey))
            ->assertNoContent();

        self::assertDatabaseMissing('passkeys', ['id' => $passkey->getKey()]);
    }

    /** Create a credential fixture without storing real authenticator secrets. */
    private function createPasskey(User $user, string $name): Passkey
    {
        return $user->passkeys()->create([
            'name' => $name,
            'credential_id' => 'opaque-credential-'.uniqid(),
            'credential' => ['publicKey' => 'fixture-only'],
        ]);
    }
}
