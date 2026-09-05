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

use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class AbstractModelFeatureTest extends TestCase
{

    protected $modelName;
    protected $modelClass = '';

    /** @var User */
    protected $testUser = null;

    // test via web

    public function testIndexViewWorks()
    {
        $this->assertEquals(3, Role::count());

        $existing = $this->factory()->create();

        $response = $this->actingAs($this->testUser)->get(($this->modelClass)::getSingleRoute('web', 'index'));
        $response->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page
                ->component(($this->modelClass)::getVuePath('index'))
                ->has(($this->modelClass)::pluralKey(), 1)
                ->has(($this->modelClass)::pluralKey() . '.0', fn(Assert $page) => $page
                    ->where('id', $existing->id)
                    ->etc()
                )
                ->etc()
            );
    }

    public function testEditorViewWorks()
    {
        $this->assertEquals(3, Role::count());

        $existing = $this->factory()->create();

        $response = $this->actingAs($this->testUser)->get(
            route(($this->modelClass)::getSingleRouteName('web', 'edit'), $existing->id)
        );
        $response->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page
                ->component(($this->modelClass)::getVuePath('editor'))
                ->has(($this->modelClass)::singularKey(), fn(Assert $page) => $page
                    ->where('id', $existing->id)
                    ->etc()
                )
                ->etc()
            );
    }


    /**
     * Test if model can be created via the frontend
     * @return void
     */
    public function testCreateViaFrontend()
    {
        $data = $this->factory()->raw();

        $routeName = ($this->modelClass)::getSingleRouteName('web', 'store');
        $response = $this->actingAs($this->testUser, 'web')
            ->post(route($routeName), $data);

        $response->assertStatus(302);
        $this->assertEquals(1, ($this->modelClass)::count());
        $created = ($this->modelClass)::first();
        $response->assertRedirect($this->getActionRedirectUrl($created));
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $created->$key);
        }
    }

    /**
     * Test if model can be updated via the frontend
     * @return void
     */
    public function testUpdateViaFrontend()
    {
        $existing = $this->factory()->create();
        $this->assertTrue($this->testUser->can('update', $existing));

        $data = $this->factory()->raw();

        $routeName = ($this->modelClass)::getSingleRouteName('web', 'update');
        $response = $this->actingAs($this->testUser, 'web')
            ->patchJson(route($routeName, $existing->id), $data);

        $response->assertStatus(302);
        $this->assertEquals(1, ($this->modelClass)::count());

        $updated = ($this->modelClass)::first();
        $response->assertRedirect($this->getActionRedirectUrl($existing));
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $updated->$key);
        }
    }


    /**
     * Test if model can be deleted via the frontend
     * @return void
     */
    public function testDeleteFrontend()
    {
        $existing = $this->factory()->create();
        $existingClone = clone $existing;
        $this->assertTrue($this->testUser->can('delete', $existing));

        $routeName = ($this->modelClass)::getSingleRouteName('web', 'destroy');
        $response = $this->actingAs($this->testUser, 'web')
            ->delete(route($routeName, $existing->id));

        $response->assertStatus(302)
            ->assertRedirect($this->getActionRedirectUrl($existingClone));
        $this->assertEquals(0, ($this->modelClass)::count());
    }

    /**
     * Get the expected redirect URL after an action
     * @return string
     */
    protected function getActionRedirectUrl($model = null): string {
        return ($this->modelClass)::getSingleRoute('web', 'index');
    }

    /**
     * @return Factory
     */
    protected function factory(): Factory
    {
        return ($this->modelClass)::factory();
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->assertNotEmpty($this->modelClass);
        $this->assertEquals(1, preg_match('/Tests\\\\Feature\\\\(.*)FeatureTest/', get_called_class(), $matches));
        $this->assertGreaterThan(1, count($matches));
        $this->modelName = $matches[1];
        $this->testUser = User::factory()->create();
    }


}
