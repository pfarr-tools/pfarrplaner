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
use App\Models\Rites\Wedding;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeddingFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        foreach (range((int)date('Y') - 2, (int)date('Y') + 2) as $year) {
            Storage::put("liturgy/{$year}.json", '{}');
        }
        Storage::put('liturgy/.json', '{}');
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testEditorLoads(): void
    {
        $wedding = Wedding::factory()->create();
        $this->actingAs($this->user)
            ->get(route('weddings.edit', $wedding->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Rites/WeddingEditor'));
    }

    public function testCreateRedirectsToEditor(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->user)
            ->get(route('weddings.create', ['service' => $service->id]))
            ->assertRedirectContains('/weddings/');

        $this->assertCount(1, Wedding::all());
    }
}
