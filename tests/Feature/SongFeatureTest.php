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

use App\Models\Liturgy\Song;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SongFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testIndexLoads(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.songs.index'))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Song/Index'));
    }

    public function testEditorLoads(): void
    {
        $song = Song::factory()->create();
        $this->actingAs($this->user)
            ->get(route('admin.song.edit', $song->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Song/SongEditor'));
    }

    public function testCreateSong(): void
    {
        $this->actingAs($this->user)
            ->post(route('admin.songs.store'), [
                'title' => 'Testlied',
                'verses' => [['number' => '1', 'text' => 'Testtext', 'refrain_before' => false, 'refrain_after' => false]],
                'songbooks' => [],
            ])
            ->assertRedirect(route('admin.songs.index'));
        $this->assertTrue(Song::where('title', 'Testlied')->exists());
    }
}
