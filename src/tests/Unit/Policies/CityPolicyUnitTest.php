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
use App\Policies\CityPolicy;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CityPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private CityPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CityPolicy();
        Permission::findOrCreate('ort-bearbeiten', 'web');
        Permission::findOrCreate('gd-opfer-bearbeiten', 'web');
    }

    public function testViewAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertTrue($this->policy->view($user, $city));
    }

    public function testViewAnyAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function testRegularUserCannotCreate(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function testAdminCanCreate(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertTrue($this->policy->create($user));
    }

    public function testRegularUserCannotDelete(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertFalse($this->policy->delete($user, $city));
    }

    public function testSuperAdminCanDelete(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $city = City::factory()->create();
        $this->assertTrue($this->policy->delete($user, $city));
    }

    public function testRegularUserCannotIndex(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function testUserWithOrtBearbeitenPermissionCanIndex(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ort-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function testRestoreAlwaysReturnsFalse(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertFalse($this->policy->restore($user, $city));
    }

    public function testForceDeleteAlwaysReturnsFalse(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertFalse($this->policy->forceDelete($user, $city));
    }
}
