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

namespace Tests\Unit;

use App\Models\Location;
use App\Http\Requests\ServiceRequest;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ServiceUnitTest
 * @package Tests\Unit
 */
class ServiceUnitTest extends TestCase
{
    use RefreshDatabase;


    /**
     * Test that a service can be created
     *
     * @return void
     */
    public function testServiceCanBeCreated()
    {
        Service::factory()->create();
        $this->assertCount(1, Service::all());
    }

    /**
     * Test that a service can be updated
     *
     * @return void
     */
    public function testServiceCanBeUpdated()
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
    public function testServiceCanBeDeleted()
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
    public function testCheckBoxesCanBeSetAndUnset()
    {
        $service = Service::factory()->raw(['need_predicant' => 1]);
        $rules = (new ServiceRequest())->rules();
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
    public function testServiceCanHaveTitle()
    {
        $data = Service::factory()->raw();
        $data['title'] = 'Cool title';
        $service = Service::create($data);
        $service->update(['slug' => $service->createSlug()]);
        $this->assertEquals('Cool title', Service::first()->title);
    }

    /**
     * @return void
     */
    public function testServiceHasCityRelationship(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $this->assertNotNull($service->city);
        $this->assertEquals($city->id, $service->city->id);
    }

    /**
     * @return void
     */
    public function testServiceHasParticipantsRelationship(): void
    {
        $service = Service::factory()->create();
        $this->assertNotNull($service->participants());
    }

    /**
     * @return void
     */
    public function testServiceHasCommentsRelationship(): void
    {
        $service = Service::factory()->create();
        $this->assertNotNull($service->comments());
    }

    /**
     * @return void
     */
    public function testScopeAtDateFiltersCorrectly(): void
    {
        $date = Carbon::today();
        $service = Service::factory()->create(['date' => $date]);
        Service::factory()->create(['date' => $date->copy()->addDay()]);
        $results = Service::atDate($date)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($service->id, $results->first()->id);
    }

    /**
     * @return void
     */
    public function testScopeInCitiesAndLocationsFiltersByCityAndLocation(): void
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

    /**
     * @return void
     */
    public function testScopeInCitiesAndLocationsBehavesLikeInCitiesWhenLocationsAreEmpty(): void
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
