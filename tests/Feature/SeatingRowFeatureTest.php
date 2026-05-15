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

use App\Models\Location;
use App\Models\People\User;
use App\Models\Seating\SeatingRow;
use App\Models\Seating\SeatingSection;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SeatingRowFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Location $location;
    protected SeatingSection $section;
    protected SeatingRow $row;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->location = Location::factory()->create();
        $this->section = SeatingSection::factory()->create([
            'location_id' => $this->location->id,
        ]);
        $this->row = SeatingRow::factory()->create([
            'seating_section_id' => $this->section->id,
        ]);
    }

    public function testEditorLoads(): void
    {
        $this->actingAs($this->user)
            ->get(route('seatingRow.edit', $this->row->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Location/SeatingRowEditor'));
    }

    public function testCreateViaFrontend(): void
    {
        $data = SeatingRow::factory()->raw([
            'seating_section_id' => $this->section->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('seatingRow.store'), $data)
            ->assertRedirect(route('admin.location.edit', ['modelId' => $this->location->id, 'tab' => 'seating']));

        $this->assertDatabaseHas('seating_rows', [
            'seating_section_id' => $this->section->id,
            'title' => str_pad((string) $data['title'], 2, '0', STR_PAD_LEFT),
        ]);
    }
}
