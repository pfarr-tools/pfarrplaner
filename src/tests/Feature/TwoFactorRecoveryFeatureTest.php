<?php

namespace Tests\Feature;

use App\Models\People\TwoFactorRecoveryAudit;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Tests\TestCase;

/** Verifies the durable schema and model contract for 2FA recovery. */
final class TwoFactorRecoveryFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** Recovery state and audit rows persist the expected non-secret fields. */
    public function test_recovery_schema_and_audit_model_are_ready(): void
    {
        self::assertTrue(Schema::hasColumn('users', 'two_factor_recovery_required_at'));
        self::assertTrue(Schema::hasTable('two_factor_recovery_audits'));
        self::assertTrue(Schema::hasColumns('two_factor_recovery_audits', [
            'id',
            'actor_user_id',
            'target_user_id',
            'support_reference',
            'ip_address',
            'created_at',
            'updated_at',
        ]));

        $actor = User::factory()->create();
        $target = User::factory()->create();
        $audit = TwoFactorRecoveryAudit::query()->create([
            'actor_user_id' => $actor->getKey(),
            'target_user_id' => $target->getKey(),
            'support_reference' => 'SUP-2026-001',
            'ip_address' => '192.0.2.18',
        ]);

        self::assertSame($actor->getKey(), $audit->actor->getKey());
        self::assertSame($target->getKey(), $audit->target->getKey());
        self::assertSame('SUP-2026-001', $audit->fresh()->support_reference);
    }

    /** The recovery migration refuses to destroy security state on rollback. */
    public function test_recovery_migration_is_forward_only(): void
    {
        $migration = require database_path(
            'migrations/2026_11_16_000001_add_two_factor_recovery_state_and_audit_table.php',
        );

        try {
            $migration->down();
            self::fail('Recovery state and audit history must not be removed by rollback.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('vorwärtsgerichtet', $exception->getMessage());
        }

        self::assertTrue(Schema::hasColumn('users', 'two_factor_recovery_required_at'));
        self::assertTrue(Schema::hasTable('two_factor_recovery_audits'));
    }

    /** Account deletion preserves audit attribution by immutable internal ID. */
    public function test_audit_survives_target_account_deletion(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $targetId = $target->getKey();
        $audit = TwoFactorRecoveryAudit::query()->create([
            'actor_user_id' => $actor->getKey(),
            'target_user_id' => $targetId,
            'support_reference' => 'SUP-2026-002',
            'ip_address' => '192.0.2.19',
        ]);

        $target->delete();

        $retainedAudit = $audit->fresh();
        self::assertNotNull($retainedAudit);
        self::assertSame($targetId, $retainedAudit->target_user_id);
        self::assertNull($retainedAudit->target);
    }

    /** A super-admin can recover another account with a pending factor. */
    public function test_super_admin_can_recover_another_users_two_factor(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        $targetPassword = $target->password;
        $targetMustChangePassword = $target->must_change_password;
        config(['session.driver' => 'database']);

        $this->actingAs($admin)->post(route('user.two-factor.recover', $target), $this->validRecoveryPayload())
            ->assertRedirect();

        $target->refresh();
        self::assertNull($target->two_factor_secret);
        self::assertNotNull($target->two_factor_recovery_required_at);
        self::assertSame($targetPassword, $target->password);
        self::assertSame((bool) $targetMustChangePassword, (bool) $target->must_change_password);
        self::assertSame(1, TwoFactorRecoveryAudit::query()->count());
        self::assertDatabaseHas('two_factor_recovery_audits', [
            'actor_user_id' => $admin->getKey(),
            'target_user_id' => $target->getKey(),
            'support_reference' => 'SUP-2026-080',
        ]);
        self::assertTrue(Hash::check('admin-password', $admin->fresh()->password));
    }

    /** Regular admins and self-recovery requests are forbidden. */
    public function test_recovery_rejects_non_super_admin_and_self_recovery(): void
    {
        [$regularAdmin, $target] = $this->createRecoveryPair(false);
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'database']);

        $this->actingAs($regularAdmin)->postJson(
            route('user.two-factor.recover', $target),
            $this->validRecoveryPayload(),
        )->assertForbidden();

        $superAdmin = User::factory()->create([
            'password' => Hash::make('admin-password'),
            'must_change_password' => false,
        ]);
        $superAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        RateLimiter::clear('two-factor-recovery:'.$superAdmin->getKey());
        $superAdmin->startTwoFactorEnrolment();
        self::assertFalse((bool) $superAdmin->must_change_password);
        $selfRecoveryResponse = $this->actingAs($superAdmin)->postJson(
            route('user.two-factor.recover', $superAdmin),
            $this->validRecoveryPayload(),
        );
        self::assertSame(403, $selfRecoveryResponse->status(), (string) $selfRecoveryResponse->headers->get('Location'));

        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
    }

    /** Accounts without a pending or confirmed TOTP factor cannot be recovered. */
    public function test_recovery_requires_active_or_pending_factor(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        config(['session.driver' => 'database']);

        $this->actingAs($admin)->postJson(
            route('user.two-factor.recover', $target),
            $this->validRecoveryPayload(),
        )->assertUnprocessable();

        self::assertNull($target->fresh()->two_factor_recovery_required_at);
        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
    }

    /** Confirmed TOTP factors can also be revoked through recovery. */
    public function test_recovery_can_revoke_an_active_factor(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $setup = $target->startTwoFactorEnrolment();
        $target->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        config(['session.driver' => 'database']);

        $this->actingAs($admin)->post(route('user.two-factor.recover', $target), $this->validRecoveryPayload())
            ->assertRedirect();

        self::assertNull($target->fresh()->two_factor_secret);
        self::assertNotNull($target->fresh()->two_factor_recovery_required_at);
    }

    /** Password confirmation is required and repeated failures are throttled. */
    public function test_recovery_requires_current_admin_password_and_rate_limits_failures(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'database']);
        RateLimiter::clear('two-factor-recovery:'.$admin->getKey());
        $payload = $this->validRecoveryPayload();
        $invalidReference = $payload;
        $invalidReference['support_reference'] = 'SUP 80';
        $this->actingAs($admin)->postJson(route('user.two-factor.recover', $target), $invalidReference)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('support_reference');

        $payload['current_password'] = 'incorrect-password';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($admin)->postJson(route('user.two-factor.recover', $target), $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('current_password');
        }

        $this->actingAs($admin)->postJson(route('user.two-factor.recover', $target), $payload)
            ->assertStatus(429);
        self::assertNull($target->fresh()->two_factor_recovery_required_at);
        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
    }

    /** A stale target model cannot bypass reloading its current factor state. */
    public function test_recovery_reloads_factor_after_admin_reauthentication(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        $staleTarget = User::query()->findOrFail($target->getKey());
        config(['session.driver' => 'database']);
        $expectedAdminHash = $admin->password;
        Hash::shouldReceive('check')->once()->andReturnUsing(
            static function (string $password, string $hash) use ($target, $expectedAdminHash): bool {
                DB::table('users')->where('id', $target->getKey())->update([
                    'two_factor_secret' => null,
                    'two_factor_confirmed_at' => null,
                ]);

                return $password === 'admin-password' && $hash === $expectedAdminHash;
            },
        );

        $raceResponse = $this->actingAs($admin)->postJson(
            route('user.two-factor.recover', $staleTarget),
            $this->validRecoveryPayload(),
        );
        self::assertSame(422, $raceResponse->status(), (string) $raceResponse->headers->get('Location'));

        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
        self::assertNull($target->fresh()->two_factor_recovery_required_at);
    }

    /** Pending email challenges are consumed and the audit stores no secrets. */
    public function test_recovery_consumes_pending_email_codes_and_writes_secret_free_audit(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        DB::table('two_factor_login_challenges')->insert([
            'user_id' => $target->getKey(),
            'code_hash' => Hash::make('12345678'),
            'expires_at' => now()->addMinutes(5),
            'consumed_at' => null,
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config(['session.driver' => 'database']);

        $this->actingAs($admin)->post(route('user.two-factor.recover', $target), $this->validRecoveryPayload())
            ->assertRedirect();

        self::assertNotNull(DB::table('two_factor_login_challenges')->where('user_id', $target->getKey())->value('consumed_at'));
        $audit = TwoFactorRecoveryAudit::query()->sole();
        self::assertArrayNotHasKey('current_password', $audit->getAttributes());
        self::assertArrayNotHasKey('secret', $audit->getAttributes());
        self::assertSame('SUP-2026-080', $audit->support_reference);
    }

    /** Recovery refuses non-database sessions before changing account state. */
    public function test_recovery_requires_database_sessions(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'array']);

        $this->actingAs($admin)->postJson(
            route('user.two-factor.recover', $target),
            $this->validRecoveryPayload(),
        )->assertStatus(503);

        self::assertNotNull($target->fresh()->two_factor_secret);
        self::assertNull($target->fresh()->two_factor_recovery_required_at);
        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
    }

    /** The action revokes target database sessions and Sanctum personal tokens. */
    public function test_recovery_revokes_database_sessions_and_personal_tokens(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'database']);
        $target->createToken('recovery-test');
        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->getKey(),
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->post(route('user.two-factor.recover', $target), $this->validRecoveryPayload())
            ->assertRedirect();

        self::assertDatabaseMissing('sessions', ['id' => 'target-session']);
        self::assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $target->getKey(),
            'name' => 'recovery-test',
        ]);
    }

    /** Audit failure rolls back factor revocation and recovery state. */
    public function test_recovery_rolls_back_factor_changes_when_audit_write_fails(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'database']);
        $this->withoutExceptionHandling();
        DB::statement("CREATE TRIGGER fail_recovery_audit BEFORE INSERT ON two_factor_recovery_audits BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");

        try {
            $this->actingAs($admin)->post(route('user.two-factor.recover', $target), $this->validRecoveryPayload());
            self::fail('An audit write failure must fail the recovery operation.');
        } catch (QueryException) {
            self::assertNotNull($target->fresh()->two_factor_secret);
            self::assertNull($target->fresh()->two_factor_recovery_required_at);
        }
    }

    /** Create an administrator and target account for recovery endpoint tests. */
    private function createRecoveryPair(bool $superAdmin = true): array
    {
        $admin = User::factory()->create([
            'password' => Hash::make('admin-password'),
            'must_change_password' => false,
        ]);
        RateLimiter::clear('two-factor-recovery:'.$admin->getKey());
        if ($superAdmin) {
            $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        }

        return [$admin, User::factory()->create()];
    }

    /** Return valid re-authentication and support-reference input. */
    private function validRecoveryPayload(): array
    {
        return [
            'current_password' => 'admin-password',
            'support_reference' => 'SUP-2026-080',
        ];
    }
}
