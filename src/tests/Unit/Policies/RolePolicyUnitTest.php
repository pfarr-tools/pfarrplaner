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
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private RolePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RolePolicy;
        Permission::findOrCreate('rollen-bearbeiten', 'web');
    }

    public function test_regular_user_cannot_index(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function test_user_with_permission_can_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function test_regular_user_cannot_create(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_permission_can_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $this->assertTrue($this->policy->create($user));
    }

    public function test_regular_user_cannot_update(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Testrole', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->update($user, $role));
    }

    public function test_user_with_permission_can_update(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $role = Role::create(['name' => 'Testrole', 'guard_name' => 'web']);
        $this->assertTrue($this->policy->update($user, $role));
    }

    public function test_delete_always_returns_false(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $role = Role::create(['name' => 'Testrole2', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->delete($user, $role));
    }

    public function test_restore_always_returns_false(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Testrole3', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->restore($user, $role));
    }

    public function test_force_delete_always_returns_false(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Testrole4', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->forceDelete($user, $role));
    }
}
