<?php

namespace Tests\Feature;

use App\Models\People\TwoFactorRecoveryAudit;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
}
