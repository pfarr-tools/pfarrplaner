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

namespace App\Integrations\CommuniApp\Commands;

use App\Integrations\CommuniApp\CommuniAppIntegration;
use App\Models\Calendar\Occurence;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PushToCommuniApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'communiapp:push {--dry-run : Only show which events would be pushed} {--force : Force push of all events, even if their publication date is already in the past }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push (all) events to CommuniApp ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach (City::all() as $city) {
            $pushCtr = 0;
            if (CommuniAppIntegration::isActive($city)) {

                $this->line('Pushing events to CommuniApp for "'.$city->name.'"');

                $events = Occurence::adRunningAt('communiapp', Carbon::now(), !$this->option('force'))
                    ->whereHas('service', function ($q) use ($city) {
                        $q->inCity($city)->notHidden()->displayable(Carbon::now());
                    })->get();
                if ($events->count()) {
                    $communiApp = CommuniAppIntegration::get($city);
                    foreach ($events as $event) {
                        $this->line('Pushing event #'.$event->id.' ('
                                    .$event->start->setTimeZone('Europe/Berlin')->format('d.m.Y, H:i')
                                        .', '.$event->service->locationTextWithCity
                                    .') '.$event->service->titleText(false, true));
                        if (!$this->option('dry-run')) $communiApp->publish($event);
                        $pushCtr++;
                    }
                }
                if ($pushCtr == 0) {
                    $this->line('No events to push');
                } else {
                    $this->line('Pushed '.$pushCtr.' events to CommuniApp');
                }
            }
        }
    }

    public function line($string, $style = null, $verbosity = null)
    {
        Log::debug ($string);
        parent::line($string, $style, $verbosity);
    }


}
