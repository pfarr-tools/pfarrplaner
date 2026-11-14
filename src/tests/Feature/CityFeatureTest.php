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

use App\Models\Places\City;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\AbstractModelFeatureTest;

class CityFeatureTest extends AbstractModelFeatureTest
{
    use RefreshDatabase;

    protected $modelClass = City::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /**
     * City editing exposes the signed offerings URL and its QR image URL.
     */
    public function test_city_editor_exposes_public_offerings_links(): void
    {
        $city = City::factory()->create();

        $this->actingAs($this->testUser)->get(route('admin.city.edit', ['modelId' => $city->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('offeringsUrl', URL::signedRoute('offerings.public', ['city' => $city]))
                ->where('offeringsQrUrl', route('offerings.qr', ['city' => $city])));
    }
}
