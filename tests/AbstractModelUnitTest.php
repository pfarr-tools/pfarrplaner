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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests;

use App\Http\Controllers\AbstractCRUDController;
use App\Http\Controllers\Api\AbstractApiCRUDController;
use App\Models\AbstractModel;
use App\Models\People\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Traits\TestsClasses;

abstract class AbstractModelUnitTest extends TestCase
{

    use TestsClasses;
    use RefreshDatabase;

    protected $modelName;
    protected $modelClass = '';

    /** @var User */
    protected $testUser = null;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->assertNotEmpty($this->modelClass);
        $this->assertEquals(1, preg_match('/Tests\\\\Unit\\\\(.*)UnitTest/', get_called_class(), $matches));
        $this->assertGreaterThan(1, count($matches));
        $this->modelName = $matches[1];
    }

    /**
     * Get a protected property from an object
     */
    protected static function getProtectedProperty($object, $property)
    {
        $reflectedClass = new \ReflectionClass($object);
        $reflection = $reflectedClass->getProperty($property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }


    /**
     * Test if model class exists and is a descendant of AbstractModel
     * @return void
     * @test
     */
    public function testModelClassExistsAndIsDescendant()
    {
        $this->assertTrue(
            class_exists($this->modelClass),
            'Model class ' . $this->modelClass . ' does not exist.'
        );
        $this->assertTrue(
            is_subclass_of($this->modelClass, AbstractModel::class),
            'Model class ' . $this->modelClass . ' is not a descendant of ' . AbstractModel::class,
        );
    }

    /**
     * Test if the model table is present
     * @return void
     */
    public function testModelTableExists()
    {
        $model = (new $this->modelClass);
        $table = $model->getTable();
        $this->assertTrue(Schema::hasTable($table), 'Missing table ' . $table);
    }

    /**
     * Test if columns from fillable are present
     * @return void
     * @test
     */
    public function testColumnsFromFillableArePresent()
    {
        $model = (new $this->modelClass);
        $table = $model->getTable();
        $fillable = $this->getProtectedProperty($model, 'fillable');
        $this->assertIsArray(
            $fillable,
            $this->modelClass . '::$fillable is not an array'
        );
        foreach ($fillable as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                'Table ' . $table . ' is missing column ' . $column
            );
        }
    }

    /**
     * test if controller is present and descendent of AbstractCRUDController
     * @return void
     * @test
     */
    public function testControllerClassIsPresentAndDescendant()
    {
        $this->assertTrue(class_exists(($this->modelClass)::controllerClass()));
        $this->assertTrue(
            is_subclass_of(($this->modelClass)::controllerClass(), AbstractCRUDController::class),
            ($this->modelClass)::controllerClass() . ' is not a descendant of ' . AbstractCRUDController::class
        );
    }

    /**
     * test if API controller is present and descendent of AbstractCRUDController
     * @return void
     * @test
     */
    public function testApiControllerClassIsPresentAndDescendant()
    {
        $this->assertTrue(class_exists(($this->modelClass)::apiControllerClass()));
        $this->assertTrue(
            is_subclass_of(($this->modelClass)::apiControllerClass(), AbstractApiCRUDController::class),
            ($this->modelClass)::apiControllerClass() . ' is not a descendant of ' . AbstractApiCRUDController::class
        );
    }


    /**
     * Test if controller methods are present
     * @return void
     * @test
     */
    public function testControllerMethodsArePresent()
    {
        $routes = ($this->modelClass)::getRoutes();
        foreach (['web' => ($this->modelClass)::controllerClass(), 'api' => ($this->modelClass)::apiControllerClass()] as $routeClass => $controllerClass) {
            foreach ($routes[$routeClass] ?? [] as $routeData) {
                $this->assertTrue(
                    method_exists($controllerClass, $routeData['verb']),
                    'Controller method missing: ' . $controllerClass . '::' . $routeData['verb'] . '()'
                );
            }
        }
    }


    /**
     * Test if all the defined routes for the model are present
     * @return void
     * @test
     */
    public function testRoutesArePresent()
    {
        foreach (($this->modelClass)::getRoutes() as $routeClass => $routes) {
            foreach (array_keys($routes) as $routeKey) {
                $this->assertTrue(Route::has($routeKey), Str::ucfirst($routeClass).' route '.$routeKey.' is missing.');
            }
        }
    }


    /**
     * Test if the contract interfaces exist
     * @return void
     * @test
     */
    public function testContractsExists()
    {
        foreach (['create', 'update', 'delete'] as $verb) {
            $this->assertInterfaceExists(($this->modelClass)::getContractName($verb));
        }
    }

    /**
     * Test if the contract resolve to a concrete action
     * @return void
     * @test
     */
    public function testContractsResolve()
    {
        foreach (['create', 'update', 'delete'] as $verb) {
            $this->assertInstanceOf(
                ($this->modelClass)::getActionName($verb),
                app(($this->modelClass)::getContractName($verb))
            );
        }
    }


    /**
     * Test if factory can be instantiated
     * @return void
     */
    public function testFactoryExists()
    {
        $factory = $this->factory();
        $this->assertIsObject($factory, 'Factory class does not exist');
        $this->assertIsA(
            Str::replace('App\\Models\\', 'Database\\Factories\\', $this->modelClass . 'Factory'),
            $factory
        );
    }


    // test with contracted actions

    /**
     * Test if model can be created via action
     * @return void
     * @test
     */
    public function testCreateViaAction()
    {
        Event::fake();

        $data = $this->factory()->raw();
        $action = app(($this->modelClass)::getContractName('create'));
        $model = $action->create($this->testUser, $data);
        $this->assertIsObject($model);
        $this->assertIsA($this->modelClass, $model);
        $this->assertEquals(1, ($this->modelClass)::count());
        $dbModel = ($this->modelClass)::first();
        $this->assertEquals($model->id, ($this->modelClass)::first()->id);
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $model->$key);
            $this->assertEquals($value, $dbModel->$key);
            $this->assertEquals($model->$key, $dbModel->$key);
        }

        Event::assertDispatched(($this->modelClass)::relatedClass('App\\Events\\Models\\###\\Created###'));
    }

    /**
     * Test if model can be updated via action
     * @return void
     * @test
     */
    public function testUpdateViaAction()
    {
        Event::fake();

        $original = $this->factory()->create();
        $data = $this->factory()->raw();
        $action = app(($this->modelClass)::getContractName('update'));
        $model = $action->update($this->testUser, $original, $data);
        $this->assertIsObject($model);
        $this->assertIsA($this->modelClass, $model);
        $this->assertEquals(1, ($this->modelClass)::count());
        $dbModel = ($this->modelClass)::first();
        $this->assertEquals($model->id, ($this->modelClass)::first()->id);
        $this->assertEquals($model->id, $original->id);
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $model->$key);
            $this->assertEquals($value, $dbModel->$key);
            $this->assertEquals($model->$key, $dbModel->$key);
        }

        Event::assertDispatched(($this->modelClass)::relatedClass('App\\Events\\Models\\###\\Updated###'));
    }

    /**
     * Test if model can be deleted via action
     * @return void
     * @test
     */
    public function testDeleteViaAction()
    {
        Event::fake();

        $original = $this->factory()->create();
        $this->assertEquals(1, ($this->modelClass)::count());
        $action = app(($this->modelClass)::getContractName('delete'));
        $result = $action->delete($this->testUser, $original);
        $this->assertTrue($result);
        $this->assertEquals(0, ($this->modelClass)::count());

        Event::assertDispatched(($this->modelClass)::relatedClass('App\\Events\\Models\\###\\Deleted###'));
    }

    // test via api

    /**
     * Test if model index can be retrieved via API call
     * @return void
     * @test
     */
    public function testIndexViaApi()
    {
        $existing = $this->factory()->create();

        $apiRoute = 'api.' . ($this->modelClass)::pluralKey() . '.index';
        $response = $this->actingAs($this->testUser, 'api')
            ->getJson(route($apiRoute));

        $response->assertStatus(200);
        $response->assertJson(fn(AssertableJson $json) => $json->has(1)
            ->first(fn(AssertableJson $json) =>
                $json->where('id', $existing->id)
                    ->etc()
            )
        );
    }

    /**
     * Test if model can be retrieved via API call
     * @return void
     * @test
     */
    public function testShowViaApi()
    {
        if (in_array('show', ($this->modelClass)::$exceptRoutes['api'] ?? [])) {
            $this->assertTrue(true);
            return;
        }
        $existing = $this->factory()->create();

        $apiRoute = 'api.' . ($this->modelClass)::pluralKey() . '.show';
        $response = $this->actingAs($this->testUser, 'api')
            ->getJson(route($apiRoute));

        $response->assertStatus(200);
        $response->assertJson(fn(AssertableJson $json) => $json
            ->where('id', $existing->id)
        );
    }


    /**
     * Test if model can be created via API call
     * @return void
     * @test
     */
    public function testCreateViaApi()
    {
        $data = $this->factory()->raw();

        $apiRoute = 'api.' . ($this->modelClass)::pluralKey() . '.store';
        $response = $this->actingAs($this->testUser, 'api')
            ->postJson(route($apiRoute), $data);

        $response->assertStatus(200);
        foreach ($data as $key => $value) {
            $response->assertJson([$key => $value]);
        }
    }

    /**
     * Test if model can be updated via API call
     * @return void
     * @test
     */
    public function testUpdateViaApi()
    {
        $existing = $this->factory()->create();
        $this->testUser->adminCities()->sync([$existing->id]);
        $this->assertTrue($this->testUser->can('update', $existing));

        $data = $this->factory()->raw();

        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.update';
        $response = $this->actingAs($this->testUser, 'api')
            ->patchJson(route($apiRoute, $existing->id), $data);

        $response->assertStatus(200);
        foreach ($data as $key => $value) {
            $response->assertJson([$key => $value]);
        }
        $this->testUser->adminCities()->sync([]);
    }


    /**
     * Test if model can be deleted via API call
     * @return void
     * @test
     */
    public function testDeleteViaApi()
    {
        $existing = $this->factory()->create();
        $this->assertTrue($this->testUser->can('delete', $existing));

        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.destroy';
        $response = $this->actingAs($this->testUser, 'api')
            ->delete(route($apiRoute, $existing->id));

        $response->assertStatus(200);
        $this->assertEquals(0, ($this->modelClass)::count());

    }

    /**
     * Checks if a policy class exists for the model
     * @return void
     */
    public function testPolicyExists()
    {
        $policy = Gate::getPolicyFor($this->modelClass);
        $this->assertNotNull($policy, 'There is no registered policy for '.$this->modelClass);
    }


    /**
     * @return Factory
     */
    protected function factory(): Factory {
        return ($this->modelClass)::factory();
    }




    protected function setUp(): void
    {
        parent::setUp(); //
        $this->testUser = User::factory()->create();
    }


}
