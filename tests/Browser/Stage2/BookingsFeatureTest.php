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

namespace Tests\Browser\Stage2;

use App\Models\Seating\Booking;
use App\Models\Service;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class BookingsFeatureTest extends AbstractPageLoadTest
{
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $service       = Service::factory()->create();
        $this->booking = Booking::create([
            'service_id' => $service->id,
            'code'       => 'TEST001',
            'name'       => 'Mustermann',
            'first_name' => 'Max',
            'contact'    => '0700 123456',
            'number'     => 2,
        ]);
    }

    public function testBookingEditorRendersNameField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('booking.edit', $this->booking->id))
                    ->waitFor('[name="name"]', 10)
                    ->assertInputValue('[name="name"]', 'Mustermann');
        });
    }

    public function testBookingEditorFirstNameCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('booking.edit', $this->booking->id))
                    ->waitFor('[name="first_name"]', 10)
                    ->clear('[name="first_name"]')
                    ->type('[name="first_name"]', 'Maria')
                    ->assertInputValue('[name="first_name"]', 'Maria');
        });
    }

    public function testBookingEditorNumberFieldPresent(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('booking.edit', $this->booking->id))
                    ->waitFor('[name="number"]', 10)
                    ->assertPresent('[name="number"]');
        });
    }

    public function testBookingEditorHasSaveButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('booking.edit', $this->booking->id))
                    ->waitFor('#app', 10)
                    ->assertPresent('.save-button, button.btn-primary');
        });
    }
}
