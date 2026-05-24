<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Feature;

use App\Models\People\User;
use App\Http\Middleware\ForceDomain;
use App\Services\InterstitialService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class InterstitialFeatureTest extends TestCase
{
    public function testDismissStoresUserSetting(): void
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

    public function testLaterStoresUserSettingForFutureLogins(): void
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
}
