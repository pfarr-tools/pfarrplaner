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

namespace Tests\Unit;

use App\Events\Models\Booking\CreatedBooking;
use App\Events\Models\Booking\DeletedBooking;
use App\Events\Models\Booking\UpdatedBooking;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Seating\Booking;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookingUnitTest extends TestCase
{
    private User $user;

    public function testBookingCanBeCreatedViaFactory(): void
    {
        $booking = Booking::factory()->create();
        $this->assertCount(1, Booking::all());
    }

    public function testBookingExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(Booking::class, AbstractModel::class));
    }

    public function testBookingControllerClassesExist(): void
    {
        $this->assertTrue(is_subclass_of(Booking::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(Booking::apiControllerClass(), \App\Http\Controllers\Api\AbstractApiCRUDController::class));
    }

    public function testBookingContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\Booking\CreateBooking::class, app(Booking::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\Booking\UpdateBooking::class, app(Booking::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\Booking\DeleteBooking::class, app(Booking::getContractName('delete')));
    }

    public function testBookingCanBeCreatedViaAction(): void
    {
        Event::fake();

        $service = Service::factory()->create();
        $booking = app(Booking::getContractName('create'))->create($this->user, [
            'service_id' => $service->id,
            'name' => 'Mustermann',
            'first_name' => 'Max',
            'contact' => 'max@example.com',
            'number' => 1,
            'fixed_seat' => '',
            'override_seats' => '',
            'override_split' => '',
            'email' => 'max@example.com',
        ]);

        $this->assertSame($service->id, $booking->service_id);
        Event::assertDispatched(CreatedBooking::class);
    }

    public function testBookingCanBeUpdatedViaAction(): void
    {
        Event::fake();

        $booking = Booking::factory()->create();
        $updated = app(Booking::getContractName('update'))->update($this->user, $booking, [
            'name' => 'Musterfrau',
            'first_name' => 'Anna',
            'contact' => 'anna@example.com',
            'number' => 2,
            'fixed_seat' => '',
            'override_seats' => '',
            'override_split' => '',
            'email' => 'anna@example.com',
        ]);

        $this->assertSame('Musterfrau', $updated->name);
        Event::assertDispatched(UpdatedBooking::class);
    }

    public function testBookingCanBeDeletedViaAction(): void
    {
        Event::fake();

        $booking = Booking::factory()->create();
        $result = app(Booking::getContractName('delete'))->delete($this->user, $booking);

        $this->assertTrue($result);
        $this->assertCount(0, Booking::all());
        Event::assertDispatched(DeletedBooking::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }
}
