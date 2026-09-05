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
use Tests\TestCase;

class ServiceKonfiAppQrAccessorUnitTest extends TestCase
{
    public function testAccessorReturnsExistingKonfiAppQrCodeWithoutCallingIntegration(): void
    {
        $city = City::factory()->create(['konfiapp_apikey' => 'demo-key']);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::parse('2026-07-20 08:00:00', 'UTC'),
            'konfiapp_event_type' => 17,
            'konfiapp_event_qr' => 'existing-qr',
        ])->fresh();

        $this->assertSame('existing-qr', $service->konfiapp_event_qr);
        $this->assertSame('existing-qr', $service->fresh()->getRawOriginal('konfiapp_event_qr'));
    }
}
