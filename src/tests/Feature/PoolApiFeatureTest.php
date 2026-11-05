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
    public function test_poolmasters_returns_day_map()
    {
        $pool = Pool::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.pool.poolmasters', ['pool' => $pool->id, 'date' => '2024-01']));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    /**
     * @return void
     */
    public function test_poolmasters_returns404_for_missing_pool()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.pool.poolmasters', ['pool' => 999999, 'date' => '2024-01']));
        $response->assertNotFound();
    }

    /**
     * @return void
     */
    public function test_mastered_returns_user_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.pools.mastered', ['user' => $user->id, 'date' => '2024-01-15']));

        $response->assertOk();
        $response->assertJsonStructure(['users', 'period']);
    }

    /**
     * @return void
     */
    public function test_mastered_returns404_for_missing_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.pools.mastered', ['user' => 999999, 'date' => '2024-01-15']));
        $response->assertNotFound();
    }

    /**
     * @return void
     */
    public function test_poolmasters_requires_auth()
    {
        $pool = Pool::factory()->create();

        $response = $this->getJson(route('api.pool.poolmasters', ['pool' => $pool->id, 'date' => '2024-01']));

        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_mastered_requires_auth()
    {
        $user = User::factory()->create();

        $response = $this->getJson(route('api.pools.mastered', ['user' => $user->id, 'date' => '2024-01-15']));

        $response->assertUnauthorized();
    }
}
