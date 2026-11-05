<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Unit\Database;

use App\Services\RoleService;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederUnitTest extends TestCase
{
    public function test_seeder_creates_core_roles_when_another_role_already_exists(): void
    {
        Role::query()->delete();
        Role::create(['name' => RoleService::ROLE_PREDICANT]);

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => RoleService::ROLE_SUPER_ADMIN]);
        $this->assertDatabaseHas('roles', ['name' => RoleService::ROLE_ADMIN]);
        $this->assertDatabaseHas('roles', ['name' => RoleService::ROLE_PASTOR]);
        $this->assertDatabaseHas('roles', ['name' => RoleService::ROLE_PREDICANT]);
    }
}
