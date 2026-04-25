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

use App\Models\Leave\Poolmaster;
use App\Services\RoleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\AbstractModelUnitTest;

class PoolmasterUnitTest extends AbstractModelUnitTest
{
    protected $modelClass = Poolmaster::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /** Override: date fields are serialized as ISO 8601 in JSON but factory produces Y-m-d strings */
    public function testCreateViaApi()
    {
        $data = $this->factory()->raw();

        $apiRoute = 'api.' . ($this->modelClass)::pluralKey() . '.store';
        $response = $this->actingAs($this->testUser, 'api')
            ->postJson(route($apiRoute), $data);

        $response->assertStatus(200);
        $dateFields = array_keys(($this->modelClass)::make()->getCasts());
        foreach ($data as $key => $value) {
            if (in_array($key, $dateFields)) {
                $response->assertJson(fn(AssertableJson $json) => $json
                    ->where($key, fn($jsonValue) => str_starts_with($jsonValue, $value))
                    ->etc()
                );
            } else {
                $response->assertJson([$key => $value]);
            }
        }
    }

    /** Override: Poolmaster policy uses role checks, no city sync needed */
    public function testUpdateViaApi()
    {
        $existing = $this->factory()->create();
        $this->assertTrue($this->testUser->can('update', $existing));

        $data = $this->factory()->raw();
        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.update';
        $response = $this->actingAs($this->testUser, 'api')
            ->patchJson(route($apiRoute, $existing->id), $data);
        $response->assertStatus(200);
    }

    /** Override: compare date fields by string representation since model casts to Carbon */
    public function testCreateViaAction()
    {
        Event::fake();

        $data = $this->factory()->raw();
        $action = app(($this->modelClass)::getContractName('create'));
        $model = $action->create($this->testUser, $data);
        $this->assertIsObject($model);
        $this->assertEquals(1, ($this->modelClass)::count());

        foreach ($data as $key => $value) {
            $modelValue = $model->$key;
            if ($modelValue instanceof Carbon) {
                $this->assertEquals($value, $modelValue->toDateString());
            } else {
                $this->assertEquals($value, $modelValue);
            }
        }

        Event::assertDispatched(($this->modelClass)::relatedClass('App\\Events\\Models\\###\\Created###'));
    }

    /** Override: compare date fields by string representation since model casts to Carbon */
    public function testUpdateViaAction()
    {
        Event::fake();

        $original = $this->factory()->create();
        $data = $this->factory()->raw();
        $action = app(($this->modelClass)::getContractName('update'));
        $model = $action->update($this->testUser, $original, $data);
        $this->assertIsObject($model);
        $this->assertEquals(1, ($this->modelClass)::count());
        $this->assertEquals($model->id, $original->id);

        foreach ($data as $key => $value) {
            $modelValue = $model->$key;
            if ($modelValue instanceof Carbon) {
                $this->assertEquals($value, $modelValue->toDateString());
            } else {
                $this->assertEquals($value, $modelValue);
            }
        }

        Event::assertDispatched(($this->modelClass)::relatedClass('App\\Events\\Models\\###\\Updated###'));
    }
}
