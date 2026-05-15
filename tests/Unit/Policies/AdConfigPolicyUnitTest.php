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

namespace Tests\Unit\Policies;

use App\Models\Ads\AdConfig;
use App\Models\People\User;
use App\Models\Service;
use App\Policies\AdConfigPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdConfigPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private AdConfigPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AdConfigPolicy();
        Permission::findOrCreate('gd-bearbeiten', 'web');
    }

    public function testRegularUserCannotCreateAdConfig(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $this->assertFalse($this->policy->create($user, $service));
    }

    public function testUserWithServicePermissionCanCreateAdConfig(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $service = Service::factory()->create();
        $user->writableCities()->attach($service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->create($user, $service));
    }

    public function testRegularUserCannotUpdateAdConfig(): void
    {
        $user = User::factory()->create();
        $adConfig = AdConfig::factory()->create();
        $this->assertFalse($this->policy->update($user, $adConfig));
    }

    public function testUserWithServicePermissionCanUpdateAdConfig(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $adConfig = AdConfig::factory()->create();
        $user->writableCities()->attach($adConfig->service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->update($user, $adConfig));
    }

    public function testRegularUserCannotDeleteAdConfig(): void
    {
        $user = User::factory()->create();
        $adConfig = AdConfig::factory()->create();
        $this->assertFalse($this->policy->delete($user, $adConfig));
    }

    public function testUserWithServicePermissionCanDeleteAdConfig(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $adConfig = AdConfig::factory()->create();
        $user->writableCities()->attach($adConfig->service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->delete($user, $adConfig));
    }
}
