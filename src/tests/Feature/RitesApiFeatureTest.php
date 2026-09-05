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

class RitesApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testQueryReturnsStructuredResults()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.rites.query', ['query' => 'test']));

        $response->assertOk();
        $response->assertJsonStructure(['baptisms', 'funerals', 'weddings']);
    }

    /**
     * @return void
     */
    public function testQueryRequiresAuth()
    {
        $response = $this->getJson(route('api.rites.query', ['query' => 'test']));
        $response->assertUnauthorized();
    }
}
