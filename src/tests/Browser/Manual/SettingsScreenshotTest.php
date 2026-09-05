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

use App\Models\Calendar\External\CalendarConnection;
use App\Models\Places\City;
use Laravel\Dusk\Browser;

class SettingsScreenshotTest extends ManualScreenshotTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $cities = collect([
            City::factory()->create(['name' => 'Evangelische Kirchengemeinde Nordstadt']),
            City::factory()->create(['name' => 'Evangelische Kirchengemeinde Martinskirche']),
            City::factory()->create(['name' => 'Verbundkirchengemeinde am Fluss']),
        ]);

        foreach ($cities as $index => $city) {
            $this->superAdminUser->cities()->syncWithoutDetaching([$city->id => ['permission' => $index === 2 ? 'r' : 'w']]);
            $this->superAdminUser->setSubscription($city, [2, 1, 4][$index]);
        }

        $this->superAdminUser->setSetting('homeScreen', 'homescreen:configurable');
        $this->superAdminUser->setSetting('homeScreenConfig', [
            'wizardButtons' => true,
            'showReplacements' => true,
        ]);
        $this->superAdminUser->setSetting('homeScreenTabsConfig', [
            'tabs' => [
                ['type' => 'nextServices', 'config' => ['mine' => 1]],
                ['type' => 'missingEntries', 'config' => ['ministries' => [], 'locations' => []]],
                ['type' => 'cases', 'config' => ['cities' => $cities->pluck('id')->all()]],
            ],
        ]);

        $calendarConnection = CalendarConnection::create([
            'user_id' => $this->superAdminUser->id,
            'title' => 'Meine Pfarrplaner-Termine',
            'include_hidden' => 0,
            'include_alternate' => 1,
            'include_vacations' => 1,
            'include_rite_anniversaries' => 1,
        ]);
        foreach ($cities->take(2) as $city) {
            $calendarConnection->cities()->attach($city->id, ['connection_type' => CalendarConnection::CONNECTION_TYPE_ALL]);
        }
    }

    public function testCaptureProfilePage(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('user.profile'),
                'einstellungen-profil',
                800
            );
            foreach ([
                'security' => 'einstellungen-sicherheit',
                'subscriptions' => 'einstellungen-benachrichtigungen',
                'homeScreenConfiguration' => 'einstellungen-startseite',
                'calendars' => 'einstellungen-kalender',
                'externalContent' => 'einstellungen-externe-inhalte',
            ] as $tab => $filename) {
                $this->captureManualScreenshot(
                    $browser,
                    route('user.profile', ['tab' => $tab]),
                    $filename,
                    800
                );
            }
        });
    }
}
