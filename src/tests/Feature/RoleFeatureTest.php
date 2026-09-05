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

namespace Tests\Feature;

use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testIndexLoads(): void
    {
        $this->actingAs($this->user)
            ->get(route('roles.index'))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Role/Index'));
    }

    public function testEditorLoads(): void
    {
        $role = Role::create(['name' => 'Testrole', 'guard_name' => 'web']);
        $this->actingAs($this->user)
            ->get(route('role.edit', $role->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Role/RoleEditor'));
    }

    public function testCreateRole(): void
    {
        $this->actingAs($this->user)
            ->post(route('role.store'), ['name' => 'Neue Rolle', 'permissions' => []])
            ->assertStatus(302);
        $this->assertTrue(Role::where('name', 'Neue Rolle')->exists());
    }

    public function testUpdateRole(): void
    {
        $role = Role::create(['name' => 'AlteRolle', 'guard_name' => 'web']);
        $this->actingAs($this->user)
            ->patch(route('role.update', $role->id), ['name' => 'NeueRolle', 'permissions' => []])
            ->assertStatus(302);
        $this->assertTrue(Role::where('name', 'NeueRolle')->exists());
    }
}
