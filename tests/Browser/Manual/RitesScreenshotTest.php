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

use App\Models\Places\City;
use App\Models\Location;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Laravel\Dusk\Browser;

class RitesScreenshotTest extends ManualScreenshotTestCase
{
    protected Baptism $baptism;
    protected Funeral $funeral;
    protected Wedding $wedding;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::factory()->create(['name' => 'Mustergemeinde']);
        $this->superAdminUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->superAdminUser->homeCities()->attach($city->id);

        $location = Location::factory()->create([
            'city_id' => $city->id,
            'name' => 'Musterkirche',
        ]);

        $this->service = Service::factory()->create([
            'city_id' => $city->id,
            'location_id' => $location->id,
            'date' => now()->addDays(10)->setTime(8, 30),
            'time' => '10:30',
            'description' => 'Gottesdienst mit Kasualien',
        ]);

        $this->baptism = Baptism::create([
            'candidate_name' => 'Mika Mustermann',
            'candidate_address' => 'Musterweg 5',
            'candidate_zip' => '70173',
            'candidate_city' => 'Musterstadt',
            'candidate_email' => 'familie@example.test',
            'candidate_phone' => '0711 123456',
            'first_contact_with' => $this->superAdminUser->fullName(),
            'first_contact_on' => now()->format('Y-m-d'),
            'appointment' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'service_id' => $this->service->id,
            'city_id' => $city->id,
            'text' => 'Taufspruch der Musterfamilie',
            'needs_dimissorial' => 1,
            'registered' => 0,
            'signed' => 0,
            'docs_ready' => 0,
            'docs_where' => '',
        ]);

        $this->wedding = Wedding::factory()->create([
            'service_id' => $this->service->id,
            'spouse1_needs_dimissorial' => 1,
        ]);

        $this->funeral = Funeral::factory()->create([
            'service_id' => $this->service->id,
            'notes' => 'Notizen aus dem Trauergespräch',
            'faith' => 'Wichtige Gedanken zum Glauben',
        ]);
    }

    public function testCaptureRitesIndex(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('rites.index'),
                'kasualien-uebersicht',
                800
            );
            $browser->type('input[placeholder="Name"]', 'Muster')
                ->press('Suchen')
                ->waitForText('Suchergebnisse', self::APP_RENDER_TIMEOUT_SECONDS);
            $this->captureCurrentManualScreenshot($browser, 'kasualien-uebersicht', 800);
        });
    }

    public function testCaptureFuneralEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('funerals.edit', $this->funeral->id),
                'beerdigung-editor-allgemeines',
                800
            );
            $this->captureTab($browser, 'funeral', 'beerdigung-editor-bestattung');
            $this->captureTab($browser, 'family', 'beerdigung-editor-angehoerige');
            $this->captureTab($browser, 'interview', 'beerdigung-editor-trauergespraech');
            $this->captureTab($browser, 'attachments', 'beerdigung-editor-dateien');
        });
    }

    public function testCaptureBaptismEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('baptisms.edit', $this->baptism->id),
                'taufe-editor-allgemeines',
                800
            );
            $this->captureTab($browser, 'prep', 'taufe-editor-vorbereitung');
            $this->captureTab($browser, 'attachments', 'taufe-editor-dateien');
        });
    }

    public function testCaptureWeddingEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('weddings.edit', $this->wedding->id),
                'trauung-editor-brautpaar',
                800
            );
            $this->captureTab($browser, 'prep', 'trauung-editor-vorbereitung');
            $this->captureTab($browser, 'attachments', 'trauung-editor-dateien');
        });
    }

    public function testCaptureRiteWizards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot($browser, route('baptism.add', $this->service->id), 'taufe-anlegen', 800);
            $this->captureManualScreenshot($browser, route('funerals.wizard'), 'beerdigung-assistent', 800);
            $this->captureManualScreenshot($browser, route('weddings.wizard'), 'trauung-assistent', 800);
        });
    }
}
