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

use App\Models\Places\City;
use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class CalendarPageLoadTest extends AbstractPageLoadTest
{
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->city = City::factory()->create();
    }

    public function testCalendarLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('calendar', [
                'date' => Carbon::now()->year,
                'month' => Carbon::now()->month,
            ]));
        });
    }

    public function testSingleDayLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('calendar.day', [
                'day' => Carbon::now()->format('Y-m-d'),
                'city' => $this->city->id,
            ]));
        });
    }
}
