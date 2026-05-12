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

use App\Models\Leave\Pool;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testPoolmastersReturnsDayMap()
    {
        $pool = Pool::factory()->create();

        $response = $this->getJson(route('api.pool.poolmasters', ['pool' => $pool->id, 'date' => '2024-01']));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    /**
     * @return void
     */
    public function testPoolmastersReturns404ForMissingPool()
    {
        $response = $this->getJson(route('api.pool.poolmasters', ['pool' => 999999, 'date' => '2024-01']));
        $response->assertNotFound();
    }

    /**
     * @return void
     */
    public function testMasteredReturnsUserData()
    {
        $user = User::factory()->create();

        $response = $this->getJson(route('api.pools.mastered', ['user' => $user->id, 'date' => '2024-01-15']));

        $response->assertOk();
        $response->assertJsonStructure(['users', 'period']);
    }

    /**
     * @return void
     */
    public function testMasteredReturns404ForMissingUser()
    {
        $response = $this->getJson(route('api.pools.mastered', ['user' => 999999, 'date' => '2024-01-15']));
        $response->assertNotFound();
    }
}
