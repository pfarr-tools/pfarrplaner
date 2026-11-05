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

namespace Tests\Unit\Policies;

use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Policies\LocationPolicy;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LocationPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private LocationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new LocationPolicy;
        Permission::findOrCreate('ort-bearbeiten', 'web');
        Permission::findOrCreate('gd-opfer-bearbeiten', 'web');
        Permission::findOrCreate('kirche-bearbeiten', 'web');
    }

    public function test_view_always_returns_true(): void
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();
        $this->assertTrue($this->policy->view($user, $location));
    }

    public function test_regular_user_cannot_create(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_write_access_can_create(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->create($user, $city));
    }

    public function test_regular_user_cannot_index(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function test_admin_can_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_ADMIN);
        $this->assertTrue($this->policy->index($user));
    }

    public function test_user_with_permission_can_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ort-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function test_regular_user_cannot_update(): void
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();
        $this->assertFalse($this->policy->update($user, $location));
    }

    public function test_regular_user_cannot_delete(): void
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();
        $this->assertFalse($this->policy->delete($user, $location));
    }
}
