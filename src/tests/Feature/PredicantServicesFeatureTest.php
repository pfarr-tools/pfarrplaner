<?php

namespace Tests\Feature;

use App\HomeScreen\Tabs\PredicantServicesHomeScreenTab;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\RoleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredicantServicesFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_tab_shows_only_open_future_services_in_visible_cities(): void
    {
        $visibleCity = City::factory()->create();
        $otherCity = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($visibleCity);
        $user->assignRole(RoleService::ROLE_PREDICANT);

        $openService = Service::factory()->create([
            'city_id' => $visibleCity->id,
            'date' => Carbon::tomorrow(),
            'need_predicant' => true,
        ]);
        Service::factory()->create([
            'city_id' => $visibleCity->id,
            'date' => Carbon::tomorrow(),
            'need_predicant' => false,
        ]);
        Service::factory()->create([
            'city_id' => $otherCity->id,
            'date' => Carbon::tomorrow(),
            'need_predicant' => true,
        ]);
        Service::factory()->create([
            'city_id' => $visibleCity->id,
            'date' => Carbon::yesterday(),
            'need_predicant' => true,
        ]);

        $this->actingAs($user, 'api');
        $tab = new PredicantServicesHomeScreenTab([]);

        $this->assertSame([$openService->id], $tab->buildQuery()->pluck('id')->all());
    }

    public function test_claim_assigns_current_user_and_clears_predicant_flag(): void
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city);
        $user->assignRole(RoleService::ROLE_PREDICANT);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::tomorrow(),
            'need_predicant' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.service.claimPredicant', $service));

        $response->assertOk();
        $this->assertDatabaseHas('service_user', [
            'service_id' => $service->id,
            'user_id' => $user->id,
            'category' => 'P',
        ]);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'need_predicant' => 0,
        ]);
    }

    public function test_claim_cannot_be_used_for_invisible_or_already_assigned_services(): void
    {
        $visibleCity = City::factory()->create();
        $otherCity = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($visibleCity);
        $user->assignRole(RoleService::ROLE_PREDICANT);
        $service = Service::factory()->create([
            'city_id' => $otherCity->id,
            'date' => Carbon::tomorrow(),
            'need_predicant' => true,
        ]);

        $this->actingAs($user, 'api')
            ->postJson(route('api.service.claimPredicant', $service))
            ->assertForbidden();

        $service->update(['city_id' => $visibleCity->id]);
        $otherUser = User::factory()->create();
        $service->pastors()->attach($otherUser, ['category' => 'P']);

        $this->postJson(route('api.service.claimPredicant', $service))
            ->assertUnprocessable();
    }
}
