<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Feature;

use App\Models\People\TwoFactorRecoveryAudit;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Tests\TestCase;

class UserWebFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function test_index_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('users.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/User/Index')
                ->has('users', 1)
                ->missing('users.0.address')
                ->missing('users.0.api_token')
                ->missing('users.0.created_at')
                ->missing('users.0.updated_at')
            );
    }

    /** The profile exposes only non-sensitive factor state to the UI. */
    public function test_profile_exposes_two_factor_state_without_secrets(): void
    {
        $this->actingAs($this->user)
            ->get(route('user.profile'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/ProfileEditor')
                ->where('twoFactorEnabled', false)
                ->where('twoFactorPending', false)
                ->missing('user.two_factor_secret')
                ->missing('user.two_factor_recovery_codes')
            );
    }

    /** A legacy password-change flag no longer blocks passwordless account access. */
    public function test_legacy_password_change_flag_does_not_intercept_profile_routes(): void
    {
        $this->user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($this->user)
            ->get(route('user.profile'))
            ->assertOk();
    }

    /** The administration can save the durable official-account classification. */
    public function test_admin_can_persist_official_user_classification(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $target = User::factory()->create(['is_official_user' => false]);

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'isOfficialUser' => true,
            ])
            ->assertRedirect(route('users.index'));

        self::assertTrue($target->fresh()->isOfficialUser);
    }

    /** A correct password for a TOTP-enabled account must not authenticate the session yet. */
    /** Invalid passwords do not create a pending factor challenge. */
    /** Invalid login input is rejected by Laravel validation without exceptions. */
    /** A valid TOTP code is required before the login session is established. */
    /** A TOTP challenge rejects an invalid factor without authenticating. */
    /** A challenge created before an enrolment change cannot verify the retired secret. */
    /** A TOTP timestep cannot be reused across separate login challenges. */
    /** A recovery code completes login and cannot be used for a second login. */
    /** TOTP challenges offer no email-code route or substitute. */
    /** Pending factor state expires even while its session remains active. */
    /** A new password-only login clears an earlier pending factor challenge. */
    public function test_editor_loads(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/User/UserEditor')
                ->has('users', 2)
                ->missing('users.0.address')
                ->missing('users.0.api_token')
                ->missing('user.password')
                ->missing('user.created_at')
                ->missing('user.updated_at')
            );
    }

    /** Only a Super-Admin editing another account receives recovery controls and history. */
    public function test_only_super_admin_receives_recovery_action_and_history(): void
    {
        $target = User::factory()->create();
        $targetSetup = $target->startTwoFactorEnrolment();
        $target->confirmTwoFactor(app(TwoFactorService::class)->currentCode($targetSetup->secret));
        $this->enableTwoFactor($this->user);
        config(['session.driver' => 'database']);
        $actor = User::factory()->create();
        $actor->assignRole(RoleService::ROLE_ADMIN);
        $this->actingAs($actor)
            ->get(route('user.edit', $target->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorRecovery.canRecover', false)
                ->where('twoFactorRecovery.history', [])
            );

        $this->actingAs($this->user)
            ->get(route('user.edit', $this->user->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorRecovery.canRecover', false)
                ->where('twoFactorRecovery.history', [])
            );

        config(['session.connection' => config('database.default')]);
        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorRecovery.canRecover', true)
            );
    }

    /** Recovery controls are hidden when the session backend cannot revoke sessions. */
    public function test_recovery_action_is_hidden_for_unsupported_session_driver(): void
    {
        $target = User::factory()->create();
        $target->startTwoFactorEnrolment();
        $this->enableTwoFactor($this->user);
        config(['session.driver' => 'file']);

        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorRecovery.canRecover', false)
                ->where('twoFactorRecovery.unavailableReason', 'Die Wiederherstellung erfordert Datenbank-Sitzungen mit derselben Datenbankverbindung wie die Anwendung.')
            );
    }

    /** Recovery stays available with a local cache because counters use the database. */
    public function test_recovery_action_supports_local_file_cache(): void
    {
        $target = User::factory()->create();
        $target->startTwoFactorEnrolment();
        $this->enableTwoFactor($this->user);
        config([
            'cache.default' => 'file',
            'session.driver' => 'database',
        ]);
        app()->instance('env', 'local');

        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorRecovery.canRecover', true)
                ->where('twoFactorRecovery.unavailableReason', null)
            );
    }

    /** Recovery is unavailable when the database sessions use a different connection. */
    public function test_recovery_action_is_hidden_for_mismatched_session_connection(): void
    {
        $target = User::factory()->create();
        $target->startTwoFactorEnrolment();
        $this->actingAs($this->user);
        $this->enableTwoFactor($this->user);
        config([
            'session.driver' => 'database',
            'session.connection' => 'other',
            'database.default' => 'sqlite',
            'database.connections.other' => config('database.connections.sqlite'),
        ]);
        Schema::connection('other')->create('sessions', function ($table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        $this->get(route('user.edit', $target->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorRecovery.canRecover', false)
                ->where('twoFactorRecovery.unavailableReason', 'Die Wiederherstellung erfordert Datenbank-Sitzungen mit derselben Datenbankverbindung wie die Anwendung.')
            );
    }

    /** Recovery controls are not offered for accounts without a pending or active factor. */
    public function test_recovery_action_is_hidden_when_target_has_no_recoverable_factor(): void
    {
        $target = User::factory()->create();
        config(['session.driver' => 'database']);

        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorRecovery.canRecover', false)
                ->where('twoFactorRecovery.unavailableReason', null)
            );
    }

    /** Recovery history exposes only actor identity, time, and the support reference. */
    public function test_recovery_history_contains_actor_time_and_reference_only(): void
    {
        $target = User::factory()->create();
        TwoFactorRecoveryAudit::query()->create([
            'actor_user_id' => $this->user->id,
            'target_user_id' => $target->id,
            'support_reference' => 'SUP-80-2026',
            'ip_address' => '192.0.2.12',
        ]);

        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('twoFactorRecovery.history', 1)
                ->where('twoFactorRecovery.history.0.actor.id', $this->user->id)
                ->where('twoFactorRecovery.history.0.actor.name', $this->user->name)
                ->where('twoFactorRecovery.history.0.supportReference', 'SUP-80-2026')
                ->has('twoFactorRecovery.history.0.createdAt')
                ->missing('twoFactorRecovery.history.0.ipAddress')
                ->missing('twoFactorRecovery.history.0.password')
                ->missing('twoFactorRecovery.history.0.secret')
            );
    }

    public function test_join_loads_compact_people(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->get(route('user.join', $target->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/User/Join')
                ->has('people', 1)
                ->missing('user.address')
                ->missing('people.0.address')
                ->missing('people.0.api_token')
            );
    }

    public function test_updating_user_profile_without_password_keeps_existing_password_hash(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $target = User::factory()->create(['password' => 'long-term-password']);
        $passwordHash = $target->getRawOriginal('password');

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => null,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame($passwordHash, $target->fresh()->getRawOriginal('password'));
    }

    /** An administrator may issue a temporary password for an editable account. */
    /** An administrator cannot reset their own password through account administration. */
    /** A regular administrator cannot submit another user's password field. */
    /** The explicit manual assignment flag is forbidden outside the super-admin role. */
    /** A super administrator can explicitly assign and mail a temporary password. */
    /** The generated temporary password format is accepted only for a forced password change. */
    /** Manual Super-Admin assignment accepts any non-empty temporary password. */
    /** Personal TOTP setup and removal rely on authentication and the TOTP code, not a password. */
    public function test_two_factor_security_actions_do_not_require_a_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create(['password' => 'Current1!']);
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());

        $setupResponse = $this->actingAs($user)
            ->postJson(route('user.two-factor.start'), [])
            ->assertOk()
            ->assertJsonStructure(['provisioning_uri', 'recovery_codes']);

        self::assertTrue($user->fresh()->hasPendingTwoFactor());
        self::assertArrayNotHasKey('secret', $setupResponse->json());

        parse_str((string) parse_url($setupResponse->json('provisioning_uri'), PHP_URL_QUERY), $query);
        self::assertNotEmpty($query['secret'] ?? null);
        $code = app(TwoFactorService::class)->currentCode($query['secret']);
        $this->actingAs($user)
            ->postJson(route('user.two-factor.confirm'), ['code' => $code])
            ->assertOk();
        self::assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->postJson(route('user.two-factor.disable'), [])->assertOk();
        self::assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    /** Invalid TOTP input is generic and never becomes an internal server error. */
    public function test_two_factor_confirmation_rejects_invalid_codes_generically(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create();
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());
        $user->startTwoFactorEnrolment();

        $this->actingAs($user)
            ->postJson(route('user.two-factor.confirm'), ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code')
            ->assertJsonMissingPath('exception');

        self::assertTrue($user->fresh()->hasPendingTwoFactor());
    }

    /** State-changing endpoints retain CSRF protection. */
    public function test_two_factor_start_requires_csrf_protection(): void
    {
        $route = Route::getRoutes()->getByName('user.two-factor.start');

        self::assertNotNull($route);
        self::assertContains('web', $route->middleware());
        self::assertNotContains(PreventRequestForgery::class, $route->excludedMiddleware());
    }

    /** Repeated invalid confirmation attempts are throttled per user. */
    public function test_two_factor_confirmation_is_rate_limited(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create();
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());
        $user->startTwoFactorEnrolment();

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)
                ->postJson(route('user.two-factor.confirm'), ['code' => '000000'])
                ->assertUnprocessable();
        }

        $this->actingAs($user)
            ->postJson(route('user.two-factor.confirm'), ['code' => '000000'])
            ->assertStatus(429)
            ->assertJson(['message' => 'Zu viele Versuche. Bitte später erneut versuchen.']);
    }

    /** The personal factor routes require an authenticated user. */
    public function test_two_factor_routes_require_authentication(): void
    {
        $this->postJson(route('user.two-factor.start'), [])->assertUnauthorized();
    }

    /** A proxy session cannot alter the target account's personal login factors. */
    public function test_impersonated_session_cannot_change_personal_two_factor_settings(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create();
        $user->startTwoFactorEnrolment();

        $this->actingAs($user)
            ->withSession(['adminUserSwitchBack' => $this->user->id])
            ->postJson(route('user.two-factor.confirm'), ['code' => '000000'])
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['adminUserSwitchBack' => $this->user->id])
            ->postJson(route('user.two-factor.disable'), [])
            ->assertForbidden();

        self::assertTrue($user->fresh()->hasPendingTwoFactor());
    }

    /** A nested impersonation cannot replace the original administrator session marker. */
    public function test_impersonated_session_cannot_start_a_second_impersonation(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->withSession(['adminUserSwitchBack' => $this->user->id])
            ->post(route('user.switch', $target))
            ->assertForbidden();

        $this->assertSame($this->user->id, session('adminUserSwitchBack'));
    }

    /** A string false value is not treated as an account-creation request. */
    /** A confirmed initial password is hashed, forced to change, and mailed. */
    /** Account creation requires a password instead of generating an unseen replacement. */
    /** The manual-password flag cannot weaken validation while creating an account. */
    /** A regular administrator cannot create a user with a password. */
    public function test_updating_profile_without_email_keeps_existing_password_hash(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $target = User::factory()->create(['email' => null, 'password' => 'long-term-password']);
        $passwordHash = $target->getRawOriginal('password');

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => null,
                'password' => null,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame($passwordHash, $target->fresh()->getRawOriginal('password'));
    }

    /** The profile endpoint ignores password fields and never changes the account password. */
    public function test_profile_editor_cannot_change_the_account_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->user->update(['password' => 'Current1!', 'must_change_password' => 0]);

        $this->actingAs($this->user)
            ->patch(route('user.profile.save'), [
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'current_password' => 'Current1!',
                'new_password' => 'Voca1234!',
                'new_password_confirmation' => 'Voca1234!',
                'password' => 'Attempted1!',
            ])
            ->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('Current1!', $this->user->fresh()->password));
        $this->assertFalse((bool) $this->user->fresh()->must_change_password);
    }

    /** Enable the actor's TOTP factor for recovery authorization tests. */
    private function enableTwoFactor(User $user): void
    {
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $this->travel(61)->seconds();
    }
}
