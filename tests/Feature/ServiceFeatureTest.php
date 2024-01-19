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

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Class ServiceFeatureTest
 * @package Tests\Feature
 */
class ServiceFeatureTest extends TestCase
{

    /** @var City */
    protected $city;

    /**
     * Test that a service can be created
     *
     * @return void
     */
    public function testServiceCanBeCreated()
    {
        // this test is currently empty, since service creation is done via update method
        $this->assertTrue(true);
    }

    /**
     * Test that a service can be updated
     *
     * @return void
     */
    public function testServiceCanBeUpdated()
    {
        $city = (City::class)::factory()->create();
        $service = Service::factory()->create(['city_id' => $city]);
        $this->user->writableCities()->attach($city->id);
        $response = $this->actingAs($this->user)
            ->patch(
                route('service.update', Service::first()->slug),
                ['description' => 'cool title']
            );
        $response->assertStatus(302);
        $this->assertEquals('cool title', Service::first()->description);
    }

    /**
     * Test that a service cannot be updated without write permissions for city
     *
     * @return void
     */
    public function testServiceCannotBeUpdatedForCityWithoutWritePermissions()
    {
        $city = (City::class)::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $title = $service->description;
        $response = $this->actingAs($this->user)
            ->patch(
                route('service.update', Service::first()->slug),
                ['description' => 'cool title']
            );
        $response->assertStatus(403);
        $this->assertEquals($title, Service::first()->description);
    }

    /**
     * Test that a service can be updated
     *
     * @return void
     */
    public function testServiceCanBeDeleted()
    {
        $service = Service::factory()->create(['city_id' => $this->city]);
        $this->assertCount(1, Service::all());
        $response = $this->actingAs($this->user)
            ->delete(route('service.destroy', $service->slug));
        $response->assertStatus(302);
        $this->assertCount(0, Service::all());
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fake');

        $this->withoutMiddleware(Authenticate::class);
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->registerPermissions();

        Permission::create(['name' => 'gd-bearbeiten']);
        Permission::create(['name' => 'gd-allgemein-bearbeiten']);

        $this->city = (City::class)::factory()->create();

        /** @var User */
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('gd-bearbeiten');
        $this->user->writableCities()->attach($this->city);
    }

}
