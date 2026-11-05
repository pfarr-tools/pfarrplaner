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
    public function test_set_checked_updates_workflow_status()
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
    public function test_set_checked_requires_auth()
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson(route('api.absence.set-checked', $absence));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_set_approved_updates_workflow_status()
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
    public function test_set_approved_requires_auth()
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson(route('api.absence.set-approved', $absence));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_destroy_deletes_absence()
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
    public function test_destroy_requires_auth()
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson(route('api.absence.destroy', $absence));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_destroy_returns404_for_missing_absence()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.absence.destroy', 999999));

        $response->assertNotFound();
    }
}
