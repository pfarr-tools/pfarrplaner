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
use App\Models\Seating\SeatingSection;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SeatingSectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected SeatingSection $seatingSection;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\People\User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->location = Location::factory()->create();
        $this->seatingSection = SeatingSection::factory()->create([
            'location_id' => $this->location->id,
        ]);
    }

    public function testEditorLoads(): void
    {
        $this->actingAs($this->user)
            ->get(route('seatingSection.edit', $this->seatingSection->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Admin/Location/SeatingSectionEditor'));
    }

    public function testCreateViaFrontend(): void
    {
        $data = SeatingSection::factory()->raw([
            'location_id' => $this->location->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('seatingSection.store'), $data)
            ->assertRedirect(route('admin.location.edit', ['modelId' => $this->location->id, 'tab' => 'seating']));

        $this->assertDatabaseHas('seating_sections', [
            'title' => $data['title'],
            'location_id' => $this->location->id,
        ]);
    }
}
