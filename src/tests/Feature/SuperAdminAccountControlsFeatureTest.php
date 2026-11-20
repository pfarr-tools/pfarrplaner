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

use App\Http\Controllers\DAV\Backends\AuthBackend;
use App\Jobs\ProcessPasswordlessLoginLinkRequest;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use ReflectionMethod;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Tests\TestCase;

/** Covers privileged account controls available to Super-Administrators. */
final class SuperAdminAccountControlsFeatureTest extends TestCase
{
    /** A Super-Admin can lock and unlock another user's web account. */
    public function test_super_admin_can_lock_and_unlock_another_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);

        $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/konto/sperren')
            ->assertRedirect(route('user.edit', $target));

        self::assertDatabaseHas('users', [
            'id' => $target->getKey(),
            'is_locked' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/konto/entsperren')
            ->assertRedirect(route('user.edit', $target));

        self::assertDatabaseHas('users', [
            'id' => $target->getKey(),
            'is_locked' => false,
        ]);
    }

    /** Ordinary administrators cannot invoke Super-Admin account controls. */
    public function test_non_super_admin_cannot_lock_another_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);

        $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/konto/sperren')
            ->assertForbidden();

        self::assertFalse($target->fresh()->isLocked);
    }

    /** Super-Admins cannot change their own account or another Super-Admin. */
    public function test_super_admin_cannot_lock_self_or_another_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $otherSuperAdmin = User::factory()->create(['is_official_user' => true]);
        $otherSuperAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $this->actingAs($admin)
            ->post('/admin/benutzer/'.$admin->getKey().'/konto/sperren')
            ->assertForbidden();
        $this->actingAs($admin)
            ->post('/admin/benutzer/'.$otherSuperAdmin->getKey().'/konto/sperren')
            ->assertForbidden();

        self::assertFalse($admin->fresh()->isLocked);
        self::assertFalse($otherSuperAdmin->fresh()->isLocked);
    }

    /** Locked accounts cannot receive new passwordless web login links. */
    public function test_locked_accounts_do_not_receive_email_login_links(): void
    {
        Mail::fake();
        $user = User::factory()->create(['is_official_user' => true]);
        $user->forceFill(['is_locked' => true])->save();

        (new ProcessPasswordlessLoginLinkRequest((string) $user->email))->handle();

        Mail::assertNothingQueued();
        self::assertDatabaseCount('passwordless_login_links', 0);
    }

    /** A locked user's existing web session is rejected on the next protected request. */
    public function test_locked_account_cannot_continue_using_an_existing_web_session(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);

        $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/konto/sperren')
            ->assertRedirect(route('user.edit', $target));
        self::assertTrue($target->fresh()->isLocked);

        $this->actingAs($target)
            ->get('/profil')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** A login link issued before account lock cannot authenticate the locked user. */
    public function test_locked_account_cannot_redeem_an_existing_email_login_link(): void
    {
        $user = User::factory()->create(['is_official_user' => true]);
        $user->forceFill(['is_locked' => true])->save();
        $linkId = DB::table('passwordless_login_links')->insertGetId([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', 'link-issued-before-lock'),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['login.link.confirmation_id' => $linkId])
            ->from(route('login.link.confirm-form'))
            ->post(route('login.link.confirm'));

        $this->assertGuest();
        self::assertNotNull(DB::table('passwordless_login_links')->where('id', $linkId)->value('consumed_at'));
    }

    /** A Super-Admin can remove only account data while preserving the person record. */
    public function test_super_admin_can_reset_account_to_person_without_login(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create([
            'email' => 'person@example.test',
            'is_official_user' => true,
            'api_token' => 'calendar-token',
            'remember_token' => 'remember-before-reset',
        ]);
        $setup = $target->startTwoFactorEnrolment();
        $target->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $target->passkeys()->create([
            'name' => 'Arbeitslaptop',
            'credential_id' => 'credential-before-reset',
            'credential' => ['publicKey' => 'fixture-only'],
        ]);
        $target->createToken('calendar-client');
        $linkId = DB::table('passwordless_login_links')->insertGetId([
            'user_id' => $target->getKey(),
            'token_hash' => hash('sha256', 'link-before-reset'),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $challengeId = DB::table('two_factor_login_challenges')->insertGetId([
            'user_id' => $target->getKey(),
            'code_hash' => Hash::make('12345678'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete('/admin/benutzer/'.$target->getKey().'/konto')
            ->assertRedirect(route('user.edit', $target));

        $target->refresh();
        self::assertSame('person@example.test', $target->email);
        self::assertFalse($target->isOfficialUser);
        self::assertNull($target->password);
        self::assertNull($target->api_token);
        $davAuthentication = new ReflectionMethod(AuthBackend::class, 'validateUserPass');
        self::assertFalse($davAuthentication->invoke(new AuthBackend, 'person@example.test', 'calendar-token'));
        self::assertNotSame('remember-before-reset', $target->remember_token);
        self::assertNull($target->two_factor_secret);
        self::assertNull($target->two_factor_recovery_codes);
        self::assertDatabaseCount('passkeys', 0);
        self::assertDatabaseCount('personal_access_tokens', 0);
        self::assertNotNull(DB::table('passwordless_login_links')->where('id', $linkId)->value('consumed_at'));
        self::assertNotNull(DB::table('two_factor_login_challenges')->where('id', $challengeId)->value('consumed_at'));
    }

    /** A Super-Admin can invalidate all passkeys belonging to another account. */
    public function test_super_admin_can_revoke_all_target_passkeys(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);
        foreach (['Arbeitslaptop', 'Mobiltelefon'] as $name) {
            $target->passkeys()->create([
                'name' => $name,
                'credential_id' => 'credential-'.$name,
                'credential' => ['publicKey' => 'fixture-only'],
            ]);
        }

        $this->actingAs($admin)
            ->delete('/admin/benutzer/'.$target->getKey().'/passkeys')
            ->assertRedirect(route('user.edit', $target));

        self::assertDatabaseCount('passkeys', 0);
    }

    /** A target locked during the TOTP step cannot finish its pending web login. */
    public function test_locked_account_cannot_complete_an_existing_two_factor_challenge(): void
    {
        $user = User::factory()->create(['is_official_user' => true]);
        $setup = $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $this->travel(31)->seconds();
        $user->forceFill(['is_locked' => true])->save();
        $this->withSession(['login.challenge' => [
            'user_id' => $user->getKey(),
            'remember' => false,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]]);

        $this->post(route('login.challenge.verify'), [
            'code' => app(TwoFactorService::class)->currentCode($setup->secret),
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
