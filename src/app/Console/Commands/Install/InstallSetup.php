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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands\Install;

use App\Actions\Setup\CreateSuperAdmin;
use App\Models\People\User;
use Illuminate\Console\Command;
use Illuminate\Console\Command\Install;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Output\BufferedOutput;

class InstallSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform initial setup';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info(config('app.name') . ' Setup');

        foreach (['roles', 'admin'] as $item) {
            $this->output->write(str_pad('Setting up initial ' . $item . '... ', 40));
            $method = Str::camel('setup_' . $item);
            if (method_exists($this, $method)) {
                $result = $this->$method();
            } else {
                $result = false;
            }
            $this->output->writeln(
                $result !== false ? ' <fg=green;options=bold>DONE</>' : ' <fg=red;options=bold>FAIL</>'
            );
        }
    }

    public function setupPermissions()
    {
        return false;
    }

    public function setupRoles()
    {
        $roleDefinitions = [
            'Administrator:in' => [
                'admin',
                'fremden-urlaub-bearbeiten',
                'gd-abendmahl-bearbeiten',
                'gd-allgemein-bearbeiten',
                'gd-anmerkungen-bearbeiten',
                'gd-bearbeiten',
                'gd-kasualien-bearbeiten',
                'gd-kinderkirche-bearbeiten',
                'gd-loeschen',
                'gd-mesner-bearbeiten',
                'gd-opfer-bearbeiten',
                'gd-organist-bearbeiten',
                'gd-pfarrer-bearbeiten',
                'gd-taufe-bearbeiten',
                'ort-bearbeiten',
                'benutzer-bearbeiten',
                'rollen-bearbeiten',
                'liederbuecher-bearbeiten',
                'lieder-bearbeiten',
            ],
            'Gemeindesekretär:in' => [
                'gd-abendmahl-bearbeiten',
                'gd-allgemein-bearbeiten',
                'gd-bearbeiten',
                'gd-kasualien-bearbeiten',
                'gd-kasualien-lesen',
                'gd-kinderkirche-bearbeiten',
                'gd-loeschen',
                'gd-mesner-bearbeiten',
                'gd-opfer-bearbeiten',
                'gd-organist-bearbeiten',
                'gd-pfarrer-bearbeiten',
                'gd-taufe-bearbeiten',
                'kirche-bearbeiten',
                'ort-bearbeiten',
                'pfarramt-bearbeiten',
            ],
            'Kirchenpflege' => [
                'fremden-urlaub-bearbeiten',
                'gd-allgemein-bearbeiten',
                'gd-bearbeiten',
                'gd-kasualien-bearbeiten',
                'gd-kasualien-lesen',
                'gd-kinderkirche-bearbeiten',
                'gd-mesner-bearbeiten',
                'gd-opfer-bearbeiten',
                'kirche-bearbeiten',
                'gd-organist-bearbeiten',
            ],
            'Gottesdienstbearbeiter:in' => ['gd-bearbeiten'],
            'Urlaubsbearbeiter:in' => ['fremden-urlaub-bearbeiten'],
            'KGR' => ['gd-kasualien-nur-statistik'],
            'Opferverwaltung' => ['gd-bearbeiten'],
            'Pfarrer:in' => [
                'benutzerliste-lokal-sehen',
                'gd-abendmahl-bearbeiten',
                'gd-allgemein-bearbeiten',
                'gd-bearbeiten',
                'gd-kasualien-bearbeiten',
                'gd-kasualien-lesen',
                'gd-kinderkirche-bearbeiten',
                'gd-loeschen',
                'gd-mesner-bearbeiten',
                'gd-opfer-bearbeiten',
                'gd-organist-bearbeiten',
                'gd-pfarrer-bearbeiten',
                'gd-taufe-bearbeiten',
                'kirche-bearbeiten',
                'urlaub-lesen',
                'ort-bearbeiten',
            ],
            'Streaming-Bearbeiter:in' => [
                'ort-bearbeiten',
                'gd-bearbeiten',
                'gd-allgemein-bearbeiten',
            ]
        ];


        foreach ($roleDefinitions as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            foreach ($rolePermissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $role->givePermissionTo($permissionName);
            }
        }

        return true;
    }

    public function setupAdmin()
    {
        $output = new BufferedOutput();
        return Artisan::call('install:admin', [], $this->getOutput());
    }
}
