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

use App\Services\RoleConfigurationSynchronizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Performs the initial application setup.
 */
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
        $this->components->info(config('app.name').' Setup');

        foreach (['roles', 'admin'] as $item) {
            $this->output->write(str_pad('Setting up initial '.$item.'... ', 40));
            $method = Str::camel('setup_'.$item);
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

    /**
     * Retains the historical permissions setup hook for compatibility.
     *
     * @return bool Always false because roles:sync owns the setup now.
     */
    public function setupPermissions(): bool
    {
        return false;
    }

    /**
     * Synchronizes the configured roles and permissions.
     *
     * @return bool Whether synchronization completed.
     */
    public function setupRoles(): bool
    {
        app(RoleConfigurationSynchronizer::class)->synchronize();

        return true;
    }

    /**
     * Creates the initial administrator through the dedicated command.
     *
     * @return int Command exit status.
     */
    public function setupAdmin(): int
    {
        return Artisan::call('install:admin', [], $this->getOutput());
    }
}
