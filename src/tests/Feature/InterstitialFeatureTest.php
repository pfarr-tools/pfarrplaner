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

use App\Http\Middleware\ForceDomain;
use App\Models\People\User;
use App\Services\InterstitialService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InterstitialFeatureTest extends TestCase
{
    public function test_dismiss_stores_user_setting(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ForceDomain::class);

        config()->set('interstitials.items', [
            'breaking-change' => [
                'enabled' => true,
                'audience' => 'all',
                'title' => 'Wichtige Änderung',
            ],
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('interstitial.update', ['key' => 'breaking-change']), [
            'action' => InterstitialService::ACTION_DISMISS,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => InterstitialService::ACTION_DISMISS,
            ]);

        $this->assertSame(
            InterstitialService::ACTION_DISMISS,
            $user->fresh()->getSetting('interstitials')['breaking-change']['status']
        );
    }

    public function test_later_stores_user_setting_for_future_logins(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ForceDomain::class);

        config()->set('interstitials.items', [
            'breaking-change' => [
                'enabled' => true,
                'audience' => 'all',
                'title' => 'Wichtige Änderung',
            ],
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('interstitial.update', ['key' => 'breaking-change']), [
            'action' => InterstitialService::ACTION_LATER,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => InterstitialService::ACTION_LATER,
            ]);

        $this->assertSame(
            InterstitialService::ACTION_LATER,
            $user->fresh()->getSetting('interstitials')['breaking-change']['status']
        );
    }

    /** Permanent dismissal of the repeatable security reminder is rejected by the endpoint. */
    public function test_repeatable_interstitial_rejects_permanent_dismissal(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ForceDomain::class);
        config()->set('interstitials.items.security-beyond-password', [
            'enabled' => true,
            'audience' => 'password-only',
            'repeatable' => true,
            'dismissible' => false,
            'snooze_days' => 21,
            'title' => 'Sicherheit erhöhen',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('interstitial.update', ['key' => 'security-beyond-password']), [
                'action' => InterstitialService::ACTION_DISMISS,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');

        $this->assertSame([], $user->fresh()->getSetting('interstitials', []));
    }

    /** A later response exposes the stored expiry so the overlay can navigate safely. */
    public function test_repeatable_interstitial_later_response_includes_persistent_expiry(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ForceDomain::class);
        config()->set('interstitials.items.security-beyond-password', [
            'enabled' => true,
            'audience' => 'password-only',
            'repeatable' => true,
            'dismissible' => false,
            'snooze_days' => 21,
            'title' => 'Sicherheit erhöhen',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('interstitial.update', ['key' => 'security-beyond-password']), [
                'action' => InterstitialService::ACTION_LATER,
            ])
            ->assertOk()
            ->assertJsonPath('status', InterstitialService::ACTION_LATER)
            ->assertJsonPath('snoozed_until', now()->addDays(21)->toIso8601String());

        $this->assertSame(
            InterstitialService::ACTION_LATER,
            $user->fresh()->getSetting('interstitials')['security-beyond-password']['status'],
        );

        $this->actingAs($user)
            ->get('/profil?tab=security')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('interstitials', []));
    }
}
