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

use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\Location;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Seating\SeatingRow;
use App\Models\Seating\SeatingSection;
use App\Models\Service;
use App\Seating\RowBasedSeatingModel;
use Laravel\Dusk\Browser;

class AdminScreenshotTest extends ManualScreenshotTestCase
{
    protected Pool $pool;

    protected City $city;

    protected Location $location;

    protected SeatingSection $seatingSection;

    protected SeatingRow $seatingRow;

    protected function setUp(): void
    {
        parent::setUp();

        $cities = collect([
            City::factory()->create(['name' => 'Musterstadt Mitte']),
            City::factory()->create(['name' => 'Musterstadt West']),
            City::factory()->create(['name' => 'Friedenskirche Nord']),
            City::factory()->create(['name' => 'Johanneskirche Süd']),
            City::factory()->create(['name' => 'Christuskirche Berg']),
            City::factory()->create(['name' => 'Auferstehungsgemeinde']),
        ]);

        $this->superAdminUser->cities()->syncWithoutDetaching(
            $cities->mapWithKeys(fn (City $city) => [$city->id => ['permission' => 'a']])->all()
        );

        $this->city = $cities->first();
        $this->createManualPlaces($this->city);
        $this->createManualUsers($cities->all());

        $this->pool = Pool::factory()->create([
            'name' => 'Bestattungsvertretung',
            'contact' => 'Pfarramt Musterstadt Mitte',
            'office' => 'Musterweg 1',
            'phone' => '0711 123456',
            'email' => 'pool@example.test',
        ]);
        $this->pool->cities()->attach($cities->take(3)->pluck('id')->all());
        $this->pool->users()->attach($this->superAdminUser->id);

        $this->createManualTrashData();
    }

    protected function createManualTrashData(): void
    {
        $this->superAdminUser->update([
            'manage_absences' => 1,
        ]);

        $service = Service::factory()->create([
            'city_id' => $this->city->id,
            'location_id' => $this->location->id,
            'title' => 'Abendgottesdienst mit Taufe',
        ]);
        Baptism::factory()->create([
            'service_id' => $service->id,
            'city_id' => $this->city->id,
            'candidate_name' => 'Leonie Beispiel',
        ]);

        $absence = Absence::factory()->create([
            'user_id' => $this->superAdminUser->id,
            'reason' => 'Fortbildung',
            'from' => now()->addDays(5)->startOfDay(),
            'to' => now()->addDays(7)->endOfDay(),
        ]);

        $service->delete();
        $absence->delete();
    }

    protected function createManualPlaces(City $city): void
    {
        $city->update([
            'official_name' => 'Evangelische Kirchengemeinde Musterstadt Mitte',
            'homepage' => 'https://www.musterstadt-mitte.example',
            'default_offering_goal' => 'Eigene Gemeinde',
            'default_offering_description' => 'Für die laufende Arbeit der Kirchengemeinde',
            'default_funeral_offering_goal' => 'Hospizarbeit',
            'default_funeral_offering_description' => 'Für die Begleitung trauernder Menschen',
            'default_wedding_offering_goal' => 'Familienarbeit',
            'default_wedding_offering_description' => 'Für Angebote für Familien und Paare',
            'default_offering_url' => 'https://www.musterstadt-mitte.example/spenden',
            'iban' => 'DE02120300000000202051',
            'bic' => 'BYLADEM1001',
            'youtube_channel_url' => 'https://www.youtube.com/@musterstadtmitte',
            'youtube_auto_startstop' => 1,
            'youtube_self_declared_for_children' => 0,
            'youtube_cutoff_days' => 30,
            'konfiapp_apikey' => 'konfiapp-beispielschluessel',
            'communiapp_url' => 'https://musterstadt.communiapp.de',
            'communiapp_default_group_id' => 42,
        ]);

        $locations = [
            ['Stadtkirche', '10:30', 'in der Stadtkirche', 'Musterstadt Mitte'],
            ['Gemeindehaus', '19:30', 'im Gemeindehaus', 'Musterstadt Mitte'],
            ['Friedhofskapelle', '14:00', 'in der Friedhofskapelle', 'Musterstadt Mitte'],
        ];

        foreach ($locations as [$name, $time, $atText, $generalLocationName]) {
            Location::factory()->create([
                'city_id' => $city->id,
                'name' => $name,
                'default_time' => $time,
                'at_text' => $atText,
                'general_location_name' => $generalLocationName,
                'instructions' => 'Barrierefreier Zugang über den Seiteneingang.',
            ]);
        }

        $this->location = Location::where('city_id', $city->id)->where('name', 'Stadtkirche')->first();
        $this->location->update([
            'alternate_location_id' => Location::where('city_id', $city->id)->where('name', 'Gemeindehaus')->first()->id,
        ]);

        foreach ([['Pfarramt Mitte', 'PM'], ['Pfarramt Westbezirk', 'PW'], ['Pfarramt Klinikseelsorge', 'PK']] as [$name, $code]) {
            Parish::factory()->create([
                'city_id' => $city->id,
                'name' => $name,
                'code' => $code,
                'address' => 'Kirchplatz 1',
                'zip' => '70173',
                'city' => 'Musterstadt',
                'phone' => '0711 123456',
                'email' => strtolower($code) . '@musterstadt.example',
                'assistant' => 'Gemeindebüro',
                'opening_hours' => 'Mo-Fr 09:00-12:00 Uhr',
            ]);
        }

        $this->seatingSection = SeatingSection::create([
            'location_id' => $this->location->id,
            'title' => 'Mittelschiff',
            'seating_model' => RowBasedSeatingModel::class,
            'priority' => 1,
            'color' => '#8bc34a',
        ]);
        SeatingSection::create([
            'location_id' => $this->location->id,
            'title' => 'Empore',
            'seating_model' => RowBasedSeatingModel::class,
            'priority' => 2,
            'color' => '#ffc107',
        ]);

        $this->seatingRow = SeatingRow::create([
            'seating_section_id' => $this->seatingSection->id,
            'title' => '01',
            'seats' => 12,
            'split' => '6,6',
            'color' => '#8bc34a',
        ]);

        foreach ([['02', 12, '4,4,4'], ['03', 10, '5,5'], ['04', 10, '']] as [$title, $seats, $split]) {
            SeatingRow::create([
                'seating_section_id' => $this->seatingSection->id,
                'title' => $title,
                'seats' => $seats,
                'split' => $split,
                'color' => '#8bc34a',
            ]);
        }
    }

    /**
     * @param City[] $cities
     */
    protected function createManualUsers(array $cities): void
    {
        $users = [
            ['Anna', 'Berger', 'anna.berger@example.test', 0, 'a'],
            ['Martin', 'Schneider', 'martin.schneider@example.test', 0, 'w'],
            ['Elisabeth', 'Keller', 'elisabeth.keller@example.test', 1, 'w'],
            ['Johannes', 'Fischer', 'johannes.fischer@example.test', 1, 'r'],
            ['Sabine', 'Hoffmann', 'sabine.hoffmann@example.test', 2, 'w'],
            ['Thomas', 'Weber', 'thomas.weber@example.test', 2, 'r'],
            ['Clara', 'Neumann', 'clara.neumann@example.test', 3, 'w'],
            ['Michael', 'Braun', 'michael.braun@example.test', 3, 'r'],
            ['Katharina', 'Mayer', 'katharina.mayer@example.test', 4, 'w'],
            ['Peter', 'Wagner', 'peter.wagner@example.test', 5, 'r'],
        ];

        foreach ($users as [$firstName, $lastName, $email, $cityIndex, $permission]) {
            $user = User::factory()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
            ]);
            $city = $cities[$cityIndex];

            $user->homeCities()->syncWithoutDetaching([$city->id]);
            $user->cities()->syncWithoutDetaching([$city->id => ['permission' => $permission]]);
        }
    }

    public function testCaptureAdminIndex(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('admin.index'),
                'admin-uebersicht',
                800
            );
        });
    }

    public function testCaptureUserList(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('users.index'),
                'admin-benutzerliste',
                800
            );
        });
    }

    public function testCaptureRoleList(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('roles.index'),
                'admin-rollen',
                800
            );
        });
    }

    public function testCaptureUserEditorTabs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('user.edit', $this->superAdminUser->id),
                'admin-benutzer-person',
                800
            );
            $this->captureTab($browser, 'account', 'admin-benutzer-konto');
            $this->captureTab($browser, 'permissions', 'admin-benutzer-berechtigungen');
            $this->captureTab($browser, 'menu', 'admin-benutzer-menue');
            $this->captureTab($browser, 'homescreen', 'admin-benutzer-startseite');
            $this->captureTab($browser, 'absences', 'admin-benutzer-urlaub');
        });
    }

    public function testCapturePoolAdministration(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot($browser, route('admin.pools.index'), 'admin-pools-uebersicht', 800);
            $this->captureManualScreenshot(
                $browser,
                route('admin.pool.edit', ['modelId' => $this->pool->id]),
                'admin-pool-editor',
                800
            );
        });
    }

    public function testCaptureTrashAdministration(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('admin.trash.index'),
                'admin-papierkorb',
                800
            );
        });
    }

    public function testCaptureCityAdministration(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('admin.city.edit', ['modelId' => $this->city->id]),
                'admin-gemeinde-allgemein',
                1200
            );
            $this->captureTab($browser, 'offerings', 'admin-gemeinde-opfer');
            $this->captureTab($browser, 'parishes', 'admin-gemeinde-pfarramt');
            $this->captureTab($browser, 'streaming', 'admin-gemeinde-streaming');
            $this->captureTab($browser, 'locations', 'admin-gemeinde-orte');
            $this->captureTab($browser, 'integrations', 'admin-gemeinde-integrationen');
        });
    }

    public function testCaptureLocationAdministration(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('admin.location.edit', ['modelId' => $this->location->id]),
                'admin-standort-allgemein',
                800
            );
            $this->captureTab($browser, 'seating', 'admin-standort-sitzplaetze', 800);
            $this->captureManualScreenshot(
                $browser,
                route('seatingSection.edit', $this->seatingSection->id),
                'admin-sitzplan-bereich',
                800
            );
            $this->captureManualScreenshot(
                $browser,
                route('seatingRow.edit', $this->seatingRow->id),
                'admin-sitzplan-reihe',
                800
            );
        });
    }
}
