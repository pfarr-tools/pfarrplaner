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

namespace App\Console\Commands\Liturgy;

use App\Models\Service;
use App\StudyHelpers\AbstractStudyHelper;
use Illuminate\Console\Command;
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
            if (($file->getExtension() == 'php') && (! Str::contains($file->getPathname(), 'Abstract'))) {
                $className = substr('App\\StudyHelpers\\'.Str::replace('/', '\\', $file->getRelativePathname()), 0, -4);
                /** @var AbstractStudyHelper $studyHelperProvider */
                $studyHelperProvider = new $className($this);
                $studyHelperProviders[$studyHelperProvider->getKey()] = $studyHelperProvider;
            }
        }

        $only = $this->option('only') ? explode(',', $this->option('only')) : [];

        $this->getOutput()->section('Verzeichnisse lesen');

        $data = [];

        $latestService = Service::select('date')->distinct()->orderBy('date', 'desc')->first();
        $latestDate = $latestService?->date;
        if ($latestDate === null) {
            throw new \RuntimeException('Kein Gottesdienst mit einem Datum gefunden.');
        }
        $maxYear = (int) $latestDate->year;
        for ($year = 2018; $year <= $maxYear; $year++) {
            if (empty($only) || in_array((string) $year, $only, true)) {
                $this->processItem('Kalender für '.$year, function () use ($year, &$data) {
                    $contents = file_get_contents('https://kirchenjahr.pfarr.tools/api/jahr/'.$year);
                    if ($contents === false) {
                        throw new \RuntimeException('Kalender für '.$year.' konnte nicht geladen werden.');
                    }
                    $data[$year] = json_decode($contents, true);
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
            if (empty($only) || in_array((string) $key, $only, true)) {
                $this->processItem($studyHelperProvider->title, function () use ($studyHelperProvider, &$data) {
                    $data = $studyHelperProvider->assign($data);
                });
            }
        }

        foreach ($data as $year => $calendar) {
            $json = json_encode($calendar);
            if ($json === false) {
                throw new \RuntimeException('Kalender für '.$year.' konnte nicht serialisiert werden.');
            }
            Storage::put('liturgy/'.$year.'.json', $json);
        }

        $this->newLine(2);
        $this->line('Der liturgische Kalender wurde aktualisiert.');
    }

    /**
     * Führt einen Arbeitsschritt mit Statusausgabe aus.
     *
     * @param  string  $title  Angezeigter Name des Arbeitsschritts
     * @param  callable  $callback  Auszuführender Arbeitsschritt
     * @return mixed Ergebnis des Arbeitsschritts oder null bei Fehlern
     */
    protected function processItem(string $title, callable $callback): mixed
    {
        $this->getOutput()->write(Str::padRight($title, 75));
        $success = true;
        $result = null;
        try {
            $result = $callback();
        } catch (\Exception $e) {
            $success = false;
        }
        $this->getOutput()->writeln($success ? '[<info>OK</info>]' : '[<error>FAILED</error>]');

        return $result ?? null;
    }
}
