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
use App\Services\RoleService;
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
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $booking = Booking::factory()->create();
        $id = $booking->id;

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.booking.destroy', $booking));

        $response->assertNoContent();
        $this->assertNull(Booking::find($id));
    }

    /**
     * @return void
     */
    public function testDestroyRequiresAuth()
    {
        $booking = Booking::factory()->create();

        $response = $this->deleteJson(route('api.booking.destroy', $booking));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testDestroyReturns404ForMissingBooking()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.booking.destroy', 999999));

        $response->assertNotFound();
    }
}
