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

namespace Tests\Unit;

use App\Http\Requests\ServiceRequest;
use App\Models\Location;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ServiceUnitTest
 */
class ServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a service can be created
     *
     * @return void
     */
    public function test_service_can_be_created()
    {
        Service::factory()->create();
        $this->assertCount(1, Service::all());
    }

    /**
     * Test that a service can be updated
     *
     * @return void
     */
    public function test_service_can_be_updated()
    {
        $service = Service::factory()->create();
        $this->assertCount(1, Service::all());
        $service->update(['description' => 'cool title']);
        $this->assertEquals('cool title', Service::first()->description);
    }

    /**
     * Test that a service can be deleted
     *
     * @return void
     */
    public function test_service_can_be_deleted()
    {
        $service = Service::factory()->create();
        $this->assertCount(1, Service::all());
        $service->delete();
        $this->assertCount(0, Service::all());
    }

    /**
     * Test that checkbox fields can be set and unset
     *
     * @return void
     */
    public function test_check_boxes_can_be_set_and_unset()
    {
        $service = Service::factory()->raw(['need_predicant' => 1]);
        $rules = (new ServiceRequest)->rules();
        $validator = app('validator')->make($service, $rules);
        $this->assertTrue($validator->passes());
        $data = $validator->validate();
        $this->assertEquals(1, $data['need_predicant']);
        unset($service['need_predicant']);
        $validator = app('validator')->make($service, $rules);
        $data = $validator->validate();
        $this->assertArrayNotHasKey('need_predicant', $data);
    }

    /**
     * Test that a service can have a title
     *
     * @return void
     */
    public function test_service_can_have_title()
    {
        $data = Service::factory()->raw();
        $data['title'] = 'Cool title';
        $service = Service::create($data);
        $service->update(['slug' => $service->createSlug()]);
        $this->assertEquals('Cool title', Service::first()->title);
    }

    public function test_service_has_city_relationship(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $this->assertNotNull($service->city);
        $this->assertEquals($city->id, $service->city->id);
    }

    public function test_service_has_participants_relationship(): void
    {
        $service = Service::factory()->create();
        $this->assertNotNull($service->participants());
    }

    public function test_service_has_comments_relationship(): void
    {
        $service = Service::factory()->create();
        $this->assertNotNull($service->comments());
    }

    public function test_scope_at_date_filters_correctly(): void
    {
        $date = Carbon::today();
        $service = Service::factory()->create(['date' => $date]);
        Service::factory()->create(['date' => $date->copy()->addDay()]);
        $results = Service::atDate($date)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($service->id, $results->first()->id);
    }

    public function test_scope_in_cities_and_locations_filters_by_city_and_location(): void
    {
        $cityA = City::factory()->create();
        $cityB = City::factory()->create();
        $locationA1 = Location::factory()->create(['city_id' => $cityA->id]);
        $locationA2 = Location::factory()->create(['city_id' => $cityA->id]);
        $locationB1 = Location::factory()->create(['city_id' => $cityB->id]);

        $matching = Service::factory()->create([
            'city_id' => $cityA->id,
            'location_id' => $locationA1->id,
        ]);
        Service::factory()->create([
            'city_id' => $cityA->id,
            'location_id' => $locationA2->id,
        ]);
        Service::factory()->create([
            'city_id' => $cityB->id,
            'location_id' => $locationB1->id,
        ]);

        $results = Service::inCitiesAndLocations([$cityA->id, $cityB->id], [$locationA1->id])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($matching->id, $results->first()->id);
    }

    public function test_scope_in_cities_and_locations_behaves_like_in_cities_when_locations_are_empty(): void
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();
        $location1 = Location::factory()->create(['city_id' => $city->id]);
        $location2 = Location::factory()->create(['city_id' => $city->id]);
        $otherLocation = Location::factory()->create(['city_id' => $otherCity->id]);

        Service::factory()->create([
            'city_id' => $city->id,
            'location_id' => $location1->id,
        ]);
        Service::factory()->create([
            'city_id' => $city->id,
            'location_id' => $location2->id,
        ]);
        Service::factory()->create([
            'city_id' => $otherCity->id,
            'location_id' => $otherLocation->id,
        ]);

        $cityOnlyResults = Service::inCities([$city->id])->pluck('id')->sort()->values()->all();
        $cityAndEmptyLocationsResults = Service::inCitiesAndLocations([$city->id], [])->pluck('id')->sort()->values()->all();

        $this->assertEquals($cityOnlyResults, $cityAndEmptyLocationsResults);
    }
}
