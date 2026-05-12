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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AbsenceApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testSetCheckedUpdatesWorkflowStatus()
    {
        Mail::fake();

        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.absence.set-checked', $absence));

        $response->assertOk();
        $this->assertEquals(Absence::STATUS_CHECKED, $absence->fresh()->workflow_status);
    }

    /**
     * @return void
     */
    public function testSetCheckedRequiresAuth()
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson(route('api.absence.set-checked', $absence));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testSetApprovedUpdatesWorkflowStatus()
    {
        Mail::fake();

        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.absence.set-approved', $absence));

        $response->assertOk();
        $this->assertEquals(Absence::STATUS_APPROVED, $absence->fresh()->workflow_status);
    }

    /**
     * @return void
     */
    public function testSetApprovedRequiresAuth()
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson(route('api.absence.set-approved', $absence));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testDestroyDeletesAbsence()
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);
        $id = $absence->id;

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.absence.destroy', $absence));

        $response->assertOk();
        $this->assertNull(Absence::find($id));
    }

    /**
     * @return void
     */
    public function testDestroyRequiresAuth()
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson(route('api.absence.destroy', $absence));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testDestroyReturns404ForMissingAbsence()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.absence.destroy', 999999));

        $response->assertNotFound();
    }
}
