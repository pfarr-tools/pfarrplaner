<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Feature;

use App\Calendars\AbstractCalendar;
use App\Calendars\SyncEngines\AbstractSyncEngine;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\Calendar\External\CalendarConnectionEntry;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Verify that service deletion removes the same additional events that synchronization creates. */
class CalendarAlternateDeletionFeatureTest extends TestCase
{
    /**
     * Exercise preparation dates and optional anniversaries without connecting to an external calendar.
     *
     * @param  bool  $includeAlternate  Whether additional events are synchronized.
     * @param  bool  $includeAnniversaries  Whether rite anniversaries are synchronized.
     */
    #[DataProvider('calendarOptions')]
    public function test_deletes_configured_additional_events(bool $includeAlternate, bool $includeAnniversaries): void
    {
        $service = Service::factory()->create();
        $connection = CalendarConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Test calendar',
            'include_alternate' => $includeAlternate,
            'include_rite_anniversaries' => $includeAnniversaries,
        ]);
        $appointment = '20.11.2026 12:00';
        $funeral = Funeral::factory()->create(['service_id' => $service->id, 'appointment' => $appointment]);
        $wedding = Wedding::factory()->create(['service_id' => $service->id, 'appointment' => $appointment]);
        $baptism = Baptism::factory()->create(['service_id' => $service->id, 'appointment' => $appointment]);
        $preparationKeys = ['funeral_prep_'.$funeral->id, 'wedding_prep_'.$wedding->id, 'baptism_prep_'.$baptism->id];
        $anniversaryKeys = ['funeral_anniversary_'.$funeral->id, 'wedding_anniversary_'.$wedding->id];
        $allKeys = array_merge($preparationKeys, $anniversaryKeys, ['unrelated']);

        // This fake represents only the remote calendar boundary; all selection and database queries remain real.
        $calendar = new class extends AbstractCalendar
        {
            /** @var array<string, bool> Remote records keyed by provider ID. */
            public array $events = [];

            /** Remove a remote event while leaving unrelated records intact. */
            public function delete(string $id): void
            {
                unset($this->events[$id]);
            }
        };
        foreach ($allKeys as $key) {
            $calendar->events[$key] = true;
            CalendarConnectionEntry::create([
                'calendar_connection_id' => $connection->id,
                'alternate_key' => $key,
                'foreign_id' => $key,
            ]);
        }
        $engine = new class($connection) extends AbstractSyncEngine {};
        $engine->setCalendar($calendar);

        $engine->deleteSingleService($service);

        $expected = $includeAlternate
            ? ($includeAnniversaries ? ['unrelated'] : array_merge($anniversaryKeys, ['unrelated']))
            : $allKeys;
        $this->assertSame($expected, array_keys($calendar->events));
    }

    /** @return array<string, array{bool, bool}> Calendar option combinations. */
    public static function calendarOptions(): array
    {
        return [
            'preparation only' => [true, false],
            'preparation and anniversaries' => [true, true],
            'additional events disabled' => [false, false],
            'anniversaries alone do not enable additional events' => [false, true],
        ];
    }
}
