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
use App\Models\Rites\Baptism;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BaptismFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testEditorLoads(): void
    {
        $baptism = Baptism::factory()->create();
        $this->actingAs($this->user)
            ->get(route('baptisms.edit', $baptism->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Rites/BaptismEditor'));
    }

    public function testCreateRedirectsToEditor(): void
    {
        $this->actingAs($this->user)
            ->get(route('baptisms.create'))
            ->assertRedirectContains('/baptisms/');

        $this->assertCount(1, Baptism::all());
    }
}
