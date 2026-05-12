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

namespace Tests\Browser\Stage1;

use App\Models\Seating\Booking;
use App\Models\Service;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class BookingsPageLoadTest extends AbstractPageLoadTest
{
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $service = Service::factory()->create();
        $this->booking = Booking::create([
            'service_id' => $service->id,
            'code' => 'TEST001',
            'name' => 'Mustermann',
            'first_name' => 'Max',
            'contact' => '0700 123456',
            'number' => 2,
        ]);
    }

    public function testBookingEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('booking.edit', $this->booking->id));
        });
    }
}
