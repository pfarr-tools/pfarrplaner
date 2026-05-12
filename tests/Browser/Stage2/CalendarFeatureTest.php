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

use App\Models\Places\City;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\CalendarPage;

class CalendarFeatureTest extends AbstractPageLoadTest
{
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->city = City::factory()->create();
        $this->superAdminUser->writableCities()->attach($this->city->id);
    }

    public function testCalendarRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new CalendarPage(2025, 1))
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testPrevMonthButtonNavigates(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new CalendarPage(2025, 6))
                    ->waitFor('.mdi-chevron-left', 10)
                    ->click('button .mdi-chevron-left')
                    ->pause(500)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testNextMonthButtonNavigates(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new CalendarPage(2025, 6))
                    ->waitFor('.mdi-chevron-right', 10)
                    ->click('button .mdi-chevron-right')
                    ->pause(500)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testMonthDropdownShowsMonthNames(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new CalendarPage(2025, 6))
                    ->waitFor('#app', 10)
                    ->assertSee('Januar');
        });
    }
}
