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

    public function testTabShowsOnlyOpenFutureServicesInVisibleCities(): void
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

    public function testClaimAssignsCurrentUserAndClearsPredicantFlag(): void
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

    public function testClaimCannotBeUsedForInvisibleOrAlreadyAssignedServices(): void
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
