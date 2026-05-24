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
use Tests\TestCase;

class InboxApiFeatureTest extends TestCase
{
    /**
     * @return void
     */
    public function testIndexReturnsValidResponse()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.inbox.index'));

        $response->assertOk();
        // Returns false when inbox is not configured, or a file array when it is.
        $this->assertTrue($response->json() === false || is_array($response->json()));
    }

    /**
     * @return void
     */
    public function testIndexRequiresAuth()
    {
        $response = $this->getJson(route('api.inbox.index'));

        $response->assertUnauthorized();
    }
}
