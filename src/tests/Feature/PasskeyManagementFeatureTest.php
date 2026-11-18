<?php

namespace Tests\Feature;

use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
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
