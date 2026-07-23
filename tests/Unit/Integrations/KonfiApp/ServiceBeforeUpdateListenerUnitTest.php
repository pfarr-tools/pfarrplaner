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
        $integration->shouldReceive('handleServiceUpdate')
            ->once()
            ->withArgs(function (Service $passedService, int $requestedChange) use ($service, $newDate) {
                return $passedService->is($service)
                    && ($requestedChange === 17)
                    && $passedService->date->equalTo($newDate);
            });

        $listener = Mockery::mock(ServiceBeforeUpdateListener::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $listener->shouldReceive('isIntegrationActive')->once()->with($city)->andReturn(true);
        $listener->shouldReceive('resolveIntegration')->once()->with($city)->andReturn($integration);

        $listener->handle(new ServiceBeforeUpdate($service, [
            'konfiapp_event_type' => 17,
            'date' => $newDate,
        ]));
    }
}
