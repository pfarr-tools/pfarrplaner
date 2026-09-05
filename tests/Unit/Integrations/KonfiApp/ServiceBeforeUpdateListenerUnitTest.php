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

namespace Tests\Unit\Integrations\KonfiApp;

use App\Events\ServiceBeforeUpdate;
use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Integrations\KonfiApp\ServiceBeforeUpdateListener;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class ServiceBeforeUpdateListenerUnitTest extends TestCase
{
    public function testListenerPassesRequestedKonfiAppTypeAndUpdatedDateToIntegration(): void
    {
        $city = City::factory()->create(['konfiapp_apikey' => 'demo-key']);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::parse('2026-07-20 08:00:00', 'UTC'),
            'konfiapp_event_type' => null,
            'konfiapp_event_qr' => null,
        ]);
        $newDate = Carbon::parse('2026-07-27 09:30:00', 'UTC');

        $integration = Mockery::mock(KonfiAppIntegration::class);
        $receivedService = null;
        $receivedChange = null;
        $integration->shouldReceive('handleServiceUpdate')
            ->once()
            ->withArgs(function (Service $passedService, $requestedChange) use (&$receivedService, &$receivedChange) {
                $receivedService = $passedService;
                $receivedChange = $requestedChange;

                return true;
            });

        $listener = new class($integration) extends ServiceBeforeUpdateListener {
            public function __construct(private KonfiAppIntegration $integration)
            {
            }

            protected function isIntegrationActive(City $city): bool
            {
                return true;
            }

            protected function resolveIntegration(City $city): KonfiAppIntegration
            {
                return $this->integration;
            }
        };

        $listener->handle(new ServiceBeforeUpdate($service, [
            'konfiapp_event_type' => 17,
            'date' => $newDate,
        ]));

        $this->assertNotNull($receivedService);
        $this->assertTrue($receivedService->is($service));
        $this->assertSame(17, $receivedChange);
        $this->assertTrue($receivedService->date->equalTo($newDate));
    }
}
