<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

use App\Services\PasswordService;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Console\Command\Install;
use Spatie\Permission\Models\Role;

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
     *
     * @return int
     */
    public function handle()
    {
        if (Role::query()->where('name', 'Super-Administrator*in')->count()) {
            $this->line('<error>ERROR</error> Super admin role already exists.');
            return Command::FAILURE;
        }

        if (User::where('name', 'Admin')->count()) {
            $this->line('<error>ERROR</error> Admin user already exists.');
            return Command::FAILURE;
        }

        $role = Role::create(['name' => 'Super-Administrator*in']);
        $domain = parse_url(url('/'), PHP_URL_HOST);

        $password = PasswordService::randomPassword();
        $user = User::create(['name' => 'Admin', 'email' => 'admin@'.$domain, 'password' => $password, 'must_change_password' => 1]);
        $user->assignRole($role);

        $this->line('<info>SUCCESS</info> Admin user admin@'.$domain.' with password"'.$password.'" created.');
        return Command::SUCCESS;
    }
}
