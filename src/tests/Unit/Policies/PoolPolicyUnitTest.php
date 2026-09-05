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

use App\Models\Leave\Pool;
use App\Models\People\User;
use App\Policies\PoolPolicy;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private PoolPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PoolPolicy();
    }

    public function testRegularUserCannotViewAny(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->viewAny($user));
    }

    public function testPastorCanViewAny(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_PASTOR);
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function testAdminCanViewAny(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function testRegularUserCannotView(): void
    {
        $user = User::factory()->create();
        $pool = Pool::factory()->create();
        $this->assertFalse($this->policy->view($user, $pool));
    }

    public function testPastorCanView(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_PASTOR);
        $pool = Pool::factory()->create();
        $this->assertTrue($this->policy->view($user, $pool));
    }

    public function testRegularUserCannotCreate(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function testPastorCanCreate(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_PASTOR);
        $this->assertTrue($this->policy->create($user));
    }

    public function testRegularUserCannotDelete(): void
    {
        $user = User::factory()->create();
        $pool = Pool::factory()->create();
        $this->assertFalse($this->policy->delete($user, $pool));
    }

    public function testPastorCanDelete(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_PASTOR);
        $pool = Pool::factory()->create();
        $this->assertTrue($this->policy->delete($user, $pool));
    }
}
