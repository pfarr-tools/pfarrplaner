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
 *
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

use App\Models\People\User;
use App\Services\PasswordService;
use App\Services\RoleConfigurationSynchronizer;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Creates the legacy initial administrator during installation.
 */
class InstallAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup super-admin role and user';

    /**
     * Execute the console command.
     */
    public function handle(RoleConfigurationSynchronizer $synchronizer): int
    {
        if (User::where('last_name', 'Admin')->whereNull('first_name')->count()) {
            $this->line('<error>ERROR</error> Admin user already exists.');

            return Command::FAILURE;
        }

        $synchronizer->synchronize();
        $role = Role::where('name', 'Super-Administrator:in')->firstOrFail();
        $domain = parse_url(url('/'), PHP_URL_HOST);

        $password = PasswordService::randomPassword();
        $user = User::create(['last_name' => 'Admin', 'email' => 'admin@'.$domain, 'password' => $password, 'must_change_password' => 1]);
        $user->assignRole($role);

        $this->line('<info>SUCCESS</info> Admin user admin@'.$domain.' with password"'.$password.'" created.');

        return Command::SUCCESS;
    }
}
