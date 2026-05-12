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

class TabApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testTabRequiresAuth()
    {
        $response = $this->getJson(route('api.tab', ['tab' => '0']));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testTabCountRequiresAuth()
    {
        $response = $this->getJson(route('api.tab.count', ['tab' => '0']));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testTabCountReturnsStructuredResponse()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);
        // 'nextServices' maps to NextServicesHomeScreenTab which exists.
        $user->setSetting('homeScreenTabsConfig', ['tabs' => [['type' => 'nextServices', 'config' => ['mine' => 0]]]]);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.tab.count', ['tab' => '0']));

        $response->assertOk();
        $response->assertJsonStructure(['key', 'count']);
    }
}
