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
use App\Models\Service;
use App\Policies\ServicePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServicePolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private ServicePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ServicePolicy;
        Permission::findOrCreate('gd-bearbeiten', 'web');
        Permission::findOrCreate('gd-allgemein-bearbeiten', 'web');
    }

    public function test_view_always_returns_true(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $this->assertTrue($this->policy->view($user, $service));
    }

    public function test_index_returns_false_without_permission(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function test_view_any_returns_false_without_permission(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_create_returns_false_without_permission(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_permission_can_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $this->assertTrue($this->policy->index($user));
    }

    public function test_user_with_permission_can_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_with_permission_can_view_any(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $this->assertTrue($this->policy->viewAny($user));
    }
}
