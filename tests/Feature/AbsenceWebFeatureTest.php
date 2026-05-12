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

use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AbsenceWebFeatureTest extends TestCase
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
            ->get(route('absences.index', ['year' => date('Y'), 'month' => date('m')]))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Absences/Planner'));
    }

    public function testEditorLoads(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->get(route('absence.edit', $absence->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Absences/AbsenceEditor'));
    }
}
