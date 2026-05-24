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

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testKonfiAppTypesReturnsEmptyArrayWhenNotConfigured()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.city.konfiAppTypes', $city));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    /**
     * @return void
     */
    public function testKonfiAppTypesReturns404ForMissingCity()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.city.konfiAppTypes', 999999));

        $response->assertNotFound();
    }

    /**
     * @return void
     */
    public function testKonfiAppTypesRequiresAuth()
    {
        $city = City::factory()->create();

        $response = $this->getJson(route('api.city.konfiAppTypes', $city));

        $response->assertUnauthorized();
    }
}
