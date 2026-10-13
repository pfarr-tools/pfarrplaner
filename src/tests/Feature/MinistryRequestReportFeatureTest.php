<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 */

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Reports\MinistryRequestReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MinistryRequestReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testServicesCanExcludeServicesWithAnExistingEntryForTheSelectedMinistry(): void
    {
        $city = City::factory()->create();
        $participant = User::factory()->create();
        $openService = Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2026-01-11 10:00:00',
        ]);
        $filledService = Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2026-01-18 10:00:00',
        ]);
        $filledService->participants()->attach($participant->id, ['category' => 'P']);

        $request = Request::create('/report/ministryRequest/services', 'POST', [
            'start' => '2026-01-01',
            'end' => '2026-01-31',
            'cities' => [$city->id],
            'ministry' => 'P',
            'only_open' => true,
        ]);

        $response = (new MinistryRequestReport())->services($request);

        $this->assertSame([$openService->id], collect($response->getData())->pluck('id')->all());
    }
}
