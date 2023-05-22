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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InstallUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:updates';

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
        passthru('git fetch');
        $log = shell_exec('git log --name-status origin/main');
        $currentCommit = shell_exec('git rev-parse HEAD');

        $log = explode("\n", substr($log, 0, strpos($log, $currentCommit)));
        $files = collect();

        foreach ($log as $line) {
            if (str_contains($line, "\t")) {
                $files->push(Str::afterLast($line, "\t"));
            }
        }

        if ($files->count() == 0) {
            $this->line('<info>INFO</info> Done installing updates.');
            return Command::SUCCESS;
        }

        // pull
        $this->getOutput()->section('Fetching updates to '.$files->count().' files');
        exec('git pull');

        // composer
        if ($this->hasFileChanges($files, 'composer.')) {
            $this->getOutput()->section('Composer');
            passthru('composer update');
            $this->line('');
        }

        // npm install
        if ($this->hasFileChanges($files, 'package.json')) {
            $this->getOutput()->section('NPM packages');
            passthru('npm install');
            $this->line('');
        }

        // npm run prod
        if ($this->hasFileChanges($files, 'resources/js/')) {
            $this->getOutput()->section('NPM build');
            passthru('npm run prod');
            $this->line('');
        }

        // art migrate
        if ($this->hasFileChanges($files, 'database/migrations/')) {
            $this->getOutput()->section('Database migrations');
            Artisan::call('migrate');
            $this->line('');
        }

        // art optimize
        $this->getOutput()->section('Optimizations');
        Artisan::call('optimize');
        $this->line('');

        $this->line('<info>INFO</info> Done installing updates.');
        return Command::SUCCESS;
    }

    protected function hasFileChanges(Collection $files, $tag) {
        return $files->filter(function ($item) use ($tag) {
            return Str::startsWith($item, $tag);
        })->count() > 0;
    }
}
