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
use App\Models\Service;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\Browser;

class PublicScreenshotTest extends ManualScreenshotTestCase
{
    protected City $city;
    protected Service $service;
    protected Baptism $baptism;
    protected User $pastor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::factory()->create(['name' => 'Mustergemeinde']);
        $this->pastor = User::factory()->create([
            'first_name' => 'Martina',
            'last_name' => 'Muster',
            'email' => 'martina.muster@example.test',
        ]);
        $this->pastor->cities()->attach($this->city->id, ['permission' => 'w']);
        $this->pastor->homeCities()->attach($this->city->id);

        $this->service = Service::factory()->create([
            'city_id' => $this->city->id,
            'date' => now()->addDays(14),
            'time' => '10:00:00',
            'description' => 'Familiengottesdienst mit Kinderkirche',
            'cc' => 1,
            'cc_lesson' => 'Der barmherzige Samariter',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $this->service->participants()->attach($this->pastor->id, ['category' => 'P']);

        $this->baptism = Baptism::create([
            'service_id' => $this->service->id,
            'city_id' => $this->city->id,
            'candidate_name' => 'Mika Mustermann',
            'candidate_address' => 'Musterweg 5',
            'candidate_zip' => '70173',
            'candidate_city' => 'Musterstadt',
            'candidate_email' => 'familie@example.test',
            'candidate_phone' => '0711 123456',
            'first_contact_with' => $this->pastor->fullName(),
            'first_contact_on' => now()->format('Y-m-d'),
            'appointment' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'needs_dimissorial' => 1,
            'registered' => 0,
            'signed' => 0,
            'docs_ready' => 0,
            'docs_where' => '',
        ]);
    }

    public function testCapturePublicChildrensChurch(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('cc-public', $this->city->name))
                ->pause(800);
            $this->captureCurrentManualScreenshot($browser, 'oeffentlich-kinderkirche');
        });
    }

    public function testCapturePublicDimissorial(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(URL::signedRoute('dimissorial.show', ['type' => 'taufe', 'id' => $this->baptism->id]))
                ->pause(800);
            $this->captureCurrentManualScreenshot($browser, 'oeffentlich-dimissoriale');
        });
    }

    public function testCapturePublicMinistryRequest(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(URL::signedRoute('ministry.request', [
                'ministry' => 'P',
                'user' => $this->pastor->id,
                'services' => (string)$this->service->id,
                'sender' => $this->superAdminUser->id,
            ]))
                ->pause(800);
            $this->captureCurrentManualScreenshot($browser, 'oeffentlich-dienstanfrage');
        });
    }

    public function testCapturePublicMinistryPlan(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('ministry.plan', ['cityName' => $this->city->name, 'ministry' => 'P']))
                ->pause(800);
            $this->captureCurrentManualScreenshot($browser, 'oeffentlich-dienstplan');
        });
    }

    public function testCapturePublicInfoPage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('what.is'))
                ->pause(800);
            $this->captureCurrentManualScreenshot($browser, 'oeffentlich-infoseite');
        });
    }
}
