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

use App\Models\Liturgy\Song;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function test_index_returns_song_references()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.liturgy.song.index'));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    /**
     * @return void
     */
    public function test_index_requires_auth()
    {
        $response = $this->getJson(route('api.liturgy.song.index'));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_select_returns_flat_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.liturgy.song.select'));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    /**
     * @return void
     */
    public function test_songbooks_returns_songbook_map()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.liturgy.song.songbooks'));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    /**
     * @return void
     */
    public function test_store_creates_song()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.liturgy.song.store'), [
                'song' => [
                    'title' => 'Ein feste Burg',
                    'verses' => [],
                    'songbooks' => [],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('songs', ['title' => 'Ein feste Burg']);
    }

    public function test_update_modifies_song(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->create(['title' => 'Alt']);

        $response = $this->actingAs($user, 'api')
            ->patchJson(route('api.liturgy.song.update', $song), [
                'song' => [
                    'title' => 'Neu',
                    'verses' => [],
                    'songbooks' => [],
                ],
                'ref' => 1000000 + $song->id,
            ]);

        $response->assertOk();
        $this->assertSame('Neu', $song->fresh()->title);
    }

    /**
     * @return void
     */
    public function test_store_requires_title()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.liturgy.song.store'), ['song' => []]);

        $response->assertUnprocessable();
    }

    /**
     * @return void
     */
    public function test_single_returns_not_found_for_invalid_id()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.liturgy.song.single', ['songReferenceId' => 999999]));

        $response->assertNotFound();
    }
}
