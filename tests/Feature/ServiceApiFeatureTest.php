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

use App\Models\Calendar\Day;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // LiturgyService reads liturgical calendar from storage; prevent external HTTP calls.
        Storage::fake();
        foreach (range((int)date('Y') - 2, (int)date('Y') + 2) as $year) {
            Storage::put("liturgy/{$year}.json", '{}');
        }
        Storage::put('liturgy/.json', '{}');

        // Permissions required by ServicePolicy / Service $appends.
        foreach (['gd-bearbeiten', 'gd-allgemein-bearbeiten', 'gd-opfer-bearbeiten'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
    }

    /**
     * @return void
     */
    public function testShowReturnsServiceData()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);
        $service = Service::factory()->create(['city_id' => $city->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.service.show', $service));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $service->id]);
    }

    /**
     * @return void
     */
    public function testShowRequiresAuth()
    {
        $service = Service::factory()->create();

        $response = $this->getJson(route('api.service.show', $service));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testShowReturns404ForMissingService()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.service.show', 999999));

        $response->assertNotFound();
    }

    /**
     * @return void
     */
    public function testByDayAndCityReturnsServiceIds()
    {
        $city = City::factory()->create();
        $day = Day::factory()->create();
        $service = Service::factory()->create([
            'day_id' => $day->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson(route('api.services.byDayAndCity', ['day' => $day->id, 'city' => $city->id]));

        $response->assertOk();
        $this->assertTrue(collect($response->json())->contains($service->id));
    }

    /**
     * @return void
     */
    public function testByUserReturnsServicesForUser()
    {
        $user = User::factory()->create();

        $response = $this->getJson(route('api.user.services', $user));

        $response->assertOk();
        $response->assertJsonStructure(['services']);
    }

    /**
     * @return void
     */
    public function testByMonthReturnsServicesInMonth()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.services.calendar.month', [
                'date' => '2024-01-01',
                'cities' => (string) $city->id,
            ]));

        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testDestroyDeletesServiceBySlug()
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $service->refresh();
        $id = $service->id;

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.service.destroy', $service->slug));

        $response->assertOk();
        $this->assertNull(Service::withoutGlobalScopes()->find($id));
    }

    /**
     * @return void
     */
    public function testDestroyRequiresAuth()
    {
        $service = Service::factory()->create();
        $service->refresh();

        $response = $this->deleteJson(route('api.service.destroy', $service->slug));
        $response->assertUnauthorized();
    }
}
