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

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicDimissorialFeatureTest extends TestCase
{
    protected City $city;
    protected Service $service;
    protected User $pastor;

    /**
     * @return void
     */
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
            'description' => 'Familiengottesdienst',
        ]);
        $this->service->participants()->attach($this->pastor->id, ['category' => 'P']);
    }

    /**
     * @return void
     */
    public function testPublicBaptismDimissorialCanBeShownAndGranted(): void
    {
        $baptism = Baptism::factory()->create([
            'service_id' => $this->service->id,
            'city_id' => $this->city->id,
            'candidate_name' => 'Mika Mustermann',
            'candidate_address' => 'Musterweg 5',
            'candidate_zip' => '70173',
            'candidate_city' => 'Musterstadt',
            'candidate_email' => 'familie@example.test',
            'candidate_phone' => '',
            'first_contact_with' => $this->pastor->fullName(),
            'needs_dimissorial' => 1,
        ]);

        $signedUrl = URL::signedRoute('dimissorial.show', ['type' => 'taufe', 'id' => $baptism->id]);

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee('Mika Mustermann')
            ->assertSee('Dimissoriale erteilen');

        $this->post(URL::signedRoute('dimissorial.grant', ['type' => 'taufe', 'id' => $baptism->id]))
            ->assertOk()
            ->assertSee('Herzlichen Dank!');

        $this->assertNotNull($baptism->fresh()->dimissorial_received);
    }

    /**
     * @return void
     */
    public function testPublicWeddingDimissorialShowsAlreadyGrantedStateForSignedSpouse(): void
    {
        $wedding = Wedding::factory()->create([
            'service_id' => $this->service->id,
            'spouse1_name' => 'Alex Beispiel',
            'spouse2_name' => 'Robin Beispiel',
            'spouse1_needs_dimissorial' => 1,
            'spouse1_dimissorial_received' => now(),
            'processed' => 0,
        ]);

        $response = $this->get(URL::signedRoute('dimissorial.show', [
            'type' => 'trauung',
            'id' => $wedding->id,
            'spouse' => 1,
        ]));

        $response->assertOk()
            ->assertSee('Sie haben bereits ein Dimissoriale')
            ->assertDontSee('Wenn Sie das Dimissoriale hiermit erteilen wollen');
    }
}
