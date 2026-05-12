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
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SongbookFeatureTest extends TestCase
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
            ->get(route('songbooks.index'))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Songbook/Index'));
    }

    public function testEditorLoads(): void
    {
        $songbook = Songbook::factory()->create();
        $this->actingAs($this->user)
            ->get(route('songbook.edit', $songbook->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Songbook/SongbookEditor'));
    }

    public function testCreateSongbook(): void
    {
        $this->actingAs($this->user)
            ->post(route('songbook.store'), ['name' => 'Testgesangbuch', 'code' => 'TG'])
            ->assertStatus(302);
        $this->assertTrue(Songbook::where('code', 'TG')->exists());
    }
}
