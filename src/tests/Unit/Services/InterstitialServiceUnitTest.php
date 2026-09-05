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

namespace Tests\Unit\Services;

use App\Models\People\User;
use App\Models\Places\City;
use App\Services\InterstitialService;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function testReturnsInterstitialForAllUsers(): void
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

    public function testAdminAudienceMatchesLocalAdmins(): void
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

    public function testWriterAudienceRequiresWritableCity(): void
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

    public function testDismissedInterstitialIsNotReturned(): void
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

    public function testSuperAdminMatchesAdminAudience(): void
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
}
