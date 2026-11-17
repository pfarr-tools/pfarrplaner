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

namespace Tests\Unit\Services;

use App\Models\People\User;
use App\Models\Places\City;
use App\Services\InterstitialService;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InterstitialServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    private InterstitialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InterstitialService::class);
    }

    public function test_returns_interstitial_for_all_users(): void
    {
        config()->set('interstitials.items', [
            'general-change' => [
                'enabled' => true,
                'audience' => 'all',
                'title' => 'Allgemeiner Hinweis',
                'text' => 'Dieser Hinweis gilt für alle.',
            ],
        ]);

        $user = User::factory()->create();

        $result = $this->service->forUser($user);

        $this->assertCount(1, $result);
        $this->assertSame('general-change', $result[0]['key']);
    }

    public function test_admin_audience_matches_local_admins(): void
    {
        config()->set('interstitials.items', [
            'admin-change' => [
                'enabled' => true,
                'audience' => 'admin',
                'title' => 'Admin-Hinweis',
            ],
        ]);

        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'a']);

        $result = $this->service->forUser($user);

        $this->assertCount(1, $result);
        $this->assertSame('admin-change', $result[0]['key']);
    }

    public function test_writer_audience_requires_writable_city(): void
    {
        config()->set('interstitials.items', [
            'writer-change' => [
                'enabled' => true,
                'audience' => 'writer',
                'title' => 'Schreib-Hinweis',
            ],
        ]);

        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $result = $this->service->forUser($user);

        $this->assertCount(1, $result);
        $this->assertSame('writer-change', $result[0]['key']);
    }

    public function test_dismissed_interstitial_is_not_returned(): void
    {
        config()->set('interstitials.items', [
            'dismissable-change' => [
                'enabled' => true,
                'audience' => 'all',
                'title' => 'Hinweis',
            ],
        ]);

        $user = User::factory()->create();
        $this->service->remember($user, 'dismissable-change', InterstitialService::ACTION_DISMISS);

        $result = $this->service->forUser($user);

        $this->assertSame([], $result);
    }

    public function test_super_admin_matches_admin_audience(): void
    {
        config()->set('interstitials.items', [
            'admin-change' => [
                'enabled' => true,
                'audience' => 'admin',
                'title' => 'Admin-Hinweis',
            ],
        ]);

        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $result = $this->service->forUser($user);

        $this->assertCount(1, $result);
        $this->assertSame('admin-change', $result[0]['key']);
    }

    /** Password-only reminders disappear when a user has TOTP or a passkey. */
    public function test_password_only_audience_excludes_accounts_with_an_additional_factor(): void
    {
        config()->set('interstitials.items', [
            'security-beyond-password' => [
                'enabled' => true,
                'audience' => 'password-only',
                'title' => 'Sicherheit erhöhen',
            ],
        ]);

        $passwordOnly = User::factory()->create();
        $totpUser = User::factory()->create([
            'two_factor_secret' => 'fixture-secret',
            'two_factor_confirmed_at' => now(),
        ]);
        $passkeyUser = User::factory()->create();
        $passkeyUser->passkeys()->create([
            'name' => 'Testgerät',
            'credential_id' => 'fixture-credential-id',
            'credential' => ['publicKey' => 'fixture-only'],
        ]);

        $this->assertCount(1, $this->service->forUser($passwordOnly));
        $this->assertSame([], $this->service->forUser($totpUser));
        $this->assertSame([], $this->service->forUser($passkeyUser));
    }

    /** Forced password changes and pending TOTP enrollment take precedence over nagging. */
    public function test_password_change_and_pending_totp_exclude_security_recommendation(): void
    {
        config()->set('interstitials.items', [
            'security-beyond-password' => [
                'enabled' => true,
                'audience' => 'password-only',
                'title' => 'Sicherheit erhöhen',
            ],
        ]);

        $passwordChangeRequired = User::factory()->create(['must_change_password' => true]);
        $pendingTotp = User::factory()->create(['two_factor_secret' => 'fixture-secret']);

        $this->assertSame([], $this->service->forUser($passwordChangeRequired));
        $this->assertSame([], $this->service->forUser($pendingTotp));
    }

    /** A repeatable reminder returns when its configured snooze period expires. */
    public function test_repeatable_interstitial_is_snoozed_until_its_configured_expiry(): void
    {
        config()->set('interstitials.items', [
            'security-beyond-password' => [
                'enabled' => true,
                'audience' => 'all',
                'repeatable' => true,
                'dismissible' => false,
                'snooze_days' => 21,
                'title' => 'Sicherheit erhöhen',
            ],
        ]);
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create();

        $state = $this->service->remember($user, 'security-beyond-password', InterstitialService::ACTION_LATER);

        $this->assertSame(now()->addDays(21)->toIso8601String(), $state['snoozed_until']);
        $this->assertSame([], $this->service->forUser($user));

        $this->travel(21)->days();
        $this->assertCount(1, $this->service->forUser($user));
    }

    /** Repeatable security reminders cannot be permanently dismissed. */
    public function test_non_dismissible_interstitial_cannot_be_permanently_hidden(): void
    {
        config()->set('interstitials.items', [
            'security-beyond-password' => [
                'enabled' => true,
                'audience' => 'all',
                'repeatable' => true,
                'dismissible' => false,
                'title' => 'Sicherheit erhöhen',
            ],
        ]);

        $this->expectException(ValidationException::class);
        $this->service->remember(
            User::factory()->create(),
            'security-beyond-password',
            InterstitialService::ACTION_DISMISS,
        );
    }
}
