<?php

namespace Tests\Feature;

use App\Models\People\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorAuthenticatable;
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
}
