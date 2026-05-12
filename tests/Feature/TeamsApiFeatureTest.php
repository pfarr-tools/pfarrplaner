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

use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamsApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testByCityReturnsTeamsForCity()
    {
        $city = City::factory()->create();
        Team::factory()->create(['city_id' => $city->id]);

        $response = $this->getJson(route('api.teams.byCity', $city));

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    /**
     * @return void
     */
    public function testByCityReturnsEmptyArrayForCityWithNoTeams()
    {
        $city = City::factory()->create();

        $response = $this->getJson(route('api.teams.byCity', $city));

        $response->assertOk();
        $this->assertCount(0, $response->json());
    }

    /**
     * @return void
     */
    public function testByCityReturns404ForMissingCity()
    {
        $response = $this->getJson(route('api.teams.byCity', 999999));
        $response->assertNotFound();
    }
}
