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

use App\Models\Parish;
use App\Models\People\User;
use App\Policies\ParishPolicy;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ParishPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private ParishPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ParishPolicy();
        Permission::findOrCreate('pfarramt-bearbeiten', 'web');
    }

    public function testRegularUserCannotIndex(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function testAdminCanIndex(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_ADMIN);
        $this->assertTrue($this->policy->index($user));
    }

    public function testUserWithPermissionCanIndex(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pfarramt-bearbeiten');
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
        $user->givePermissionTo('pfarramt-bearbeiten');
        $this->assertTrue($this->policy->create($user));
    }

    public function testRegularUserCannotUpdate(): void
    {
        $user = User::factory()->create();
        $parish = Parish::factory()->create();
        $this->assertFalse($this->policy->update($user, $parish));
    }

    public function testUserWithPermissionCanUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pfarramt-bearbeiten');
        $parish = Parish::factory()->create();
        $this->assertTrue($this->policy->update($user, $parish));
    }

    public function testRegularUserCannotDelete(): void
    {
        $user = User::factory()->create();
        $parish = Parish::factory()->create();
        $this->assertFalse($this->policy->delete($user, $parish));
    }

    public function testUserWithPermissionCanDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pfarramt-bearbeiten');
        $parish = Parish::factory()->create();
        $this->assertTrue($this->policy->delete($user, $parish));
    }
}
