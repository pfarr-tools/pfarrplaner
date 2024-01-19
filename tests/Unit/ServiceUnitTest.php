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

use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use Tests\TestCase;

/**
 * Class ServiceUnitTest
 * @package Tests\Unit
 */
class ServiceUnitTest extends TestCase
{

    /**
     * Test that a service can be created
     *
     * @return void
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
     */
    public function testServiceCanHaveTitle()
    {
        $data = Service::factory()->raw();
        $data['title'] = 'Cool title';
        $service = Service::create($data);
        $service->update(['slug' => $service->createSlug()]);
        $this->assertEquals('Cool title', Service::first()->title);
    }


}
