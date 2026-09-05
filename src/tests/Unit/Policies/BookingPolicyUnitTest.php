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

namespace Tests\Unit\Policies;

use App\Models\People\User;
use App\Models\Seating\Booking;
use App\Models\Service;
use App\Policies\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BookingPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private BookingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BookingPolicy();
        Permission::findOrCreate('gd-bearbeiten', 'web');
    }

    public function testRegularUserCannotCreateBooking(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $this->assertFalse($this->policy->create($user, $service));
    }

    public function testUserWithServicePermissionCanCreateBooking(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $service = Service::factory()->create();
        $user->writableCities()->attach($service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->create($user, $service));
    }

    public function testRegularUserCannotUpdateBooking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create();
        $this->assertFalse($this->policy->update($user, $booking));
    }

    public function testUserWithServicePermissionCanUpdateBooking(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $booking = Booking::factory()->create();
        $user->writableCities()->attach($booking->service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->update($user, $booking));
    }

    public function testUserWithServicePermissionCanDeleteBooking(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $booking = Booking::factory()->create();
        $user->writableCities()->attach($booking->service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->delete($user, $booking));
    }
}
