<?php

namespace Tests\Feature;

use App\Actions\Security\RecoverUserTwoFactor;
use App\Http\Controllers\ICalController;
use App\Models\People\TwoFactorRecoveryAudit;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

/** Verifies the durable schema and model contract for 2FA recovery. */
final class TwoFactorRecoveryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private ?User $recoveryAdmin = null;

    private string $recoveryAdminCode = '';

    /** Recovery state and audit rows persist the expected non-secret fields. */
    public function test_recovery_schema_and_audit_model_are_ready(): void
    {
        self::assertTrue(Schema::hasColumns('users', [
            'two_factor_recovery_required_at',
            'two_factor_recovery_attempts',
            'two_factor_recovery_attempted_at',
        ]));
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
        $attemptMigration = require database_path(
            'migrations/2026_11_16_000002_add_two_factor_recovery_attempt_tracking_to_users.php',
        );

        try {
            $migration->down();
            self::fail('Recovery state and audit history must not be removed by rollback.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('vorwärtsgerichtet', $exception->getMessage());
        }

        try {
            $attemptMigration->down();
            self::fail('Recovery throttling state must not be removed by rollback.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('vorwärtsgerichtet', $exception->getMessage());
        }

        self::assertTrue(Schema::hasColumns('users', [
            'two_factor_recovery_required_at',
            'two_factor_recovery_attempts',
            'two_factor_recovery_attempted_at',
        ]));
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
            ->assertRedirect(route('user.edit', $target))
            ->assertSessionHas('message', 'Der Zwei-Faktor-Zugang wurde widerrufen. Das Konto muss TOTP erneut einrichten.')
            ->assertSessionHas('type', 'success');

        $this->get(route('user.edit', $target))
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.message', 'Der Zwei-Faktor-Zugang wurde widerrufen. Das Konto muss TOTP erneut einrichten.')
                ->where('flash.type', 'success')
            );

        $target->refresh();
        self::assertNull($target->two_factor_secret);
        self::assertNotNull($target->two_factor_recovery_required_at);
        self::assertSame($targetPassword, $target->password);
        self::assertSame((bool) $targetMustChangePassword, (bool) $target->must_change_password);
        self::assertSame(0, (int) $admin->fresh()->two_factor_recovery_attempts);
        self::assertNull($admin->fresh()->two_factor_recovery_attempted_at);
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

    /** Current TOTP confirmation is required and repeated failures are throttled. */
    public function test_recovery_requires_current_admin_totp_and_rate_limits_failures(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'database']);
        $payload = $this->validRecoveryPayload();
        $invalidReference = $payload;
        $invalidReference['support_reference'] = 'SUP 80';
        $this->actingAs($admin)->postJson(route('user.two-factor.recover', $target), $invalidReference)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('support_reference');

        $payload['code'] = '000000';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($admin)->postJson(route('user.two-factor.recover', $target), $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('code');
        }

        self::assertSame(5, (int) $admin->fresh()->two_factor_recovery_attempts);
        self::assertNotNull($admin->fresh()->two_factor_recovery_attempted_at);

        $this->actingAs($admin)->postJson(route('user.two-factor.recover', $target), $payload)
            ->assertStatus(429);
        self::assertSame(5, (int) $admin->fresh()->two_factor_recovery_attempts);
        self::assertNull($target->fresh()->two_factor_recovery_required_at);
        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
    }

    /** Recovery codes cannot substitute for a fresh TOTP step-up. */
    public function test_recovery_rejects_super_admin_recovery_codes_as_step_up(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'database']);

        $this->actingAs($admin)->postJson(route('user.two-factor.recover', $target), [
            'code' => $this->recoveryAdminCode,
            'support_reference' => 'SUP-2026-081',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');

        self::assertNull($target->fresh()->two_factor_recovery_required_at);
        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
    }

    /** Invalid TOTP attempts persist under the locked actor row. */
    public function test_recovery_persists_attempt_counter_inside_actor_transaction(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'database']);

        try {
            app(RecoverUserTwoFactor::class)->execute($admin, $target, '000000', 'SUP-2026-081', '192.0.2.1');
            self::fail('An incorrect TOTP code must be rejected.');
        } catch (ValidationException) {
            self::assertSame(1, (int) $admin->fresh()->two_factor_recovery_attempts);
        }

        self::assertNotNull($admin->fresh()->two_factor_recovery_attempted_at);
    }

    /** Pending login challenges and links are consumed and the audit stores no secrets. */
    public function test_recovery_consumes_pending_login_tokens_and_writes_secret_free_audit(): void
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
        DB::table('passwordless_login_links')->insert([
            'user_id' => $target->getKey(),
            'token_hash' => hash('sha256', 'single-use-login-token'),
            'expires_at' => now()->addMinutes(10),
            'consumed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config(['session.driver' => 'database']);

        $this->actingAs($admin)->post(route('user.two-factor.recover', $target), $this->validRecoveryPayload())
            ->assertRedirect();

        self::assertNotNull(DB::table('two_factor_login_challenges')->where('user_id', $target->getKey())->value('consumed_at'));
        self::assertNotNull(DB::table('passwordless_login_links')->where('user_id', $target->getKey())->value('consumed_at'));
        $audit = TwoFactorRecoveryAudit::query()->sole();
        self::assertArrayNotHasKey('code', $audit->getAttributes());
        self::assertArrayNotHasKey('secret', $audit->getAttributes());
        self::assertSame('SUP-2026-080', $audit->support_reference);
    }

    /** Recovery refuses non-database sessions before changing account state. */
    public function test_recovery_requires_database_sessions(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        config(['session.driver' => 'array']);

        try {
            app(RecoverUserTwoFactor::class)->execute(
                $admin,
                $target,
                $this->validRecoveryPayload()['code'],
                'SUP-2026-081',
                '192.0.2.1',
            );
            self::fail('Recovery must fail closed when database sessions are unavailable.');
        } catch (ServiceUnavailableHttpException $exception) {
            self::assertSame(503, $exception->getStatusCode());
        }

        self::assertNotNull($target->fresh()->two_factor_secret);
        self::assertNull($target->fresh()->two_factor_recovery_required_at);
        self::assertSame(0, TwoFactorRecoveryAudit::query()->count());
    }

    /** Recovery revokes sessions, Sanctum tokens, legacy API tokens, and remember-me credentials. */
    public function test_recovery_revokes_all_target_authentication_credentials(): void
    {
        [$admin, $target] = $this->createRecoveryPair();
        $target->startTwoFactorEnrolment();
        $target->forceFill([
            'api_token' => 'legacy-api-token-before-recovery',
            'remember_token' => 'remember-token-before-recovery',
        ])->save();
        $legacyApiToken = $target->api_token;
        $rememberToken = $target->remember_token;
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
        self::assertNotSame($rememberToken, $target->fresh()->remember_token);
        self::assertNotSame($legacyApiToken, $target->fresh()->api_token);
        self::assertNull(Auth::guard('web')->getProvider()->retrieveByToken($target->getKey(), $rememberToken));
        $this->withToken($legacyApiToken)->getJson(route('api.events.range', [
            'calendar' => 'unknown',
            'start' => '2026-01-01',
            'end' => '2026-01-31',
        ]))->assertUnauthorized();
    }

    /** iCalendar token lookup refuses accounts still blocked for recovery. */
    public function test_recovery_gate_rejects_legacy_ical_tokens(): void
    {
        $target = User::factory()->create([
            'api_token' => 'legacy-ical-token',
            'two_factor_recovery_required_at' => now(),
        ]);
        $controller = new ICalController;
        $method = new \ReflectionMethod($controller, 'checkToken');

        try {
            $method->invoke($controller, $target->api_token);
            self::fail('A recovery-gated account must not authenticate an iCalendar token.');
        } catch (\ReflectionException $exception) {
            throw $exception;
        } catch (HttpExceptionInterface $exception) {
            self::assertSame(401, $exception->getStatusCode());
        }
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

    /** A recovered account can only open reenrolment and logout. */
    public function test_recovered_account_can_only_open_reenrolment_and_logout_routes(): void
    {
        $user = $this->createRecoveredAccount();

        $this->actingAs($user)->get(route('home'))
            ->assertRedirect('/profil/zwei-faktor/wiederherstellen');

        $this->actingAs($user)->get('/profil/zwei-faktor/wiederherstellen')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/TwoFactorRecovery')
                ->missing('user.two_factor_secret')
                ->missing('user.two_factor_recovery_codes')
            );

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
    }

    /** Recovered accounts cannot use authenticated API routes until reenrolment. */
    public function test_recovered_account_cannot_call_authenticated_api_routes(): void
    {
        $user = $this->createRecoveredAccount();

        $this->actingAs($user, 'api')->getJson('/api/kalender/monat/2026-11-16')->assertForbidden();
    }

    /** API-token authentication is resolved before the recovery gate is enforced. */
    public function test_recovery_gate_blocks_the_legacy_api_guard_token(): void
    {
        $user = $this->createRecoveredAccount();
        $user->forceFill(['api_token' => 'gated-api-token'])->save();

        $this->withToken($user->api_token)->getJson(route('api.events.range', [
            'calendar' => 'unknown',
            'start' => '2026-01-01',
            'end' => '2026-01-31',
        ]))->assertForbidden();
    }

    /** Guests and ordinary accounts do not inherit the recovery gate. */
    public function test_recovery_gate_does_not_block_guests_or_normal_accounts(): void
    {
        $this->get(route('login'))->assertOk();

        $user = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($user)->get(route('home'))->assertOk();
    }

    /** Invalid or incomplete TOTP confirmation keeps the recovery marker set. */
    public function test_failed_or_incomplete_totp_confirmation_keeps_recovery_gate(): void
    {
        $user = $this->createRecoveredAccount();
        RateLimiter::clear('two-factor-reauth:'.$user->getKey());
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());

        $this->actingAs($user)->postJson(route('user.two-factor.confirm'), [
            'current_password' => 'user-password',
            'code' => '123456',
        ])->assertUnprocessable();

        self::assertNotNull($user->fresh()->two_factor_recovery_required_at);

        $setup = $user->fresh()->startTwoFactorEnrolment();
        $this->actingAs($user)->postJson(route('user.two-factor.confirm'), [
            'current_password' => 'user-password',
            'code' => '000000',
        ])->assertUnprocessable();

        self::assertNotNull($user->fresh()->two_factor_recovery_required_at);
        self::assertNotNull($user->fresh()->two_factor_secret);
        self::assertNotSame('', $setup->secret);
    }

    /** A valid TOTP confirmation atomically releases the recovery marker. */
    public function test_successful_totp_confirmation_releases_recovery_gate(): void
    {
        $user = $this->createRecoveredAccount();
        $setup = $user->startTwoFactorEnrolment();
        RateLimiter::clear('two-factor-reauth:'.$user->getKey());
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());

        $confirmationResponse = $this->actingAs($user)->postJson(route('user.two-factor.confirm'), [
            'current_password' => 'user-password',
            'code' => app(TwoFactorService::class)->currentCode($setup->secret),
        ]);
        self::assertSame(200, $confirmationResponse->status(), json_encode([
            'location' => $confirmationResponse->headers->get('Location'),
            'body' => $confirmationResponse->getContent(),
        ]));
        $confirmationResponse->assertJson(['enabled' => true]);

        self::assertNull($user->fresh()->two_factor_recovery_required_at);
        self::assertTrue($user->fresh()->hasTwoFactorEnabled());
        $user->refresh();
        $this->actingAs($user)->get(route('home'))->assertOk();
    }

    /** Create an administrator and target account for recovery endpoint tests. */
    private function createRecoveryPair(bool $superAdmin = true): array
    {
        $admin = User::factory()->create([
            'password' => Hash::make('admin-password'),
            'must_change_password' => false,
        ]);
        if ($superAdmin) {
            $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        }
        $setup = $admin->startTwoFactorEnrolment();
        $this->recoveryAdminCode = $setup->recoveryCodes[0];
        $admin->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $this->travel(61)->seconds();
        RateLimiter::clear('two-factor:App\\Models\\People\\User:'.$admin->getKey());
        $this->recoveryAdmin = $admin;

        return [$admin, User::factory()->create()];
    }

    /** Create a normal account whose next login requires two-factor reenrolment. */
    private function createRecoveredAccount(bool $mustChangePassword = false): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('user-password'),
            'must_change_password' => $mustChangePassword,
        ]);
        $user->forceFill(['two_factor_recovery_required_at' => now()])->save();

        return $user;
    }

    /** Return valid re-authentication and support-reference input. */
    private function validRecoveryPayload(): array
    {
        return [
            'code' => app(TwoFactorService::class)->currentCode((string) $this->recoveryAdmin?->two_factor_secret),
            'support_reference' => 'SUP-2026-080',
        ];
    }
}
