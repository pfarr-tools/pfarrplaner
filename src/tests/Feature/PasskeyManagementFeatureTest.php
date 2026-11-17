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

    /** Registration requires a recent password confirmation. */
    public function test_registration_options_require_password_confirmation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('user.passkeys.options'))
            ->assertStatus(423);
    }

    /** Password confirmation restores passkey management in the same session. */
    public function test_registration_continues_after_password_confirmation(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['password' => 'Current1!']);

        $this->actingAs($user)
            ->getJson(route('user.passkeys.options'))
            ->assertStatus(423);

        $this->post(route('password.confirm.store'), [
            'password' => 'Current1!',
            'redirect' => '/profile?tab=security',
        ])->assertRedirect('/profile?tab=security')
            ->assertSessionHas('auth.password_confirmed_at');

        $this->getJson(route('user.passkeys.options'))->assertOk();
    }

    /** Correct confirmation enables protected actions and returns locally. */
    public function test_password_confirmation_sets_the_fresh_confirmation_timestamp(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['password' => 'Current1!']);

        $this->actingAs($user)
            ->post(route('password.confirm.store'), [
                'password' => 'Current1!',
                'redirect' => '/profile?tab=security',
            ])
            ->assertRedirect('/profile?tab=security')
            ->assertSessionHas('auth.password_confirmed_at');
    }

    /** JSON confirmation supports profile dialogs without following a redirect. */
    public function test_json_password_confirmation_returns_a_confirmed_result(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['password' => 'Current1!']);

        $this->actingAs($user)
            ->postJson(route('password.confirm.store'), ['password' => 'Current1!'])
            ->assertOk()
            ->assertJsonPath('confirmed', true)
            ->assertSessionHas('auth.password_confirmed_at');
    }

    /** JSON password errors are field validation responses and do not confirm the session. */
    public function test_json_password_confirmation_rejects_a_wrong_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['password' => 'Current1!']);

        $this->actingAs($user)
            ->postJson(route('password.confirm.store'), ['password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password')
            ->assertSessionMissing('auth.password_confirmed_at');
    }

    /** A wrong or external confirmation return path cannot reveal or redirect credentials. */
    public function test_password_confirmation_rejects_wrong_password_and_external_redirects(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['password' => 'Current1!']);

        $this->actingAs($user)
            ->from(route('password.confirm'))
            ->post(route('password.confirm.store'), ['password' => 'wrong'])
            ->assertRedirect(route('password.confirm'))
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('auth.password_confirmed_at');

        $response = $this->actingAs($user)->post(route('password.confirm.store'), [
            'password' => 'Current1!',
            'redirect' => '//evil.example/path',
        ]);

        self::assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
    }

    /** Registration options require verified user presence and a fresh ceremony. */
    public function test_confirmed_user_receives_discoverable_verified_registration_options(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->getJson(route('user.passkeys.options'))
            ->assertOk()
            ->assertJsonPath('options.authenticatorSelection.userVerification', 'required')
            ->assertJsonPath('options.authenticatorSelection.residentKey', 'required');

        $challenge = session('passkey.registration.challenge');
        self::assertIsArray($challenge);
        self::assertSame('registration', $challenge['purpose']);
        self::assertSame(60, $challenge['expires_at'] - $challenge['issued_at']);
    }

    /** Credential lists never return stored public key or credential data. */
    public function test_passkey_list_returns_only_safe_management_fields(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = $this->createPasskey($user, 'Office key');

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->getJson(route('user.passkeys.index'))
            ->assertOk()
            ->assertJsonPath('passkeys.0.name', 'Office key');

        self::assertSame($passkey->getKey(), $response->json('passkeys.0.id'));
        self::assertArrayNotHasKey('credential', $response->json('passkeys.0'));
        self::assertArrayNotHasKey('credential_id', $response->json('passkeys.0'));
    }

    /** A user may rename only their own credential after fresh confirmation. */
    public function test_user_can_rename_own_passkey(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = $this->createPasskey($user, 'Old name');

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
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
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->patchJson(route('user.passkeys.rename', $passkey), ['name' => 'Stolen'])
            ->assertNotFound();

        self::assertSame('Private key', $passkey->fresh()->name);
        self::assertDatabaseHas('passkeys', ['id' => $passkey->getKey()]);
    }

    /** A confirmed owner can revoke their own passkey. */
    public function test_user_can_revoke_own_passkey(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $passkey = $this->createPasskey($user, 'Retired key');

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
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
