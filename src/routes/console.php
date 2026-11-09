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

use App\Services\AttachmentUploadService;
use Illuminate\Support\Facades\Schedule;
use Laravel\Telescope\Telescope;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure-based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

if (app()->environment('local') && class_exists(Telescope::class)) {
    Schedule::command('telescope:prune --hours=48')->daily();
}
Schedule::command('youtube:update')
    ->everyTenMinutes()
    ->withoutOverlapping();
Schedule::command('youtube:unpublish')
    ->hourly()
    ->withoutOverlapping();
Schedule::command('query:clear')
    ->dailyAt('03:00')
    ->withoutOverlapping();
Schedule::command('liturgy:get')
    ->dailyAt('05:01')
    ->withoutOverlapping();
Schedule::command('registrations:expire')
    ->dailyAt('05:10')
    ->withoutOverlapping();
Schedule::command('trash:prune --days=30')->daily();
Schedule::command('cache:prune-stale-tags')->hourly();
Schedule::call(fn () => app(AttachmentUploadService::class)->cleanup())->hourly();

// Integration schedules
foreach (glob(app_path('Integrations/*')) as $folder) {
    if (is_dir($folder)) {
        $cls = 'App\\Integrations\\'.basename($folder).'\\'.basename($folder).'Integration';
        if (class_exists($cls) && method_exists($cls, 'schedule')) {
            $cls::schedule(app(Illuminate\Console\Scheduling\Schedule::class));
        }
    }
}
