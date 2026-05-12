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
use App\Models\Seating\Booking;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testDestroyDeletesBooking()
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $booking = Booking::create([
            'service_id' => $service->id,
            'code' => 'TEST001',
            'name' => 'Mustermann',
            'first_name' => 'Max',
            'contact' => 'max@example.de',
            'number' => 2,
        ]);
        $id = $booking->id;

        $response = $this->actingAs($user)
            ->deleteJson(route('api.booking.destroy', $booking));

        $response->assertNoContent();
        $this->assertNull(Booking::find($id));
    }

    /**
     * @return void
     */
    public function testDestroyRequiresAuth()
    {
        $service = Service::factory()->create();
        $booking = Booking::create([
            'service_id' => $service->id,
            'code' => 'TEST002',
            'name' => 'Musterfrau',
            'first_name' => 'Anna',
            'contact' => 'anna@example.de',
            'number' => 1,
        ]);

        $response = $this->deleteJson(route('api.booking.destroy', $booking));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testDestroyReturns404ForMissingBooking()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson(route('api.booking.destroy', 999999));

        $response->assertNotFound();
    }
}
