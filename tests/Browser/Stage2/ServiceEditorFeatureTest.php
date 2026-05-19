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

use App\Models\Location;
use App\Models\Places\City;
use App\Models\Service;
use App\Models\Sermon;
use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\CalendarPage;
use Tests\Browser\Pages\ServiceEditorPage;

class ServiceEditorFeatureTest extends AbstractPageLoadTest
{
    protected Service $service;
    protected Sermon $sermon;
    protected City $city;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->city = City::factory()->create(['name' => 'Musterstadt']);
        $this->location = Location::factory()->create([
            'city_id' => $this->city->id,
            'name' => 'Stadtkirche',
            'default_time' => '07:45:00',
        ]);
        $this->superAdminUser->cities()->attach($this->city->id, ['permission' => 'a']);
        $this->superAdminUser->homeCities()->attach($this->city->id);

        $this->service = Service::factory()->create([
            'city_id' => $this->city->id,
            'location_id' => $this->location->id,
            'date' => Carbon::create(2026, 7, 29, 5, 45, 0, 'UTC'),
            'time' => '07:45',
        ]);
        $this->sermon  = Sermon::create(['title' => 'Testpredigt']);
        $this->service->update(['sermon_id' => $this->sermon->id]);
    }

    public function testServiceEditorRendersForm(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->assertPresent('#formSermon');
        });
    }

    public function testDescriptionFieldCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->waitFor('[name="description"]', 10)
                    ->type('[name="description"]', 'Neue Testbeschreibung')
                    ->assertInputValue('[name="description"]', 'Neue Testbeschreibung');
        });
    }

    public function testSaveButtonIsPresent(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->assertPresent('button.btn-primary');
        });
    }

    public function testInternalRemarksCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->waitFor('[name="internal_remarks"]', 10)
                    ->type('[name="internal_remarks"]', 'Interne Anmerkung')
                    ->assertInputValue('[name="internal_remarks"]', 'Interne Anmerkung');
        });
    }

    public function testSaveServiceNavigatesOrShowsNoError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->waitFor('button.btn-primary', 10)
                    ->click('button.btn-primary')
                    ->pause(1000)
                    ->assertDontSee('Whoops')
                    ->assertDontSee('500');
        });
    }

    public function testSummerTimeChangeIsSavedInUtcAndShownCorrectlyInCalendar(): void
    {
        $this->browse(function (Browser $browser) {
            $page = new ServiceEditorPage($this->service->slug);

            $browser->loginAs($this->superAdminUser, 'web')
                ->visit($page)
                ->waitFor('@dateInput', 10)
                ->assertInputValue('@dateInput', '29.07.2026 07:45');

            $page->setDateTime($browser, '29.07.2026 08:00');

            $browser->pause(750)
                ->assertInputValue('@dateInput', '29.07.2026 08:00')
                ->click('@saveButton')
                ->pause(1500)
                ->assertDontSee('Whoops')
                ->assertDontSee('500');

            $this->service->refresh();
            $this->assertSame('2026-07-29 06:00:00', $this->service->date->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'));
            $this->assertSame('08:00:00', $this->service->time);

            $browser->visit(new CalendarPage(2026, 7))
                ->waitFor('.service-entry', 10)
                ->assertSee('8:00')
                ->assertDontSee('10:00');
        });
    }
}
