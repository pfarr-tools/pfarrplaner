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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleSelectApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testSelectReturnsUsersWithNameField()
    {
        $city = City::factory()->create();
        $requester = User::factory()->create();
        $requester->cities()->attach($city->id);

        $other = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Müller']);
        $other->cityScopes()->attach($city->id);

        $response = $this->actingAs($requester, 'api')
            ->getJson(route('api.people.select'));

        $response->assertOk();
        $users = $response->json('users');
        $this->assertNotEmpty($users);

        $found = collect($users)->firstWhere('id', $other->id);
        $this->assertNotNull($found, 'Other user not found in response');
        $this->assertArrayHasKey('name', $found);
        $this->assertSame('Anna Müller', $found['name']);
    }

    /**
     * @return void
     */
    public function testSelectNameFieldUsesNameServiceFirstLastFormat()
    {
        $city = City::factory()->create();
        $requester = User::factory()->create();
        $requester->cities()->attach($city->id);

        $other = User::factory()->create(['first_name' => 'Karl', 'last_name' => 'Schmidt', 'title' => 'Pfr.']);
        $other->cityScopes()->attach($city->id);

        $response = $this->actingAs($requester, 'api')
            ->getJson(route('api.people.select'));

        $response->assertOk();
        $found = collect($response->json('users'))->firstWhere('id', $other->id);
        $this->assertNotNull($found);
        // FIRST_LAST format does not include title — title belongs to TITLE_FIRST_LAST
        $this->assertSame('Karl Schmidt', $found['name']);
    }

    /**
     * @return void
     */
    public function testSelectOnlyReturnsUsersVisibleToRequester()
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();

        $requester = User::factory()->create();
        $requester->cities()->attach($city->id);

        $visible = User::factory()->create(['first_name' => 'Sichtbar', 'last_name' => 'Person']);
        $visible->cityScopes()->attach($city->id);

        $hidden = User::factory()->create(['first_name' => 'Versteckt', 'last_name' => 'Person']);
        $hidden->cityScopes()->attach($otherCity->id);

        $response = $this->actingAs($requester, 'api')
            ->getJson(route('api.people.select'));

        $response->assertOk();
        $ids = collect($response->json('users'))->pluck('id');
        $this->assertTrue($ids->contains($visible->id));
        $this->assertFalse($ids->contains($hidden->id));
    }
}
