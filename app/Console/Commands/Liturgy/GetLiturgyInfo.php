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
    protected $signature = 'liturgy:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get liturgical calendar from kirchenjahr-evangelisch.de';

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
                $studyHelperProvider = new $className($this);
                $studyHelperProviders[$studyHelperProvider->title] = $studyHelperProvider;
            }
        };


        $this->getOutput()->section('Verzeichnisse lesen');
        $this->processItem('Liturgische Informationen (kirchenjahr-evangelisch.de)', function() {
            Storage::put(
                'liturgy.json',
                file_get_contents(
                    'https://www.kirchenjahr-evangelisch.de/service.php?o=lcf&f=gaa&r=json&dl=user'
                )
            );
        });
        foreach ($studyHelperProviders as $studyHelperProvider) {
            $this->processItem($studyHelperProvider->title, function () use ($studyHelperProvider) {
                $studyHelperProvider->read();
            });
        }


        $this->getOutput()->section('Liturgiedatenbank aktualisieren');
        $ctr = 0;
        $list = json_decode(Storage::get('liturgy.json'), true)['content']['days'];
        foreach ($list as $id => $data) {

            $formattedDate = $data['date'];

            $data['links'] = [];
            /** @var AbstractStudyHelper $studyHelperProvider */
            foreach ($studyHelperProviders as $studyHelperProvider) {
                $data = $studyHelperProvider->getLinks($data);
            }
            ksort($data['links']);

            $data['date'] = $data['dateSql'];
            unset($data['dateSql']);
            $data['id'] = $id;
            $data['currentPerikope'] = $data['litTextsPerikope' . $data['perikope']];
            $data['currentPerikopeLink'] = $data['litTextsPerikope' . $data['perikope'] . 'Link'];
            if (!LiturgyInfo::where('id', $id)->where('date', $data['date'])->count()) {
                $ctr++;
                $this->processItem('Liturgie anlegen für '.$formattedDate, function() use ($data) {
                    LiturgyInfo::create($data);
                });
            } else {
                $this->processItem('Liturgie aktualisieren für '.$formattedDate, function() use ($data, $id) {
                    $litInfo = LiturgyInfo::where('id', $id)->where('date', $data['date'])->first();
                    $data['links'] = array_merge($litInfo->links ?? [], $data['links']);
                    ksort($data['links']);
                    $litInfo->update($data);
                });
            }
        }

        $this->newLine(2);
        $this->line('Der liturgische Kalender wurde aktualisiert.');
        if ($ctr) {
            $this->line($ctr . ' neue Einträge wurden hinzugefügt.');
        }
    }


    protected function processItem($title, $callback) {
        $this->getOutput()->write(Str::padRight($title, 75));
        $result = $callback();
        $this->getOutput()->writeln('[<info>OK</info>]');
        return $result;
    }
}
