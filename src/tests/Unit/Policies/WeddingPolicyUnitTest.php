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

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Wedding;
use App\Models\Service;
use App\Policies\WeddingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WeddingPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private WeddingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new WeddingPolicy();
        Permission::findOrCreate('gd-bearbeiten', 'web');
    }

    public function testRegularUserCannotCreate(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function testIndexUsesCreatePermission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function testUserWithPermissionCanCreateForWritableService(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $this->assertTrue($this->policy->create($user, $service));
    }

    public function testRegularUserCannotUpdate(): void
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create();
        $this->assertFalse($this->policy->update($user, $wedding));
    }

    public function testUserWithPermissionAndServiceCityCanUpdate(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $wedding = Wedding::factory()->create(['service_id' => $service->id]);

        $this->assertTrue($this->policy->update($user, $wedding));
    }
}
