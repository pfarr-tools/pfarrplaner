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

namespace Tests\Browser\Stage2;

use App\Liturgy\LiturgySheets\LiturgySheets;
use App\Models\Liturgy\Block;
use App\Models\Location;
use App\Models\Places\City;
use App\Models\Sermon;
use App\Models\Service;
use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\CalendarPage;
use Tests\Browser\Pages\ServiceEditorPage;

class ServiceEditorFeatureTest extends AbstractPageLoadTest
{
    protected Service $service;

    protected Service $event;

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
        Block::create([
            'service_id' => $this->service->id,
            'title' => 'Eröffnung',
            'instructions' => '',
            'sortable' => 0,
        ]);
        $this->sermon = Sermon::create(['title' => 'Testpredigt']);
        $this->service->update(['sermon_id' => $this->sermon->id]);

        $this->event = Service::factory()->create([
            'city_id' => $this->city->id,
            'event_class' => 'event',
            'title' => 'Kirchenchorprobe',
        ]);
    }

    public function test_service_editor_renders_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new ServiceEditorPage($this->service->slug))
                ->assertPresent('#serviceEditorForm');
        });
    }

    public function test_advertising_link_is_available_for_other_events(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new ServiceEditorPage($this->event->slug))
                ->waitFor('a[title="Werbung bearbeiten"]', 10)
                ->assertPresent('a[title="Werbung bearbeiten"]');
        });
    }

    public function test_description_field_can_be_edited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new ServiceEditorPage($this->service->slug))
                ->waitFor('[name="description"]', 10)
                ->type('[name="description"]', 'Neue Testbeschreibung')
                ->assertInputValue('[name="description"]', 'Neue Testbeschreibung');
        });
    }

    public function test_save_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new ServiceEditorPage($this->service->slug))
                ->assertPresent('button.btn-primary');
        });
    }

    public function test_internal_remarks_can_be_edited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new ServiceEditorPage($this->service->slug))
                ->waitFor('[name="internal_remarks"]', 10)
                ->type('[name="internal_remarks"]', 'Interne Anmerkung')
                ->assertInputValue('[name="internal_remarks"]', 'Interne Anmerkung');
        });
    }

    public function test_save_service_navigates_or_shows_no_error(): void
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

    public function test_attachments_tab_shows_auto_attachments_for_services_with_liturgy_blocks(): void
    {
        $firstDownloadableSheet = collect(LiturgySheets::all())
            ->first(fn (array $sheet) => ! ($sheet['isNotAFile'] ?? false));

        $this->assertNotNull($firstDownloadableSheet);

        $this->browse(function (Browser $browser) use ($firstDownloadableSheet) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new ServiceEditorPage($this->service->slug))
                ->waitFor('#attachmentsTab a', 10)
                ->click('#attachmentsTab a')
                ->waitFor('#attachments', 10)
                ->assertSee($firstDownloadableSheet['title']);
        });
    }

    public function test_summer_time_change_is_saved_in_utc_and_shown_correctly_in_calendar(): void
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
            $this->assertSame('08:00', $this->service->time);

            $browser->visit(new CalendarPage(2026, 7))
                ->waitFor('.service-entry', 10)
                ->assertSee('8:00')
                ->assertDontSee('10:00');
        });
    }
}
