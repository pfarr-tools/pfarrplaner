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
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookingWebFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        foreach (range((int)date('Y') - 2, (int)date('Y') + 2) as $year) {
            Storage::put("liturgy/{$year}.json", '{}');
        }
        Storage::put('liturgy/.json', '{}');
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testEditorLoads(): void
    {
        $booking = Booking::factory()->create();
        $this->actingAs($this->user)
            ->get(route('booking.edit', $booking->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Service/Registrations/BookingEditor'));
    }

    public function testStoreCreatesBooking(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->user)
            ->post(route('booking.store'), [
                'service_id' => $service->id,
                'name' => 'Mustermann',
                'first_name' => 'Max',
                'contact' => 'max@example.com',
                'number' => 1,
                'fixed_seat' => '',
                'override_seats' => '',
                'override_split' => '',
                'email' => 'max@example.com',
            ])
            ->assertRedirect(route('service.edit', ['service' => $service->slug, 'tab' => 'registrations']));

        $this->assertDatabaseCount('bookings', 1);
    }
}
