<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
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
        foreach (range((int) date('Y') - 2, (int) date('Y') + 2) as $year) {
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
    public function test_show_returns_service_data()
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
    public function test_show_returns_event_data()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);
        $event = Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2024-01-14 18:00:00',
            'title' => 'Gemeindeabend',
            'event_class' => 'event',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.service.show', $event));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $event->id, 'event_class' => 'event']);
    }

    /**
     * @return void
     */
    public function test_show_requires_auth()
    {
        $service = Service::factory()->create();

        $response = $this->getJson(route('api.service.show', $service));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_show_returns404_for_missing_service()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.service.show', 999999));

        $response->assertNotFound();
    }

    /**
     * @return void
     */
    public function test_by_day_and_city_returns_service_ids()
    {
        $city = City::factory()->create();
        $day = Day::factory()->create();
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'day_id' => $day->id,
            'city_id' => $city->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.services.byDayAndCity', ['day' => $day->id, 'city' => $city->id]));

        $response->assertOk();
        $this->assertTrue(collect($response->json())->contains($service->id));
    }

    /**
     * @return void
     */
    public function test_by_user_returns_services_for_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.user.services', $user));

        $response->assertOk();
        $response->assertJsonStructure(['services']);
    }

    /**
     * @return void
     */
    public function test_by_day_and_city_requires_auth()
    {
        $city = City::factory()->create();
        $day = Day::factory()->create();

        $response = $this->getJson(route('api.services.byDayAndCity', ['day' => $day->id, 'city' => $city->id]));

        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_by_user_requires_auth()
    {
        $user = User::factory()->create();

        $response = $this->getJson(route('api.user.services', $user));

        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_by_month_returns_services_in_month()
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
    public function test_destroy_deletes_service_by_slug()
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $service->refresh();
        $id = $service->id;

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.service.destroy', $service->slug));

        $response->assertOk();
        $this->assertSoftDeleted('services', ['id' => $id]);
    }

    /**
     * @return void
     */
    public function test_destroy_requires_auth()
    {
        $service = Service::factory()->create();
        $service->refresh();

        $response = $this->deleteJson(route('api.service.destroy', $service->slug));
        $response->assertUnauthorized();
    }
}
