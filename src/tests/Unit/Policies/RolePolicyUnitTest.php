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
        $this->policy = new RolePolicy();
        Permission::findOrCreate('rollen-bearbeiten', 'web');
    }

    public function testRegularUserCannotIndex(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function testUserWithPermissionCanIndex(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function testRegularUserCannotCreate(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function testUserWithPermissionCanCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $this->assertTrue($this->policy->create($user));
    }

    public function testRegularUserCannotUpdate(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Testrole', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->update($user, $role));
    }

    public function testUserWithPermissionCanUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $role = Role::create(['name' => 'Testrole', 'guard_name' => 'web']);
        $this->assertTrue($this->policy->update($user, $role));
    }

    public function testDeleteAlwaysReturnsFalse(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('rollen-bearbeiten');
        $role = Role::create(['name' => 'Testrole2', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->delete($user, $role));
    }

    public function testRestoreAlwaysReturnsFalse(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Testrole3', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->restore($user, $role));
    }

    public function testForceDeleteAlwaysReturnsFalse(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Testrole4', 'guard_name' => 'web']);
        $this->assertFalse($this->policy->forceDelete($user, $role));
    }
}
