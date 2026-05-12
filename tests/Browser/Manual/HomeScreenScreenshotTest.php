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
 */

namespace Tests\Browser\Manual;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Laravel\Dusk\Browser;

class HomeScreenScreenshotTest extends ManualScreenshotTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $city = City::factory()->create(['name' => 'Mustergemeinde']);
        $this->superAdminUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->superAdminUser->homeCities()->attach($city->id);
        $this->superAdminUser->setSetting('homeScreen', 'homescreen:configurable');
        $this->superAdminUser->setSetting('homeScreenConfig', [
            'wizardButtons' => true,
            'showReplacements' => true,
        ]);
        $this->superAdminUser->setSetting('homeScreenTabsConfig', [
            'tabs' => [
                ['type' => 'nextServices', 'config' => ['mine' => 0]],
                ['type' => 'missingEntries', 'config' => [
                    'ministries' => [config('labels.pastor'), config('labels.organist'), config('labels.sacristan')],
                    'locations' => [],
                ]],
                ['type' => 'cases', 'config' => ['includeCities' => [$city->id], 'title' => 'Kasualien']],
                ['type' => 'baptisms', 'config' => [
                    'mine' => 0,
                    'showRequests' => 1,
                    'newestFirst' => 0,
                    'excludeProcessed' => 0,
                ]],
                ['type' => 'weddings', 'config' => [
                    'mine' => 0,
                    'newestFirst' => 0,
                    'excludeProcessed' => 0,
                ]],
                ['type' => 'funerals', 'config' => [
                    'mine' => 0,
                    'newestFirst' => 0,
                    'excludeProcessed' => 0,
                ]],
                ['type' => 'registrations', 'config' => []],
                ['type' => 'streaming', 'config' => ['locations' => []]],
                ['type' => 'absences', 'config' => []],
                ['type' => 'absenceRequests', 'config' => []],
                ['type' => 'admin', 'config' => []],
            ],
        ]);

        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => now()->addDays(7),
            'time' => '10:00:00',
            'description' => 'Muster-Gottesdienst für die Startseite',
            'cc' => 1,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $service->participants()->attach($this->superAdminUser->id, ['category' => 'P']);

        Baptism::create([
            'service_id' => $service->id,
            'city_id' => $city->id,
            'candidate_name' => 'Mika Mustermann',
            'candidate_address' => 'Musterweg 5',
            'candidate_zip' => '70173',
            'candidate_city' => 'Musterstadt',
            'candidate_email' => 'familie@example.test',
            'candidate_phone' => '0711 123456',
            'first_contact_with' => $this->superAdminUser->fullName(),
            'first_contact_on' => now()->format('Y-m-d'),
            'appointment' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'registered' => 0,
            'signed' => 0,
            'docs_ready' => 0,
            'docs_where' => '',
        ]);
        Wedding::factory()->create(['service_id' => $service->id]);
        Funeral::factory()->create(['service_id' => $service->id]);
    }

    public function testCaptureHomeScreenOverview(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot($browser, route('home'), 'startseite-uebersicht', 1200);
        });
    }

    public function testCaptureHomeScreenTabs(): void
    {
        $tabs = [
            'nextServices0' => 'startseite-reiter-naechste-gottesdienste',
            'missingEntries1' => 'startseite-reiter-fehlende-eintraege',
            'cases2' => 'startseite-reiter-kasualien',
            'baptisms3' => 'startseite-reiter-taufen',
            'weddings4' => 'startseite-reiter-trauungen',
            'funerals5' => 'startseite-reiter-beerdigungen',
            'registrations6' => 'startseite-reiter-anmeldungen',
            'streaming7' => 'startseite-reiter-streaming',
            'absences8' => 'startseite-reiter-mein-urlaub',
            'absenceRequests9' => 'startseite-reiter-urlaubsantraege',
            'admin10' => 'startseite-reiter-administration',
        ];

        $this->browse(function (Browser $browser) use ($tabs) {
            $this->captureManualScreenshot($browser, route('home'), 'startseite-uebersicht', 1200);

            foreach ($tabs as $tabKey => $filename) {
                $browser->script("document.querySelector('#{$tabKey}Tab a')?.click();");
                $browser->pause(500)
                    ->waitFor("#{$tabKey}.active", self::APP_RENDER_TIMEOUT_SECONDS);

                $this->captureCurrentManualElementScreenshot($browser, "#{$tabKey}", $filename, 300);
            }
        });
    }
}
