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

namespace Tests\Browser\Manual;

use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Laravel\Dusk\Browser;

class CalendarScreenshotTest extends ManualScreenshotTestCase
{
    protected Service $service;
    protected Service $event;

    protected function setUp(): void
    {
        parent::setUp();
        $city = City::factory()->create(['name' => 'Mustergemeinde']);
        $this->superAdminUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->superAdminUser->setSetting('calendar_select', 'city:'.$city->id);
        $this->superAdminUser->setSetting('calendar_mode', 'services');
        $this->superAdminUser->setSetting('show_cc_details', 1);

        $this->service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => $this->berlinDateTime(7, '10:30'),
            'time' => '10:30',
            'description' => 'Familiengottesdienst mit Tauferinnerung',
            'cc' => 1,
            'cc_lesson' => 'Gott behütet uns',
            'cc_location' => 'Gemeindehaus',
            'cc_staff' => 'Team Kinderkirche',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $this->service->participants()->attach($this->superAdminUser->id, ['category' => 'P']);

        foreach (['09:00', '10:30', '18:00', '10:30', '09:00', '19:30'] as $index => $time) {
            Service::factory()->create([
                'city_id' => $city->id,
                'date' => $this->berlinDateTime(8, $time),
                'time' => $time,
                'description' => $index === 5 ? 'Abendgebet' : null,
            ]);
        }

        $this->event = Service::factory()->create([
            'city_id' => $city->id,
            'event_class' => 'event',
            'title' => 'Kirchengemeinderatssitzung',
            'description' => 'Sitzung im Gemeindehaus',
            'date' => $this->berlinDateTime(5, '19:30'),
            'time' => '19:30',
            'end' => $this->berlinDateTime(5, '21:00'),
        ]);
    }

    protected function berlinDateTime(int $dayOffset, string $time): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return now('Europe/Berlin')
            ->startOfMonth()
            ->addDays($dayOffset)
            ->setTime($hour, $minute)
            ->setTimezone('UTC');
    }

    public function testCaptureCalendarOverview(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('calendar'),
                'kalender-uebersicht',
                1000
            );
        });
    }

    public function testCaptureEventsCalendar(): void
    {
        $this->superAdminUser->setSetting('calendar_mode', 'events');

        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('calendar'),
                'kalender-veranstaltungen',
                1200
            );
        });
    }

    public function testCaptureCalendarElementScreenshots(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('calendar'),
                'kalender-uebersicht',
                1200
            );

            $browser->waitFor('.service-entry', self::APP_RENDER_TIMEOUT_SECONDS)
                ->mouseover('.service-entry')
                ->pause(300);
            $this->captureCurrentManualElementScreenshot(
                $browser,
                '.service-entry',
                'kalender-gottesdienst-hover',
                0
            );

            $browser->script("document.querySelector('.service-entry .overlay .btn-info')?.click();");
            $browser->waitFor('.modal-dialog', self::APP_RENDER_TIMEOUT_SECONDS);
            $this->captureCurrentManualElementScreenshot(
                $browser,
                '.modal-dialog',
                'kalender-selbsteintrag-dialog',
                300
            );
            $browser->script("document.querySelector('.modal-dialog .btn-secondary')?.click();");

            $browser->pause(300)
                ->click('#btnGroupDrop1');
            $browser->waitFor('.dropdown-menu.show', self::APP_RENDER_TIMEOUT_SECONDS);
            $this->captureCurrentManualElementScreenshot(
                $browser,
                '.dropdown-menu.show',
                'kalender-seiteneinstellungen',
                300
            );
        });
    }

    public function testCaptureEventsCalendarPopup(): void
    {
        $this->superAdminUser->setSetting('calendar_mode', 'events');

        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('calendar'),
                'kalender-veranstaltungen',
                1500
            );

            $browser->waitFor('.event-card', self::APP_RENDER_TIMEOUT_SECONDS);
            $browser->script(<<<'JS'
                document.body.classList.add('modal-open');
                const modal = document.createElement('form');
                modal.innerHTML = `
                    <div class="modal fade show d-block" data-bs-backdrop="static" tabindex="-1" aria-hidden="true" style="background-color: #000A;">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document" style="max-width: 90vw;">
                            <div class="modal-content" style="max-width: 90vw;">
                                <div class="modal-header">
                                    <h5 class="modal-title">Kirchengemeinderatssitzung</h5>
                                </div>
                                <div class="modal-body">
                                    <div>Sitzung im Gemeindehaus</div>
                                    <div><span class="mdi mdi-clock"></span> 6. Mai 2026, 19:30 Uhr</div>
                                    <div><span class="mdi mdi-map-marker"></span> Musterkirche, Mustergemeinde</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" title="Veranstaltung bearbeiten">Bearbeiten</button>
                                    <button type="button" class="btn btn-primary">Schließen</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            JS);
            $browser->waitFor('.modal-dialog', self::APP_RENDER_TIMEOUT_SECONDS);
            $this->captureCurrentManualElementScreenshot(
                $browser,
                '.modal-dialog',
                'kalender-veranstaltung-dialog',
                300
            );
        });
    }
}
