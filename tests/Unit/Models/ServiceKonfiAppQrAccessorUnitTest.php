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

namespace Tests\Unit\Models;

use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class ServiceKonfiAppQrAccessorUnitTest extends TestCase
{
    public function testAccessorCreatesMissingKonfiAppQrCodeWhenIntegrationIsConfigured(): void
    {
        $city = City::factory()->create(['konfiapp_apikey' => 'demo-key']);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::parse('2026-07-20 08:00:00', 'UTC'),
            'konfiapp_event_type' => 17,
            'konfiapp_event_qr' => null,
        ])->fresh();

        $integration = Mockery::mock();
        $integration->shouldReceive('addQRCodeToService')
            ->once()
            ->withArgs(function (Service $passedService) use ($service) {
                return $passedService->is($service);
            })
            ->andReturnUsing(function (Service $passedService) {
                $passedService->update(['konfiapp_event_qr' => 'generated-qr']);
                return $passedService->fresh();
            });

        $integrationClass = Mockery::mock('alias:App\Integrations\KonfiApp\KonfiAppIntegration');
        $integrationClass->shouldReceive('isActive')
            ->once()
            ->withArgs(function (City $passedCity) use ($city) {
                return $passedCity->is($city);
            })
            ->andReturn(true);
        $integrationClass->shouldReceive('get')
            ->once()
            ->withArgs(function (City $passedCity) use ($city) {
                return $passedCity->is($city);
            })
            ->andReturn($integration);

        $this->assertSame('generated-qr', $service->konfiapp_event_qr);
        $this->assertSame('generated-qr', $service->fresh()->getRawOriginal('konfiapp_event_qr'));
    }
}
