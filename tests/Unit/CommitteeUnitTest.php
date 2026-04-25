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

namespace Tests\Unit;

use App\Models\Meetings\Committee;
use App\Models\Places\City;
use App\Services\RoleService;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\AbstractModelUnitTest;

class CommitteeUnitTest extends AbstractModelUnitTest
{
    protected $modelClass = Committee::class;

    protected City $testCity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->testCity = City::factory()->create();
        $this->testUser->cities()->attach($this->testCity->id, ['permission' => 'w']);
    }

    /** Override: index filters by user's cities, so attach committee to user's city */
    public function testIndexViaApi()
    {
        $existing = $this->factory()->create();
        $existing->cities()->attach($this->testCity->id);

        $apiRoute = 'api.' . ($this->modelClass)::pluralKey() . '.index';
        $response = $this->actingAs($this->testUser, 'api')
            ->getJson(route($apiRoute));

        $response->assertStatus(200);
        $response->assertJson(fn(AssertableJson $json) => $json->has(1)
            ->first(fn(AssertableJson $json) =>
                $json->where('id', $existing->id)->etc()
            )
        );
    }

    /** Override: sync the committee's first city for update/delete permission */
    public function testUpdateViaApi()
    {
        $existing = $this->factory()->create();
        $city = City::factory()->create();
        $existing->cities()->attach($city->id);
        $this->testUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->assertTrue($this->testUser->can('update', $existing->fresh()));

        $data = $this->factory()->raw();
        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.update';
        $response = $this->actingAs($this->testUser, 'api')
            ->patchJson(route($apiRoute, $existing->id), $data);
        $response->assertStatus(200);
    }

    /** Override: sync the committee's first city for delete permission */
    public function testDeleteViaApi()
    {
        $existing = $this->factory()->create();
        $city = City::factory()->create();
        $existing->cities()->attach($city->id);
        $this->testUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->assertTrue($this->testUser->can('delete', $existing->fresh()));

        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.destroy';
        $response = $this->actingAs($this->testUser, 'api')
            ->delete(route($apiRoute, $existing->id));
        $response->assertStatus(200);
        $this->assertEquals(0, ($this->modelClass)::count());
    }
}
