<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\ExtranetTokenScope;
use App\Services\RoleConfigurationSynchronizer;
use App\Services\RoleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies church-scoped Extranet API token issuance, scope isolation, and service/event CRUD.
 */
final class ExtranetServiceManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(RoleConfigurationSynchronizer::class)->synchronize();
    }

    /** Users without the dedicated permission cannot issue church-scoped tokens. */
    public function test_user_without_permission_cannot_create_church_scoped_token(): void
    {
        $city = City::factory()->create(['name' => 'Motzingen']);
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_PASTOR);
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $this->actingAs($user)
            ->post(route('tokens.create'), [
                'title' => 'Gemeinde-Sync',
                'city_id' => $city->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Gemeinde-Sync',
        ]);
    }

    /** Users with the special permission may issue church keys only for their writable churches. */
    public function test_authorized_user_can_create_church_token_only_for_writable_church(): void
    {
        $ownCity = City::factory()->create(['name' => 'Motzingen']);
        $readOnlyCity = City::factory()->create(['name' => 'Baisingen']);
        $foreignCity = City::factory()->create(['name' => 'Nagold']);

        $manager = User::factory()->create();
        $manager->assignRole(RoleService::ROLE_EXTRANET_API_MANAGER);
        $manager->cities()->attach($ownCity->id, ['permission' => 'w']);
        $manager->cities()->attach($readOnlyCity->id, ['permission' => 'r']);

        $this->actingAs($manager)
            ->post(route('tokens.create'), [
                'title' => 'Motzingen Website',
                'city_id' => $ownCity->id,
            ])
            ->assertOk();

        $token = $manager->tokens()->where('name', 'Motzingen Website')->first();
        $this->assertNotNull($token);
        $this->assertSame(
            ExtranetTokenScope::abilitiesForCity($ownCity->id),
            $token->abilities
        );

        $this->actingAs($manager)
            ->post(route('tokens.create'), [
                'title' => 'Read-Only Gemeinde',
                'city_id' => $readOnlyCity->id,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tokens.create'), [
                'title' => 'Fremde Gemeinde',
                'city_id' => $foreignCity->id,
            ])
            ->assertForbidden();
    }

    /** Super-Admins can issue church-scoped Extranet API keys for any church. */
    public function test_super_admin_can_create_church_scoped_token(): void
    {
        $city = City::factory()->create(['name' => 'Motzingen']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $this->actingAs($superAdmin)
            ->post(route('tokens.create'), [
                'title' => 'Admin-Integration',
                'city_id' => $city->id,
            ])
            ->assertOk();

        $token = $superAdmin->tokens()->where('name', 'Admin-Integration')->first();
        $this->assertNotNull($token);
        $this->assertSame(ExtranetTokenScope::abilitiesForCity($city->id), $token->abilities);
    }

    /** Scoped Extranet tokens cannot call general /api routes or unrelated /extranet endpoints. */
    public function test_scoped_extranet_token_is_isolated_to_extranet_service_management(): void
    {
        $city = City::factory()->create();
        $manager = $this->createExtranetManagerForCity($city);
        $plainToken = $manager->createToken(
            'Scoped Key',
            ExtranetTokenScope::abilitiesForCity($city->id)
        )->plainTextToken;

        $this->withToken($plainToken)
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertUnauthorized();

        $this->withToken($plainToken)
            ->getJson('/extranet/user/services/next')
            ->assertForbidden();

        $this->withToken($plainToken)
            ->getJson('/extranet/user/sermons/latest')
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $feedToken = $manager->createToken('Feed', ['calendar:feed'])->plainTextToken;
        $this->withToken($feedToken)
            ->getJson('/extranet/services')
            ->assertUnauthorized();
    }

    /** Revoking the permission or church write access immediately invalidates scoped tokens. */
    public function test_revoking_permission_or_city_access_blocks_existing_church_token(): void
    {
        $city = City::factory()->create();
        $manager = $this->createExtranetManagerForCity($city);
        $plainToken = $manager->createToken(
            'Scoped Key',
            ExtranetTokenScope::abilitiesForCity($city->id)
        )->plainTextToken;

        $this->withToken($plainToken)
            ->getJson('/extranet/services')
            ->assertOk();

        $manager->removeRole(RoleService::ROLE_EXTRANET_API_MANAGER);
        $this->app['auth']->forgetGuards();

        $this->withToken($plainToken)
            ->getJson('/extranet/services')
            ->assertForbidden();
    }

    /** Full CRUD and participant assignment work for both church services and general events. */
    public function test_church_token_can_create_list_show_update_assign_and_delete_services_and_events(): void
    {
        $city = City::factory()->create(['name' => 'Motzingen']);
        $location = Location::factory()->create([
            'city_id' => $city->id,
            'default_time' => '10:00:00',
        ]);
        $otherCity = City::factory()->create(['name' => 'Fremdgemeinde']);
        $otherLocation = Location::factory()->create(['city_id' => $otherCity->id]);

        $manager = $this->createExtranetManagerForCity($city);
        $pastor = User::factory()->create();
        $plainToken = $manager->createToken(
            'Church Sync',
            ExtranetTokenScope::abilitiesForCity($city->id)
        )->plainTextToken;

        // 1. Create a church service (city_id auto-defaulted from single-church token)
        $serviceResponse = $this->withToken($plainToken)
            ->postJson('/extranet/services', [
                'date' => '2026-11-01',
                'title' => 'Reformationsgottesdienst',
                'hidden' => true,
                'needs_reservations' => true,
            ])
            ->assertCreated();

        $serviceId = (int) $serviceResponse->json('service.id');
        $this->assertSame($city->id, $serviceResponse->json('service.city_id'));
        $this->assertSame($location->id, $serviceResponse->json('service.location_id'));
        $this->assertSame('service', $serviceResponse->json('service.event_class'));
        $this->assertSame(
            '2026-11-01 09:00:00',
            Carbon::parse($serviceResponse->json('service.date'))->setTimezone('UTC')->format('Y-m-d H:i:s')
        );

        // 2. Create a recurring general event ('event')
        $eventResponse = $this->withToken($plainToken)
            ->postJson('/extranet/services', [
                'event_class' => 'event',
                'date' => '2026-11-04T19:30:00+01:00',
                'end' => '2026-11-04T21:00:00+01:00',
                'title' => 'Kirchengemeinderat',
                'rrule' => 'FREQ=WEEKLY;COUNT=3',
            ])
            ->assertCreated();

        $eventId = (int) $eventResponse->json('service.id');
        $this->assertSame('event', $eventResponse->json('service.event_class'));
        $this->assertCount(3, $eventResponse->json('service.occurences'));

        // Reject foreign city_id and foreign location_id
        $this->withToken($plainToken)
            ->postJson('/extranet/services', [
                'city_id' => $otherCity->id,
                'date' => '2026-11-08',
            ])
            ->assertForbidden();

        $this->withToken($plainToken)
            ->postJson('/extranet/services', [
                'date' => '2026-11-08',
                'location_id' => $otherLocation->id,
            ])
            ->assertUnprocessable();

        // 3. List both services and events (include_hidden=1 shows hidden service too)
        $listResponse = $this->withToken($plainToken)
            ->getJson('/extranet/services?include_hidden=1')
            ->assertOk();
        $this->assertCount(2, $listResponse->json('services'));

        $onlyEventsResponse = $this->withToken($plainToken)
            ->getJson('/extranet/services?event_class=event')
            ->assertOk();
        $this->assertCount(1, $onlyEventsResponse->json('services'));
        $this->assertSame($eventId, $onlyEventsResponse->json('services.0.id'));

        // 4. Show single event
        $this->withToken($plainToken)
            ->getJson("/extranet/services/{$eventId}")
            ->assertOk()
            ->assertJsonPath('service.title', 'Kirchengemeinderat');

        // 5. Partial PATCH preserves omitted boolean flags (hidden, needs_reservations)
        $this->withToken($plainToken)
            ->patchJson("/extranet/services/{$serviceId}", [
                'title' => 'Geänderter Gottesdienst',
            ])
            ->assertOk()
            ->assertJsonPath('service.title', 'Geänderter Gottesdienst');

        $updatedService = Service::includeAllEventTypes()->findOrFail($serviceId);
        $this->assertSame(1, (int) $updatedService->hidden);
        $this->assertSame(1, (int) $updatedService->needs_reservations);

        // Moving to another city is forbidden
        $this->withToken($plainToken)
            ->patchJson("/extranet/services/{$serviceId}", [
                'city_id' => $otherCity->id,
            ])
            ->assertForbidden();

        // 6. Assign participant ministry
        $this->withToken($plainToken)
            ->postJson("/extranet/services/{$serviceId}/assign", [
                'ministry' => 'P',
                'users' => [$pastor->id],
                'exclusive' => true,
            ])
            ->assertOk();
        $this->assertTrue($updatedService->fresh()->pastors->contains('id', $pastor->id));

        // 7. Delete event and reject access to foreign city's service
        $foreignService = Service::factory()->create([
            'city_id' => $otherCity->id,
            'location_id' => $otherLocation->id,
            'date' => Carbon::parse('2026-11-15 09:00:00', 'UTC'),
        ]);

        $this->withToken($plainToken)
            ->getJson("/extranet/services/{$foreignService->id}")
            ->assertForbidden();

        $this->withToken($plainToken)
            ->deleteJson("/extranet/services/{$foreignService->id}")
            ->assertForbidden();

        $this->withToken($plainToken)
            ->deleteJson("/extranet/services/{$eventId}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertSoftDeleted('services', ['id' => $eventId]);
    }

    /** Create a user holding the Extranet API role and write access to the given church. */
    private function createExtranetManagerForCity(City $city): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_EXTRANET_API_MANAGER);
        $user->cities()->attach($city->id, ['permission' => 'w']);

        return $user;
    }
}
