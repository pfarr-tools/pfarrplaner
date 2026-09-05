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
use App\Policies\UserPolicy;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
        Permission::findOrCreate('benutzer-bearbeiten', 'web');
        Permission::findOrCreate('benutzerliste-lokal-sehen', 'web');
        Permission::findOrCreate('fremden-urlaub-bearbeiten', 'web');
    }

    public function testSuperAdminCanIndex(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertTrue($this->policy->index($user));
    }

    public function testAdminCanIndex(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_ADMIN);
        $this->assertTrue($this->policy->index($user));
    }

    public function testRegularUserCannotIndex(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function testViewReturnsFalseWithoutPermission(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $this->assertFalse($this->policy->view($actor, $subject));
    }

    public function testSuperAdminCannotBeUpdatedByAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertFalse($this->policy->update($admin, $superAdmin));
    }

    public function testSuperAdminCannotBeDeletedByAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertFalse($this->policy->delete($admin, $superAdmin));
    }

    public function testFindDuplicatesRequiresSuperAdmin(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->findDuplicates($user));
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertTrue($this->policy->findDuplicates($user));
    }

    public function testFixDuplicatesRequiresSuperAdmin(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->fixDuplicates($user));
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertTrue($this->policy->fixDuplicates($user));
    }

    public function testDoJoinAlwaysReturnsTrue(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $this->assertTrue($this->policy->doJoin($actor, $subject));
    }

    public function testEditAbsencesAllowsSelfEdit(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->editAbsences($user, $user));
    }
}
