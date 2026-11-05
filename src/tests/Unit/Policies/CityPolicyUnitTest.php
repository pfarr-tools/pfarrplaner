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
        $this->policy = new CityPolicy;
        Permission::findOrCreate('ort-bearbeiten', 'web');
        Permission::findOrCreate('gd-opfer-bearbeiten', 'web');
    }

    public function test_view_always_returns_true(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertTrue($this->policy->view($user, $city));
    }

    public function test_view_any_always_returns_true(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_regular_user_cannot_create(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function test_admin_can_create(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertTrue($this->policy->create($user));
    }

    public function test_regular_user_cannot_delete(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertFalse($this->policy->delete($user, $city));
    }

    public function test_super_admin_can_delete(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $city = City::factory()->create();
        $this->assertTrue($this->policy->delete($user, $city));
    }

    public function test_regular_user_cannot_index(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function test_user_with_ort_bearbeiten_permission_can_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ort-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function test_restore_always_returns_false(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertFalse($this->policy->restore($user, $city));
    }

    public function test_force_delete_always_returns_false(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $this->assertFalse($this->policy->forceDelete($user, $city));
    }
}
