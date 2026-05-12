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

use App\Models\Liturgy\Songbook;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongbookApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testIndexReturnsSongbooks()
    {
        $user = User::factory()->create();
        Songbook::factory()->count(2)->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.songbooks.index'));

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    /**
     * @return void
     */
    public function testIndexRequiresAuth()
    {
        $response = $this->getJson(route('api.songbooks.index'));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testColorsReturnsColorList()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.songbooks.colors'));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    /**
     * @return void
     */
    public function testStoreCreatesSongbook()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.songbook.store'), [
                'name' => 'Evangelisches Gesangbuch',
                'code' => 'EG',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['code' => 'EG']);
        $this->assertDatabaseHas('songbooks', ['code' => 'EG']);
    }

    /**
     * @return void
     */
    public function testStoreRequiresNameAndCode()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.songbook.store'), []);

        $response->assertUnprocessable();
    }
}
