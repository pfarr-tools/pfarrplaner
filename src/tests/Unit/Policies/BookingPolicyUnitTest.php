<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
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
        $this->policy = new BookingPolicy;
        Permission::findOrCreate('gd-bearbeiten', 'web');
    }

    public function test_regular_user_cannot_create_booking(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $this->assertFalse($this->policy->create($user, $service));
    }

    public function test_user_with_service_permission_can_create_booking(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $service = Service::factory()->create();
        $user->writableCities()->attach($service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->create($user, $service));
    }

    public function test_regular_user_cannot_update_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create();
        $this->assertFalse($this->policy->update($user, $booking));
    }

    public function test_user_with_service_permission_can_update_booking(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $booking = Booking::factory()->create();
        $user->writableCities()->attach($booking->service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->update($user, $booking));
    }

    public function test_user_with_service_permission_can_delete_booking(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $booking = Booking::factory()->create();
        $user->writableCities()->attach($booking->service->city_id, ['permission' => 'w']);
        $this->assertTrue($this->policy->delete($user, $booking));
    }
}
