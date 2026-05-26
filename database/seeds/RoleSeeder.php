<?php

namespace Database\Seeders;

use App\Services\RoleService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->permissions() as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        if (0 == Role::count()) {
            Role::create(['name' => RoleService::ROLE_SUPER_ADMIN]);
            Role::create(['name' => RoleService::ROLE_ADMIN]);
            Role::create(['name' => RoleService::ROLE_PASTOR]);
        }
    }

    /**
     * @return string[]
     */
    protected function permissions(): array
    {
        return [
            'admin',
            'benutzer-bearbeiten',
            'benutzerliste-lokal-sehen',
            'fremden-urlaub-bearbeiten',
            'gd-abendmahl-bearbeiten',
            'gd-allgemein-bearbeiten',
            'gd-anmerkungen-bearbeiten',
            'gd-bearbeiten',
            'gd-kasualien-bearbeiten',
            'gd-kasualien-lesen',
            'gd-kasualien-nur-statistik',
            'gd-kinderkirche-bearbeiten',
            'gd-loeschen',
            'gd-mesner-bearbeiten',
            'gd-opfer-bearbeiten',
            'gd-organist-bearbeiten',
            'gd-pfarrer-bearbeiten',
            'gd-taufe-bearbeiten',
            'kirche-bearbeiten',
            'lieder-bearbeiten',
            'liederbuecher-bearbeiten',
            'ort-bearbeiten',
            'pfarramt-bearbeiten',
            'rollen-bearbeiten',
            'urlaub-lesen',
        ];
    }
}
