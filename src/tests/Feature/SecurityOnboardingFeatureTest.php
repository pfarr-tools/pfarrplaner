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
use App\Services\SecurityOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Verify the security onboarding reminder cadence and route gate. */
final class SecurityOnboardingFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Show the first four reminders, then wait fourteen days between later reminders.
     */
    public function test_unprotected_email_link_logins_use_the_four_then_fourteen_day_cadence(): void
    {
        $user = User::factory()->create(['is_official_user' => true]);
        $service = app(SecurityOnboardingService::class);

        for ($login = 1; $login <= 4; $login++) {
            $service->recordEmailLinkLogin($user->fresh());
            self::assertTrue($service->shouldShow($user->fresh()));
            $service->markShown($user->fresh());
        }

        $service->recordEmailLinkLogin($user->fresh());
        self::assertFalse($service->shouldShow($user->fresh()));

        $this->travel(14)->days();
        self::assertTrue($service->shouldShow($user->fresh()));
    }

    /**
     * Existing TOTP and passkeys exempt accounts from both the counter and gate.
     */
    public function test_existing_security_factors_skip_onboarding_and_counting(): void
    {
        $service = app(SecurityOnboardingService::class);
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

        foreach ([$totpUser, $passkeyUser] as $user) {
            $service->recordEmailLinkLogin($user);
            self::assertSame(0, $user->fresh()->security_onboarding_email_logins);
            self::assertFalse($service->shouldShow($user->fresh()));
        }
    }

    /**
     * An active gate protects page and XHR requests, while security setup remains reachable.
     */
    public function test_gate_blocks_app_and_xhr_requests_but_allows_factor_setup(): void
    {
        $user = User::factory()->create(['is_official_user' => true]);
        $onboarding = app(SecurityOnboardingService::class);
        $onboarding->recordEmailLinkLogin($user);
        self::assertTrue($onboarding->shouldShow($user->fresh()));

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('security.onboarding'));
        $this->get(route('security.onboarding'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/SecurityOnboarding')
                ->has('user.email')
                ->has('twoFactorEnabled')
                ->has('twoFactorPending')
                ->has('passkeys'));
        $this->getJson(route('home'))->assertRedirect(route('security.onboarding'));
        $this->get(route('user.passkeys.index'))->assertOk();
    }

    /** Protect authenticated JSON API routes from direct requests during onboarding. */
    public function test_authenticated_api_requests_cannot_bypass_the_gate(): void
    {
        $user = User::factory()->create(['is_official_user' => true]);
        app(SecurityOnboardingService::class)->recordEmailLinkLogin($user);

        $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.month', ['date' => '2026-11']))
            ->assertRedirect(route('security.onboarding'));
    }

    /** Record a reminder only when its page is rendered, and never slide its due date. */
    public function test_reminder_timestamp_changes_once_when_the_onboarding_page_is_rendered(): void
    {
        $lastShownAt = now()->subDays(14)->startOfSecond();
        $user = User::factory()->create([
            'is_official_user' => true,
            'security_onboarding_email_logins' => 5,
            'security_onboarding_last_shown_at' => $lastShownAt,
        ]);

        $this->from(route('home'))
            ->withHeader('Origin', config('app.url'))
            ->actingAs($user, 'api')
            ->getJson(route('api.calendar.month', ['date' => '2026-11']))
            ->assertRedirect(route('security.onboarding'));
        self::assertTrue($user->fresh()->security_onboarding_last_shown_at->equalTo($lastShownAt));

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('security.onboarding'));
        self::assertTrue($user->fresh()->security_onboarding_last_shown_at->equalTo($lastShownAt));

        $this->get(route('security.onboarding'))->assertOk();
        $shownAt = $user->fresh()->security_onboarding_last_shown_at;
        self::assertTrue($shownAt->greaterThan($lastShownAt));

        $this->travel(1)->day();
        $this->get(route('security.onboarding'))->assertOk();
        self::assertTrue($user->fresh()->security_onboarding_last_shown_at->equalTo($shownAt));
    }

    /** Keep onboarding active for pending TOTP and release it only after valid confirmation. */
    public function test_totp_setup_must_be_confirmed_before_the_gate_releases(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create(['is_official_user' => true]);
        app(SecurityOnboardingService::class)->recordEmailLinkLogin($user);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('security.onboarding'));
        $this->postJson(route('user.two-factor.start'), [])
            ->assertOk()
            ->assertJsonStructure(['provisioning_uri', 'recovery_codes']);
        $this->postJson(route('user.two-factor.confirm'), ['code' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
        $this->get(route('home'))->assertRedirect(route('security.onboarding'));

        $setupResponse = $this->get(route('security.onboarding'));
        $setupResponse->assertOk();
        $secret = $user->fresh()->twoFactorSecret();
        $code = app(TwoFactorService::class)->currentCode($secret);
        $this->postJson(route('user.two-factor.confirm'), ['code' => $code])->assertOk();
        $this->actingAs($user->fresh())->get(route('home'))->assertOk();
    }

    /** Protect the alternate Sanctum extranet middleware group as well as web and API. */
    public function test_extranet_requests_cannot_bypass_the_gate(): void
    {
        $user = User::factory()->create(['is_official_user' => true]);
        app(SecurityOnboardingService::class)->recordEmailLinkLogin($user);

        $this->actingAs($user)
            ->get('/extranet/user/services/next')
            ->assertRedirect(route('security.onboarding'));
    }

    /**
     * Choosing later frees only the current session from the onboarding gate.
     */
    public function test_later_action_releases_current_session_only(): void
    {
        $user = User::factory()->create(['is_official_user' => true]);
        app(SecurityOnboardingService::class)->recordEmailLinkLogin($user);
        $this->actingAs($user)->get(route('home'));

        $this->post(route('security.onboarding.later'))
            ->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk();
        $this->from(route('home'))
            ->withHeader('Origin', config('app.url'))
            ->actingAs($user, 'api')
            ->getJson(route('api.calendar.month', ['date' => '2026-11']))
            ->assertOk();

        $otherUser = User::factory()->create(['is_official_user' => true]);
        app(SecurityOnboardingService::class)->recordEmailLinkLogin($otherUser);
        $this->actingAs($otherUser)->get(route('home'))
            ->assertRedirect(route('security.onboarding'));

        Auth::shouldUse('web');
        $this->actingAs($user)->post(route('logout'));
        $this->actingAs($user)->get(route('home'))
            ->assertRedirect(route('security.onboarding'));
    }
}
