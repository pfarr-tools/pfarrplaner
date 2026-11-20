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

use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Tests\TestCase;

/** Cover creation and redemption of Super-Admin-issued passwordless links. */
final class ManualLoginLinkFeatureTest extends TestCase
{
    /** Isolate rate-limit state from other tests that reuse database user IDs. */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** Leave no rate-limit state for subsequent feature tests. */
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * A Super-Admin receives a one-hour URL while only its token hash is stored.
     */
    public function test_super_admin_can_create_hashed_one_hour_login_link_without_sending_mail(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);
        $now = Carbon::parse('2026-11-20 10:00:00', 'UTC');
        Carbon::setTestNow($now);
        $oldLinkId = DB::table('passwordless_login_links')->insertGetId([
            'user_id' => $target->getKey(),
            'token_hash' => hash('sha256', 'previous-manual-or-email-token'),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/login-link');

        $response->assertOk()
            ->assertJsonStructure(['manual_login_url', 'expires_at'])
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $url = $response->json('manual_login_url');
        self::assertIsString($url);
        self::assertMatchesRegularExpression('#/login/link/[a-f0-9]{64}$#', $url);
        $token = basename((string) parse_url($url, PHP_URL_PATH));
        $storedLink = DB::table('passwordless_login_links')->where('user_id', $target->getKey())->latest('id')->first();
        self::assertNotSame($token, $storedLink->token_hash);
        self::assertSame(hash('sha256', $token), $storedLink->token_hash);
        self::assertTrue(Carbon::parse($storedLink->expires_at, 'UTC')->equalTo($now->copy()->addHour()));
        self::assertNotNull(DB::table('passwordless_login_links')->where('id', $oldLinkId)->value('consumed_at'));
        Mail::assertNothingQueued();
    }

    /**
     * A locked, removed, self, or Super-Admin target cannot receive a manual link.
     */
    public function test_super_admin_cannot_issue_manual_link_for_ineligible_target(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $locked = User::factory()->create(['is_official_user' => true, 'is_locked' => true]);
        $person = User::factory()->create(['is_official_user' => false]);
        $otherSuperAdmin = User::factory()->create(['is_official_user' => true]);
        $otherSuperAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $this->actingAs($admin)->withHeader('Accept', 'application/json')
            ->post('/admin/benutzer/'.$locked->getKey().'/login-link')->assertUnprocessable();
        $this->actingAs($admin)->withHeader('Accept', 'application/json')
            ->post('/admin/benutzer/'.$person->getKey().'/login-link')->assertUnprocessable();
        $this->actingAs($admin)->withHeader('Accept', 'application/json')
            ->post('/admin/benutzer/'.$otherSuperAdmin->getKey().'/login-link')->assertForbidden();
        $this->actingAs($admin)->withHeader('Accept', 'application/json')
            ->post('/admin/benutzer/'.$admin->getKey().'/login-link')->assertForbidden();

        self::assertDatabaseCount('passwordless_login_links', 0);
    }

    /**
     * A regular administrator cannot request a link on behalf of a user.
     */
    public function test_non_super_admin_cannot_create_manual_login_link(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/benutzer/'.$target->getKey().'/login-link')
            ->assertForbidden();

        self::assertDatabaseCount('passwordless_login_links', 0);
    }

    /**
     * Passkey-management throttles do not consume manual-link request capacity.
     */
    public function test_manual_login_link_has_an_independent_security_rate_limit(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->actingAs($admin)->get(route('user.passkeys.index'))->assertOk();
        }

        $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/login-link')
            ->assertOk();
    }

    /**
     * A generated login link is consumed by its first completed sign-in.
     */
    public function test_manual_login_link_can_only_be_used_once(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);
        $response = $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/login-link')
            ->assertOk();
        $url = $response->json('manual_login_url');

        Auth::logout();
        $this->get($url)->assertRedirect(route('login.link.confirm-form'));
        $this->post(route('login.link.confirm'))->assertRedirect('/');
        $this->assertAuthenticatedAs($target);

        Auth::logout();
        $this->get($url)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * A generated link is rejected once its 60-minute lifetime has passed.
     */
    public function test_expired_manual_login_link_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['is_official_user' => true]);
        $response = $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/login-link')
            ->assertOk();
        $url = $response->json('manual_login_url');

        $this->travel(61)->minutes();
        Auth::logout();
        $this->get($url)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * A generated link follows the usual TOTP challenge instead of bypassing it.
     */
    public function test_manual_login_link_preserves_target_totp_challenge(): void
    {
        $target = User::factory()->create(['is_official_user' => true]);
        $setup = $target->startTwoFactorEnrolment();
        $target->confirmTwoFactor(app(TwoFactorService::class)->currentCode($setup->secret));
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $response = $this->actingAs($admin)
            ->post('/admin/benutzer/'.$target->getKey().'/login-link')
            ->assertOk();

        Auth::logout();
        $this->get($response->json('manual_login_url'))
            ->assertRedirect(route('login.link.confirm-form'));
        $this->post(route('login.link.confirm'))
            ->assertRedirect(route('login.challenge'));

        $this->assertGuest();
        self::assertSame($target->getKey(), session('login.challenge.user_id'));
    }
}
