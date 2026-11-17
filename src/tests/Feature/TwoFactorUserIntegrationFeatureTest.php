<?php

namespace Tests\Feature;

use App\Console\Commands\DevBuilder\DemoBuilder;
use App\Models\People\User;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorAuthenticatable;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Tests\TestCase;

/**
 * Verifies that the application user is wired to the selected two-factor package.
 */
final class TwoFactorUserIntegrationFeatureTest extends TestCase
{
    /**
     * The application user implements the package contract and the migration
     * adds all state columns to the users table.
     */
    public function test_user_is_two_factor_authenticatable_and_schema_is_ready(): void
    {
        $user = new User;
        $casts = $user->getCasts();

        self::assertInstanceOf(TwoFactorAuthenticatable::class, $user);
        self::assertSame('encrypted', $casts['two_factor_secret']);
        self::assertSame('array', $casts['two_factor_recovery_codes']);
        self::assertSame('datetime', $casts['two_factor_confirmed_at']);
        self::assertTrue(Schema::hasColumns('users', [
            'two_factor_secret',
            'two_factor_recovery_codes',
            'two_factor_confirmed_at',
            'two_factor_last_used_timestep',
        ]));

        $user->setRawAttributes([
            'two_factor_secret' => 'encrypted-secret',
            'two_factor_recovery_codes' => ['hashed-recovery-code'],
            'two_factor_last_used_timestep' => 123,
        ]);

        $serialized = $user->toArray();

        self::assertArrayNotHasKey('two_factor_secret', $serialized);
        self::assertArrayNotHasKey('two_factor_recovery_codes', $serialized);
        self::assertArrayNotHasKey('two_factor_last_used_timestep', $serialized);
    }

    /** The user exposes a stable opaque identity and account labels to WebAuthn. */
    public function test_user_implements_passkey_identity_contract(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.test',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        self::assertInstanceOf(PasskeyUser::class, $user);
        self::assertSame('member@example.test', $user->getPasskeyUsername());
        self::assertNotSame('member@example.test', $user->getPasskeyDisplayName());
        self::assertNotSame((string) $user->getKey(), $user->getPasskeyUserHandle());
        self::assertSame($user->getPasskeyUserHandle(), $user->fresh()->getPasskeyUserHandle());
        self::assertFalse($user->hasPasskeysEnabled());
        self::assertSame(User::class, Passkeys::userModel());
        self::assertTrue(Schema::hasTable('passkeys'));
        self::assertTrue(app('router')->has('passkey.login'));
        self::assertTrue(app('router')->has('user.passkeys.options'));
        self::assertFalse(app('router')->has('passkey.store'));
    }

    /**
     * The package writes an encrypted TOTP secret and one-way recovery hashes.
     */
    public function test_enrolment_protects_secret_and_recovery_codes_at_rest(): void
    {
        $user = User::factory()->create();

        $setup = $user->startTwoFactorEnrolment('user@example.test', 'Pfarrplaner');
        $storedSecret = DB::table('users')->where('id', $user->getKey())->value('two_factor_secret');
        $storedRecoveryCodes = json_decode(
            (string) DB::table('users')->where('id', $user->getKey())->value('two_factor_recovery_codes'),
            true,
        );

        self::assertIsString($storedSecret);
        self::assertNotSame($setup->secret, $storedSecret);
        self::assertIsArray($storedRecoveryCodes);
        self::assertCount(count($setup->recoveryCodes), $storedRecoveryCodes);

        foreach ($setup->recoveryCodes as $recoveryCode) {
            self::assertTrue(
                collect($storedRecoveryCodes)->contains(
                    static fn (mixed $storedCode): bool => is_string($storedCode)
                        && Hash::check($recoveryCode, $storedCode),
                ),
            );
        }
    }

    /** Demo anonymization removes imported factors and pending email codes. */
    public function test_demo_user_anonymization_clears_two_factor_state(): void
    {
        $user = User::factory()->create();
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        DB::table('two_factor_login_challenges')->insert([
            'user_id' => $user->getKey(),
            'code_hash' => Hash::make('12345678'),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('passkeys')->insert([
            'user_id' => $user->getKey(),
            'name' => 'Demo computer',
            'credential_id' => 'opaque-demo-credential',
            'credential' => json_encode(['credential' => 'demo'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $builder = new DemoBuilder;
        $faker = new \ReflectionProperty($builder, 'faker');
        $faker->setValue($builder, Factory::create('de_DE'));
        $method = new \ReflectionMethod($builder, 'handleUsers');
        $method->invoke($builder, $user);

        self::assertFalse($user->fresh()->hasTwoFactorEnabled());
        self::assertSame(0, DB::table('two_factor_login_challenges')->where('user_id', $user->getKey())->count());
        self::assertSame(0, DB::table('passkeys')->where('user_id', $user->getKey())->count());
    }

    /** Demo anonymization removes recovery status and audit references. */
    public function test_demo_builder_clears_recovery_markers_and_audits(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->getKey())->update([
            'two_factor_recovery_required_at' => now(),
        ]);
        DB::table('two_factor_recovery_audits')->insert([
            'actor_user_id' => $user->getKey(),
            'target_user_id' => $user->getKey(),
            'support_reference' => 'SUP-2026-002',
            'ip_address' => '192.0.2.19',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('two_factor_recovery_audits')->insert([
            'actor_user_id' => 999999999,
            'target_user_id' => 999999998,
            'support_reference' => 'SUP-2026-ORPHAN',
            'ip_address' => '192.0.2.20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $builder = new DemoBuilder;
        $faker = new \ReflectionProperty($builder, 'faker');
        $faker->setValue($builder, Factory::create('de_DE'));
        $prepare = new \ReflectionMethod($builder, 'prepUsers');
        $prepare->invoke($builder);
        $method = new \ReflectionMethod($builder, 'handleUsers');
        $method->invoke($builder, $user);

        self::assertNull($user->fresh()->two_factor_recovery_required_at);
        self::assertSame(0, DB::table('two_factor_recovery_audits')->count());
    }
}
