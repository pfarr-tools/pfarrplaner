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

use App\Models\Service;
use App\Models\Sermon;
use App\Models\Places\City;
use Carbon\Carbon;
use Laravel\Dusk\Browser;

class ServiceEditorScreenshotTest extends ManualScreenshotTestCase
{
    protected Service $service;
    protected Service $event;

    protected function setUp(): void
    {
        parent::setUp();
        $city = City::factory()->create(['name' => 'Mustergemeinde']);
        $this->superAdminUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->superAdminUser->homeCities()->attach($city->id);

        $this->service = Service::factory()->create([
            'city_id' => $city->id,
            'description' => 'Musterpredigtgottesdienst',
        ]);
        $sermon = Sermon::create(['title' => 'Musterpredigt']);
        $this->service->update(['sermon_id' => $sermon->id]);
        $this->service->comments()->create([
            'user_id' => $this->superAdminUser->id,
            'body' => "Bitte die Begrüßung mit dem Kirchengemeinderat abstimmen.\nDie Rückmeldung aus dem Pfarrbüro fehlt noch.",
            'private' => 0,
        ]);
        $this->service->comments()->create([
            'user_id' => $this->superAdminUser->id,
            'body' => 'Nur für mich: Am Freitag noch einmal beim Musikteam nachfragen.',
            'private' => 1,
        ]);

        $this->event = Service::factory()->create([
            'city_id' => $city->id,
            'event_class' => 'event',
            'title' => 'Kirchenchorprobe',
            'description' => 'Wöchentliche Probe im Gemeindehaus',
            'date' => $this->berlinDateTime(9, '19:30'),
            'time' => '19:30',
            'end' => $this->berlinDateTime(9, '21:00'),
            'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TU;COUNT=12',
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

    public function testCaptureServiceEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('service.edit', $this->service->slug),
                'veranstaltung-editor-allgemeines',
                800
            );
            $this->captureTab($browser, 'people', 'veranstaltung-editor-mitwirkende');
            $this->captureTab($browser, 'offerings', 'veranstaltung-editor-opfer');
            $this->captureTab($browser, 'rites', 'veranstaltung-editor-kasualien');
            $this->captureTab($browser, 'cc', 'veranstaltung-editor-kinderkirche');
            $this->captureTab($browser, 'registrations', 'veranstaltung-editor-anmeldungen');
            $this->captureTab($browser, 'ads', 'veranstaltung-editor-werbung');
            $this->captureTab($browser, 'attachments', 'veranstaltung-editor-dateien');
            $this->captureTab($browser, 'comments', 'veranstaltung-editor-kommentare');

            $browser->click('#dropdownMenuLink')
                ->waitFor('#dropdownMenuLink + .dropdown-menu.show', self::APP_RENDER_TIMEOUT_SECONDS);
            $this->captureCurrentManualElementScreenshot(
                $browser,
                '#dropdownMenuLink + .dropdown-menu.show',
                'veranstaltung-editor-weitere-aktionen',
                300
            );

            $this->captureManualScreenshot(
                $browser,
                route('service.edit', $this->event->slug),
                'veranstaltung-editor-wiederholungen',
                800
            );
            $this->captureTab($browser, 'recurrence', 'veranstaltung-editor-wiederholungen');
        });
    }
}
