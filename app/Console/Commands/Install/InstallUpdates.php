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

use App\Services\InstanceRegistryService;
use App\Services\UpdateService;
use Illuminate\Console\Command;
use Illuminate\Console\Command\Install;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class InstallUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:updates {--dry-run : Only show which actions would be performed by an update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install updates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $updateService = new UpdateService();

        $files = $updateService->getUpdateableFiles();
        $this->line('');

        if ($files->count() == 0) {
            $this->line('<info>INFO</info> There are no new updates to be installed..');
            return Command::SUCCESS;
        }

        $actions = $updateService->getUpdateActions($files);

        if (true === $this->options('dry-run')) {
            $this->line('The following '.$files->count().' files will be affected by the next update: ');
            foreach ($files as $file) {
                $this->line(' - '.$file);
            }
            $this->line('');
            $this->line('The following actions will be required by the next update:');
            foreach ($actions as $action => $description) {
                $this->line(' - '.$description);
            }
            return Command::SUCCESS;
        }

        // pull
        $this->getOutput()->section('Fetching updates to '.$files->count().' files');
        if (file_exists(base_path('package-lock.json'))) unlink(base_path('package-lock.json'));
        exec('git pull');


        // composer
        if (isset($actions['composer'])) {
            $this->getOutput()->section('Composer');
            passthru('composer update');
            $this->line('');
        }

        // npm install
        if (isset($actions['npm'])) {
            $this->getOutput()->section('NPM packages');
            passthru('npm install');
            $this->line('');
        }

        // npx browserslist@latest --update-db
        if (isset($actions['browserslist'])) {
            $this->getOutput()->section('Update browser list');
            passthru('npx --yes update-browserslist-db@latest');
            $this->line('');
        }

        // npm run prod
        if (isset($actions['webpack'])) {
            $this->getOutput()->section('NPM build');
            passthru('npm run prod');
            $this->line('');
        }

        // art migrate
        if (isset($actions['migrations'])) {
            $this->getOutput()->section('Database migrations');
            Artisan::call('migrate', [], $this->getOutput());
            $this->line('');
        }

        // view-cache
        if (isset($actions['view-cache'])) {
            $this->getOutput()->section('View cache');
            Artisan::call('view:clear', [], $this->getOutput());
            Artisan::call('view:cache', [], $this->getOutput());
            $this->line('');
        }

        // art optimize
        if (isset($actions['optimizations'])) {
            $this->getOutput()->section('Optimizations');
            Artisan::call('optimize', [], $this->getOutput());
            $this->line('');
        }

        // art optimize
        if (isset($actions['queue'])) {
            $this->getOutput()->section('Queue workers');
            Artisan::call('queue:restart', [], $this->getOutput());
            $this->line('');
        }

        // ping instance registry with updated info
        InstanceRegistryService::ping();

        $this->line('<info>INFO</info> Done installing updates.');
        return Command::SUCCESS;
    }

    protected function hasFileChanges(Collection $files, $tag) {
        return $files->filter(function ($item) use ($tag) {
            return Str::startsWith($item, $tag);
        })->count() > 0;
    }
}
