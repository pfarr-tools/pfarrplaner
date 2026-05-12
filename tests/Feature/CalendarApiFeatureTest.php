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

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CalendarApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // LiturgyService fetches liturgical calendar from storage; fake the disk to avoid external HTTP calls.
        Storage::fake();
        foreach (range((int)date('Y') - 2, (int)date('Y') + 2) as $year) {
            Storage::put("liturgy/{$year}.json", '{}');
        }
        Storage::put('liturgy/.json', '{}');

        foreach (['gd-bearbeiten', 'gd-allgemein-bearbeiten', 'gd-opfer-bearbeiten'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
    }

    /**
     * @return void
     */
    public function testMonthReturnsCalendarData()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.month', ['date' => '2024-01']));

        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testMonthRequiresAuth()
    {
        $response = $this->getJson(route('api.calendar.month', ['date' => '2024-01']));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testServiceReturnsCalendarServiceData()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);
        $service = Service::factory()->create(['city_id' => $city->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.service', $service));

        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testServiceRequiresAuth()
    {
        $service = Service::factory()->create();

        $response = $this->getJson(route('api.calendar.service', $service));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testCityReturnsServicesForMonth()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.byCityAndMonth', ['city' => $city->id, 'date' => '2024-01']));

        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testQuickPickReturnsServices()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.quick-pick', ['date' => '01.01.2024']));

        $response->assertOk();
    }
}
