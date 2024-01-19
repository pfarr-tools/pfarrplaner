<?php

namespace Database\Seeders;

use App\Services\RoleService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (0 == Role::count()) {
            Role::create(['name' => RoleService::ROLE_SUPER_ADMIN]);
            Role::create(['name' => RoleService::ROLE_ADMIN]);
            Role::create(['name' => RoleService::ROLE_PASTOR]);
        }
    }
}
