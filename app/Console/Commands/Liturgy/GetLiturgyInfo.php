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

namespace App\Console\Commands\Liturgy;

use App\Models\LiturgyInfo;
use App\Models\Service;
use App\StudyHelpers\AbstractStudyHelper;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Storage;

class GetLiturgyInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'liturgy:get {--only=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get liturgical calendar from kirchenjahr.pfarr.tools';

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
        // get StudyHelper providers
        $studyHelperProviders = [];
        foreach (\File::allFiles(app_path('StudyHelpers')) as $file) {
            if (($file->getExtension() == 'php') && (!Str::contains($file->getPathname(), 'Abstract'))) {
                $className = substr('App\\StudyHelpers\\' . Str::replace('/', '\\', $file->getRelativePathname()), 0, -4);
                /** @var AbstractStudyHelper $studyHelperProvider */
                $studyHelperProvider = new $className($this);
                $studyHelperProviders[$studyHelperProvider->getKey()] = $studyHelperProvider;
            }
        };

        $only = $this->option('only') ? explode(',', $this->option('only')) : [];

        $this->getOutput()->section('Verzeichnisse lesen');

        $data = [];

        $maxYear = Service::select('date')->distinct()->orderBy('date', 'desc')->first()->date->year;
        for ($year = 2018; $year <= $maxYear; $year++) {
            if (empty($only) || in_array($year, $only)) {
                $this->processItem('Kalender für '.$year, function () use ($year, &$data) {
                    $data[$year] = json_decode(file_get_contents('https://kirchenjahr.pfarr.tools/api/jahr/'.$year), true);
                    /**
                    Storage::put(
                        'liturgy/'.$year.'.json',
                        file_get_contents('https://kirchenjahr.pfarr.tools/api/jahr/'.$year)
                    );
                     */
                });
            }
        }

        foreach ($studyHelperProviders as $key => $studyHelperProvider) {
            if (empty($only) || in_array($key, $only)) {
                $this->processItem($studyHelperProvider->title, function () use ($studyHelperProvider, &$data) {
                   $data = $studyHelperProvider->assign($data);
                });
            }
        }

        foreach ($data ?? [] as $year => $calendar) {
            Storage::put('liturgy/'.$year.'.json', json_encode($calendar));
        }

        $this->newLine(2);
        $this->line('Der liturgische Kalender wurde aktualisiert.');
    }


    protected function processItem($title, $callback) {
        $this->getOutput()->write(Str::padRight($title, 75));
        $success = true;
        try {
            $result = $callback();
        } catch (\Exception $e) {
            $success = false;
        }
        $this->getOutput()->writeln($success ? '[<info>OK</info>]' : '[<error>FAILED</error>]');
        return $result ?? null;
    }
}
