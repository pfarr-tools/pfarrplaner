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

use App\Mail\User\AccountData;
use App\Mail\User\TwoFactorLoginCode;
use App\Models\People\TwoFactorRecoveryAudit;
use App\Models\People\User;
use App\Services\PasswordService;
use App\Services\RoleService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /** A correct password for a TOTP-enabled account must not authenticate the session yet. */
    public function test_login_with_two_factor_enabled_user_stops_at_a_challenge(): void
    {
        $user = User::factory()->create([
            'email' => 'two-factor@example.test',
            'password' => 'Correct1!',
            'two_factor_secret' => 'secret',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['hashed-recovery-code'],
        ]);

        $response = $this->from('/login')->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('login.challenge'));
        $this->assertGuest();
        $response->assertSessionHas('login.challenge.user_id', $user->id);
        $response->assertSessionHas('login.challenge.remember', true);
        $response->assertSessionHas('login.challenge.expires_at');
        $response->assertSessionMissing('auth.password_confirmed_at');
        $response->assertCookieMissing(Auth::guard()->getRecallerName());

        $this->get(route('login.challenge'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertInertia(fn (Assert $page) => $page->component('Auth/LoginChallenge'));
    }

    /** Invalid passwords do not create a pending factor challenge. */
    public function test_invalid_password_does_not_create_two_factor_challenge(): void
    {
        $user = User::factory()->create([
            'email' => 'two-factor@example.test',
            'password' => 'Correct1!',
            'two_factor_secret' => 'secret',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['hashed-recovery-code'],
        ]);

        $this->from('/login')->post(route('login'), [
            'email' => $user->email,
            'password' => 'Wrong1!',
        ])->assertRedirect('/login')->assertSessionMissing('login.challenge.user_id');

        $this->assertGuest();
    }

    /** Invalid login input is rejected by Laravel validation without exceptions. */
    public function test_login_rejects_malformed_credentials(): void
    {
        $this->from('/login')->post(route('login'), [
            'email' => ['not-an-email'],
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->from('/login')->post(route('login'), [
            'email' => 'existing@example.test',
        ])->assertRedirect('/login')->assertSessionHasErrors('password');
    }

    /** A valid TOTP code is required before the login session is established. */
    public function test_two_factor_login_completes_with_a_valid_totp_code(): void
    {
        $user = User::factory()->create(['password' => 'Correct1!']);
        $loginThrottleKey = strtolower($user->email).'|127.0.0.1';
        RateLimiter::clear($loginThrottleKey);
        $this->from('/login')->post(route('login'), [
            'email' => $user->email,
            'password' => 'Wrong1!',
        ])->assertRedirect('/login');
        self::assertEquals(1, RateLimiter::attempts($loginThrottleKey));

        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $this->travel(31)->seconds();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));
        self::assertEquals(1, RateLimiter::attempts($loginThrottleKey));

        $this->from(route('login.challenge'))->post(route('login.challenge.verify'), [
            'code' => app(TwoFactorService::class)->currentCode($setup->secret),
        ])->assertRedirect('/')->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        self::assertEquals(0, RateLimiter::attempts($loginThrottleKey));
    }

    /** A TOTP challenge rejects an invalid factor without authenticating. */
    public function test_two_factor_login_rejects_an_invalid_totp_code(): void
    {
        $user = User::factory()->create(['password' => 'Correct1!']);
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));

        $this->from(route('login.challenge'))->post(route('login.challenge.verify'), [
            'code' => '000000',
        ])->assertRedirect(route('login.challenge'))->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    /** A challenge created before an enrolment change cannot verify the retired secret. */
    public function test_two_factor_login_reloads_factor_after_it_is_disabled(): void
    {
        $user = User::factory()->create(['password' => 'Correct1!']);
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $this->travel(31)->seconds();
        $code = app(TwoFactorService::class)->currentCode($setup->secret);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));

        $user->disableTwoFactor();
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.verify'), ['code' => $code])
            ->assertRedirect('/login');
        $this->assertGuest();
    }

    /** A TOTP timestep cannot be reused across separate login challenges. */
    public function test_two_factor_login_rejects_a_replayed_totp_code(): void
    {
        $user = User::factory()->create(['password' => 'Correct1!']);
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $this->travel(31)->seconds();
        $code = app(TwoFactorService::class)->currentCode($setup->secret);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));
        $this->post(route('login.challenge.verify'), ['code' => $code])->assertRedirect('/');

        Auth::logout();
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));

        $this->from(route('login.challenge'))
            ->post(route('login.challenge.verify'), ['code' => $code])
            ->assertRedirect(route('login.challenge'))
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    /** A recovery code completes login and cannot be used for a second login. */
    public function test_two_factor_login_consumes_recovery_codes_once(): void
    {
        $user = User::factory()->create(['password' => 'Correct1!']);
        $setup = $user->startTwoFactorEnrolment();
        $recoveryCode = $setup->recoveryCodes[0];
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));
        $this->post(route('login.challenge.verify'), ['code' => $recoveryCode])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        self::assertSame(count($setup->recoveryCodes) - 1, $user->fresh()->twoFactorRecoveryCodesRemaining());

        Auth::logout();
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.verify'), ['code' => $recoveryCode])
            ->assertRedirect(route('login.challenge'))
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    /** Email login codes are hashed at rest, expire, and are consumed once. */
    public function test_two_factor_login_completes_with_a_single_use_email_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['password' => 'Correct1!']);
        RateLimiter::clear('two-factor-email-login:'.$user->getKey());
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.email'))
            ->assertRedirect(route('login.challenge'));
        $this->get(route('login.challenge'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/LoginChallenge')
                ->where('flash.success', 'Ein Anmeldecode wurde per E-Mail versendet.')
            );
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.email'))
            ->assertRedirect(route('login.challenge'));
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.email'))
            ->assertRedirect(route('login.challenge'))
            ->assertSessionHasErrors('code');

        $sentCode = null;
        Mail::assertSent(TwoFactorLoginCode::class, function (TwoFactorLoginCode $mail) use (&$sentCode): bool {
            $sentCode = $mail->code;

            return true;
        });
        Mail::assertSent(TwoFactorLoginCode::class, 2);

        $challenge = DB::table('two_factor_login_challenges')
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();
        self::assertNotNull($sentCode);
        self::assertNotSame($sentCode, $challenge->code_hash);
        self::assertTrue(Hash::check($sentCode, $challenge->code_hash));

        $this->from(route('login.challenge'))
            ->post(route('login.challenge.verify'), ['code' => $sentCode])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        self::assertNotNull(DB::table('two_factor_login_challenges')->where('id', $challenge->id)->value('consumed_at'));
    }

    /** An expired email code cannot complete a pending login. */
    public function test_two_factor_login_rejects_an_expired_email_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['password' => 'Correct1!']);
        RateLimiter::clear('two-factor-email-login:'.$user->getKey());
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.email'))
            ->assertRedirect(route('login.challenge'));

        $code = null;
        Mail::assertSent(TwoFactorLoginCode::class, function (TwoFactorLoginCode $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });
        DB::table('two_factor_login_challenges')
            ->where('user_id', $user->getKey())
            ->update(['expires_at' => now()->subSecond()]);
        $this->from(route('login.challenge'))
            ->post(route('login.challenge.verify'), ['code' => $code])
            ->assertRedirect(route('login.challenge'))
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    /** Pending factor state expires even while its session remains active. */
    public function test_two_factor_login_challenge_expires(): void
    {
        $user = User::factory()->create([
            'password' => 'Correct1!',
            'two_factor_secret' => 'secret',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['hashed-recovery-code'],
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));

        $this->travel(6)->minutes();
        $this->get(route('login.challenge'))->assertNotFound();
        $this->assertGuest();
    }

    /** A new password-only login clears an earlier pending factor challenge. */
    public function test_password_only_login_replaces_stale_factor_challenge(): void
    {
        $factorUser = User::factory()->create([
            'password' => 'Correct1!',
            'two_factor_secret' => 'secret',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['hashed-recovery-code'],
        ]);
        $passwordOnlyUser = User::factory()->create(['password' => 'Other1!']);

        $this->post(route('login'), [
            'email' => $factorUser->email,
            'password' => 'Correct1!',
        ])->assertRedirect(route('login.challenge'));

        $this->post(route('login'), [
            'email' => $passwordOnlyUser->email,
            'password' => 'Other1!',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($passwordOnlyUser);
        $this->assertFalse(session()->has('login.challenge.user_id'));
    }

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
        $this->user->startTwoFactorEnrolment();
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

    public function test_admin_password_update_requires_a_strong_password_and_forces_change(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $target = User::factory()->create(['password' => 'Current1!']);

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => 'Weak1234',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Current1!', $target->fresh()->password));

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => 'Strong1!',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('Strong1!', $target->fresh()->password));
        $this->assertTrue((bool) $target->fresh()->must_change_password);
    }

    /** An administrator may issue a temporary password for an editable account. */
    public function test_admin_can_reset_another_editable_users_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('user.password.reset', $target))
            ->assertRedirect(route('users.index'));

        $this->assertTrue((bool) $target->fresh()->must_change_password);
        Mail::assertQueued(AccountData::class);
    }

    /** An administrator cannot reset their own password through account administration. */
    public function test_admin_cannot_reset_their_own_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);

        $this->actingAs($admin)
            ->post(route('user.password.reset', $admin))
            ->assertForbidden();
    }

    /** A regular administrator cannot submit another user's password field. */
    public function test_non_super_admin_cannot_set_another_users_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => 'Strong1!',
            ])
            ->assertForbidden();
    }

    /** The explicit manual assignment flag is forbidden outside the super-admin role. */
    public function test_non_super_admin_cannot_use_manual_password_flag_for_self(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $admin = User::factory()->create(['password' => 'Current1!']);
        $admin->assignRole(RoleService::ROLE_ADMIN);

        $this->actingAs($admin)
            ->patch(route('user.update', $admin), [
                'id' => $admin->id,
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'email' => $admin->email,
                'password' => 'Manual1!Pass',
                'manualPassword' => 1,
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('Current1!', $admin->fresh()->password));
    }

    /** A super administrator can explicitly assign and mail a temporary password. */
    public function test_super_admin_can_manually_set_another_users_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create();

        $this->actingAs($superAdmin)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => 'Manual1!Pass',
                'manualPassword' => 1,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('Manual1!Pass', $target->fresh()->password));
        $this->assertTrue((bool) $target->fresh()->must_change_password);
        Mail::assertQueued(AccountData::class);
    }

    /** The generated temporary password format is accepted only for a forced password change. */
    public function test_super_admin_can_manually_set_a_temporary_initial_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $target = User::factory()->create();
        $password = PasswordService::temporaryInitialPassword();

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => $password,
                'manualPassword' => 1,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check($password, $target->fresh()->password));
        $this->assertTrue((bool) $target->fresh()->must_change_password);
        Mail::assertQueued(AccountData::class);
    }

    /** Manual Super-Admin assignment accepts any non-empty temporary password. */
    public function test_super_admin_can_set_an_arbitrary_temporary_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $target = User::factory()->create();
        $password = 'x';

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => $password,
                'manualPassword' => 1,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check($password, $target->fresh()->password));
        $this->assertTrue((bool) $target->fresh()->must_change_password);
        Mail::assertQueued(AccountData::class);
    }

    /** Activation and deactivation require a fresh password re-authentication. */
    public function test_two_factor_security_actions_require_reauthentication(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create(['password' => 'Current1!']);
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());
        RateLimiter::clear('two-factor-reauth:'.$user->getKey());

        $this->actingAs($user)
            ->postJson(route('user.two-factor.start'), ['current_password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $setupResponse = $this->actingAs($user)
            ->postJson(route('user.two-factor.start'), ['current_password' => 'Current1!'])
            ->assertOk()
            ->assertJsonStructure(['provisioning_uri', 'recovery_codes']);

        $setup = $setupResponse->json();
        $freshUser = $user->fresh();

        self::assertTrue($freshUser->hasPendingTwoFactor());
        self::assertArrayNotHasKey('secret', $setup);

        $setupResponse = $this->actingAs($user)
            ->postJson(route('user.two-factor.start'), ['current_password' => 'Current1!'])
            ->assertOk()
            ->assertJsonStructure(['provisioning_uri', 'recovery_codes']);

        $setup = $setupResponse->json();
        $freshUser = $user->fresh();

        $code = app(TwoFactorService::class)->currentCode($freshUser->twoFactorSecret());

        $formattedCode = substr($code, 0, 3).' '.substr($code, 3);

        $this->actingAs($user)
            ->postJson(route('user.two-factor.confirm'), [
                'current_password' => 'Current1!',
                'code' => $formattedCode,
            ])
            ->assertOk();

        self::assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->postJson(route('user.two-factor.disable'), ['current_password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->actingAs($user)
            ->postJson(route('user.two-factor.disable'), ['current_password' => 'Current1!'])
            ->assertOk();

        self::assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    /** Invalid TOTP input is generic and never becomes an internal server error. */
    public function test_two_factor_confirmation_rejects_invalid_codes_generically(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create(['password' => 'Current1!']);
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());
        RateLimiter::clear('two-factor-reauth:'.$user->getKey());
        $user->startTwoFactorEnrolment();

        $this->actingAs($user)
            ->postJson(route('user.two-factor.confirm'), [
                'current_password' => 'Current1!',
                'code' => '000000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code')
            ->assertJsonMissingPath('exception');

        self::assertTrue($user->fresh()->hasPendingTwoFactor());
    }

    /** State-changing endpoints reject a request without a CSRF token. */
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
        $user = User::factory()->create(['password' => 'Current1!']);
        RateLimiter::clear('two-factor-confirm:'.$user->getKey());
        $user->startTwoFactorEnrolment();

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)
                ->postJson(route('user.two-factor.confirm'), [
                    'current_password' => 'Current1!',
                    'code' => '000000',
                ])
                ->assertUnprocessable();
        }

        $this->actingAs($user)
            ->postJson(route('user.two-factor.confirm'), [
                'current_password' => 'Current1!',
                'code' => '000000',
            ])
            ->assertStatus(429)
            ->assertJson(['message' => 'Zu viele Versuche. Bitte später erneut versuchen.']);
    }

    /** Password re-authentication itself is throttled before factor changes. */
    public function test_two_factor_reauthentication_is_rate_limited(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create(['password' => 'Current1!']);
        RateLimiter::clear('two-factor-reauth:'.$user->getKey());

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)
                ->postJson(route('user.two-factor.start'), ['current_password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->actingAs($user)
            ->postJson(route('user.two-factor.start'), ['current_password' => 'wrong'])
            ->assertStatus(429);
    }

    /** The personal factor routes require an authenticated user. */
    public function test_two_factor_routes_require_authentication(): void
    {
        $this->postJson(route('user.two-factor.start'), ['current_password' => 'Current1!'])
            ->assertUnauthorized();
    }

    /** Re-authentication throttling is applied independently to confirmation. */
    public function test_two_factor_confirmation_reauthentication_is_rate_limited(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create(['password' => 'Current1!']);
        RateLimiter::clear('two-factor-reauth:'.$user->getKey());
        $user->startTwoFactorEnrolment();

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)
                ->postJson(route('user.two-factor.confirm'), [
                    'current_password' => 'wrong',
                    'code' => '000000',
                ])
                ->assertUnprocessable();
        }

        $this->actingAs($user)
            ->postJson(route('user.two-factor.confirm'), [
                'current_password' => 'wrong',
                'code' => '000000',
            ])
            ->assertStatus(429);
    }

    /** Re-authentication throttling is applied independently to deactivation. */
    public function test_two_factor_disable_reauthentication_is_rate_limited(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create(['password' => 'Current1!']);
        RateLimiter::clear('two-factor-reauth:'.$user->getKey());
        $user->startTwoFactorEnrolment();

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)
                ->postJson(route('user.two-factor.disable'), ['current_password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->actingAs($user)
            ->postJson(route('user.two-factor.disable'), ['current_password' => 'wrong'])
            ->assertStatus(429);
    }

    /** A string false value is not treated as an account-creation request. */
    public function test_false_create_account_value_does_not_reset_another_users_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $target = User::factory()->create(['password' => 'Current1!']);

        $this->actingAs($admin)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'createAccount' => 'false',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertFalse((bool) $target->fresh()->must_change_password);
        Mail::assertNothingQueued();
    }

    /** A confirmed initial password is hashed, forced to change, and mailed. */
    public function test_create_account_uses_the_confirmed_initial_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $target = User::factory()->create(['password' => null]);
        $password = PasswordService::temporaryInitialPassword();

        $response = $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => 'new-account@example.com',
                'createAccount' => '1',
                'password' => $password,
            ])
            ->assertRedirect(route('users.index'));

        $fresh = $target->fresh();
        $this->assertTrue(Hash::check($password, $fresh->password));
        $this->assertTrue((bool) $fresh->must_change_password);
        Mail::assertQueued(AccountData::class);
        $this->assertStringNotContainsString($password, json_encode($response->getSession()->all()));
    }

    /** Account creation requires a password instead of generating an unseen replacement. */
    public function test_create_account_requires_a_confirmed_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($this->user)
            ->post(route('user.store'), [
                'first_name' => 'Neue',
                'last_name' => 'Person',
                'email' => 'missing-password@example.com',
                'createAccount' => '1',
            ])
            ->assertSessionHasErrors('password');
    }

    /** The manual-password flag cannot weaken validation while creating an account. */
    public function test_manual_password_flag_does_not_bypass_account_creation_password_rules(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $target = User::factory()->create(['password' => null]);

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => 'new-account@example.com',
                'createAccount' => '1',
                'manualPassword' => '1',
                'password' => 'x',
            ])
            ->assertSessionHasErrors('password');

        $this->assertNull($target->fresh()->password);
        Mail::assertNothingQueued();
    }

    /** A regular administrator cannot create a user with a password. */
    public function test_regular_admin_cannot_store_a_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);

        $this->actingAs($admin)
            ->post(route('user.store'), [
                'first_name' => 'Neue',
                'last_name' => 'Person',
                'email' => 'regular-admin@example.com',
                'password' => 'Voca1234!',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_reset_uses_a_temporary_password_and_sends_account_data(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
        $target = User::factory()->create(['email' => 'target@example.com']);

        $this->actingAs($this->user)
            ->post(route('user.password.reset', $target))
            ->assertRedirect(route('users.index'));

        $this->assertTrue((bool) $target->fresh()->must_change_password);
        Mail::assertQueued(AccountData::class);
    }

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

    public function test_profile_password_change_keeps_current_hash_until_validation(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->user->update(['password' => 'testtest', 'must_change_password' => 0]);

        $this->actingAs($this->user)
            ->patch(route('user.profile.save'), [
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'current_password' => 'testtest',
                'new_password' => 'Voca1234!',
                'new_password_confirmation' => 'Voca1234!',
            ])
            ->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('Voca1234!', $this->user->fresh()->password));
        $this->assertFalse((bool) $this->user->fresh()->must_change_password);
    }

    public function test_profile_password_change_rejects_password_without_symbol(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->user->update(['password' => 'Current1!']);

        $response = $this->actingAs($this->user)
            ->patch(route('user.profile.save'), [
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'current_password' => 'Current1!',
                'new_password' => 'Voca1234',
                'new_password_confirmation' => 'Voca1234',
            ]);

        $response->assertSessionHasErrors('new_password');

        $this->assertTrue(Hash::check('Current1!', $this->user->fresh()->password));
    }

    public function test_initial_password_can_be_changed_without_current_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->user->update(['password' => 'testtest', 'must_change_password' => 1]);

        $this->actingAs($this->user)
            ->post(route('password.change'), [
                'new_password' => 'Voca1234!',
                'new_password_confirmation' => 'Voca1234!',
            ])
            ->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('Voca1234!', $this->user->fresh()->password));
        $this->assertFalse((bool) $this->user->fresh()->must_change_password);
    }
}
