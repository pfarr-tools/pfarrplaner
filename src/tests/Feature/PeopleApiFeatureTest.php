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
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testSearchReturnsUsersMatchingName()
    {
        $city = City::factory()->create();
        $requester = User::factory()->create();
        $requester->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $requester->cities()->attach($city->id);

        $other = User::factory()->create(['first_name' => 'Brigitte', 'last_name' => 'Musterfrau']);

        $response = $this->actingAs($requester, 'api')
            ->postJson(route('api.people.search', ['searchString' => 'Brigitte']));

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($other->id));
    }

    /**
     * @return void
     */
    public function testSearchExcludesUsersAlreadyInRequesterCity()
    {
        $city = City::factory()->create();
        $requester = User::factory()->create();
        $requester->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $requester->cities()->attach($city->id);

        $inCity = User::factory()->create(['first_name' => 'Sichtbar', 'last_name' => 'Person']);
        $inCity->cityScopes()->attach($city->id);

        $response = $this->actingAs($requester, 'api')
            ->postJson(route('api.people.search', ['searchString' => 'Sichtbar']));

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertFalse($ids->contains($inCity->id));
    }

    /**
     * @return void
     */
    public function testSearchRequiresAuth()
    {
        $response = $this->postJson(route('api.people.search', ['searchString' => 'test']));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testActivateAddsUserToCityScope()
    {
        $city = City::factory()->create();
        $requester = User::factory()->create();
        $requester->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $requester->cities()->attach($city->id);

        $target = User::factory()->create();

        $response = $this->actingAs($requester, 'api')
            ->postJson(route('api.people.activate', ['user' => $target->id, 'city' => $city->id]));

        $response->assertOk();
        $this->assertTrue($target->fresh()->cityScopes()->where('city_id', $city->id)->exists());
    }

    /**
     * @return void
     */
    public function testActivateRequiresAuth()
    {
        $city = City::factory()->create();
        $target = User::factory()->create();

        $response = $this->postJson(route('api.people.activate', ['user' => $target->id, 'city' => $city->id]));
        $response->assertUnauthorized();
    }
}
