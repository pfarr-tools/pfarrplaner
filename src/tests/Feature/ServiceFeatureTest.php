<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Class ServiceFeatureTest
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
    public function test_service_can_be_created()
    {
        Event::fake(['App\\Events\\ServiceRealtimeChanged']);

        Service::factory()->create(['city_id' => $this->city->id]);

        Event::assertDispatched('App\\Events\\ServiceRealtimeChanged');
    }

    /**
     * Test that a service can be updated
     *
     * @return void
     */
    public function test_service_can_be_updated()
    {
        Event::fake(['App\\Events\\ServiceRealtimeChanged']);

        $city = (City::class)::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $this->user->writableCities()->attach($city->id);
        $response = $this->actingAs($this->user)
            ->patch(
                route('service.update', $service->slug),
                ['description' => 'cool title']
            );
        $response->assertStatus(302);
        $this->assertEquals('cool title', $service->fresh()->description);
        Event::assertDispatched('App\\Events\\ServiceRealtimeChanged');
    }

    /**
     * Test that a service cannot be updated without write permissions for city
     *
     * @return void
     */
    public function test_service_cannot_be_updated_for_city_without_write_permissions()
    {
        $city = (City::class)::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $title = $service->description;
        $response = $this->actingAs($this->user)
            ->patch(
                route('service.update', $service->slug),
                ['description' => 'cool title']
            );
        $response->assertStatus(403);
        $this->assertEquals($title, $service->fresh()->description);
    }

    /**
     * Test that a service can be updated
     *
     * @return void
     */
    public function test_service_can_be_deleted()
    {
        Event::fake(['App\\Events\\ServiceRealtimeChanged']);

        $service = Service::factory()->create(['city_id' => $this->city->id]);
        $this->assertCount(1, Service::all());
        $response = $this->actingAs($this->user)
            ->delete(route('service.destroy', $service->slug));
        $response->assertStatus(302);
        $this->assertCount(0, Service::all());
        Event::assertDispatched('App\\Events\\ServiceRealtimeChanged');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fake');

        $this->withoutMiddleware(Authenticate::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'gd-bearbeiten']);
        Permission::firstOrCreate(['name' => 'gd-allgemein-bearbeiten']);

        $this->city = (City::class)::factory()->create();

        /** @var User */
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('gd-bearbeiten');
        $this->user->givePermissionTo('gd-allgemein-bearbeiten');
        $this->user->writableCities()->attach($this->city);
    }
}
