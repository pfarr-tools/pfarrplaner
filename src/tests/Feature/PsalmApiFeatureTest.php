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

use App\Models\Liturgy\Psalm;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsalmApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testIndexReturnsPsalms()
    {
        $user = User::factory()->create();
        Psalm::factory()->count(3)->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.psalms.index'));

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    /**
     * @return void
     */
    public function testIndexRequiresAuth()
    {
        $response = $this->getJson(route('api.psalms.index'));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testStoreCreatesPsalm()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.psalms.store'), [
                'title' => 'Psalm 23',
                'text' => 'Der Herr ist mein Hirte',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Psalm 23']);
        $this->assertDatabaseHas('psalms', ['title' => 'Psalm 23']);
    }

    /**
     * @return void
     */
    public function testStoreRequiresTitle()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.psalms.store'), []);

        $response->assertUnprocessable();
    }

    /**
     * @return void
     */
    public function testUpdateModifiesPsalm()
    {
        $user = User::factory()->create();
        $psalm = Psalm::factory()->create(['title' => 'Alter Titel']);

        $response = $this->actingAs($user, 'api')
            ->patchJson(route('api.psalm.update', $psalm), [
                'title' => 'Neuer Titel',
            ]);

        $response->assertOk();
        $this->assertSame('Neuer Titel', $psalm->fresh()->title);
    }

    /**
     * @return void
     */
    public function testUpdateReturns404ForMissingPsalm()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->patchJson(route('api.psalm.update', 999999), ['title' => 'Test']);

        $response->assertNotFound();
    }
}
