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
use App\Models\Rites\Baptism;
use App\Policies\BaptismPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BaptismPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private BaptismPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BaptismPolicy();
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

    public function testUserWithPermissionCanCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $this->assertTrue($this->policy->create($user));
    }

    public function testRegularUserCannotUpdate(): void
    {
        $user = User::factory()->create();
        $baptism = Baptism::factory()->create();
        $this->assertFalse($this->policy->update($user, $baptism));
    }

    public function testRegularUserCannotDelete(): void
    {
        $user = User::factory()->create();
        $baptism = Baptism::factory()->create();
        $this->assertFalse($this->policy->delete($user, $baptism));
    }

    public function testUserWithPermissionAndCityCanUpdate(): void
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $baptism = Baptism::factory()->create(['city_id' => $city->id]);
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $this->assertTrue($this->policy->update($user, $baptism));
    }
}
