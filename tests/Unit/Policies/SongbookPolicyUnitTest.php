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

use App\Models\Liturgy\Songbook;
use App\Models\People\User;
use App\Policies\SongbookPolicy;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SongbookPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private SongbookPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SongbookPolicy();
        Permission::findOrCreate('liederbuecher-bearbeiten', 'web');
    }

    public function testRegularUserCannotViewAny(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->viewAny($user));
    }

    public function testIndexDelegatesToViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('liederbuecher-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function testAdminCanViewAny(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function testUserWithPermissionCanViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('liederbuecher-bearbeiten');
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function testRegularUserCannotCreate(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function testUserWithPermissionCanCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('liederbuecher-bearbeiten');
        $this->assertTrue($this->policy->create($user));
    }

    public function testRegularUserCannotUpdate(): void
    {
        $user = User::factory()->create();
        $songbook = Songbook::factory()->create();
        $this->assertFalse($this->policy->update($user, $songbook));
    }

    public function testUserWithPermissionCanUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('liederbuecher-bearbeiten');
        $songbook = Songbook::factory()->create();
        $this->assertTrue($this->policy->update($user, $songbook));
    }

    public function testRegularUserCannotDelete(): void
    {
        $user = User::factory()->create();
        $songbook = Songbook::factory()->create();
        $this->assertFalse($this->policy->delete($user, $songbook));
    }

    public function testUserWithPermissionCanDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('liederbuecher-bearbeiten');
        $songbook = Songbook::factory()->create();
        $this->assertTrue($this->policy->delete($user, $songbook));
    }
}
