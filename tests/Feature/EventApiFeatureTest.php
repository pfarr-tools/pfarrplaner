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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testByRangeReturns404ForMalformedCalendarKey()
    {
        $user = User::factory()->create();

        // Calendar keys must have the format 'type:id'; plain strings abort with 404.
        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.events.range', [
                'calendar' => 'unknown',
                'start'    => '2024-01-01',
                'end'      => '2024-01-31',
            ]));

        $response->assertNotFound();
    }

    /**
     * @return void
     */
    public function testByRangeRequiresAuth()
    {
        $response = $this->getJson(route('api.events.range', [
            'calendar' => 'unknown',
            'start'    => '2024-01-01',
            'end'      => '2024-01-31',
        ]));

        $response->assertUnauthorized();
    }
}
