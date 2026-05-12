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

namespace Tests\Unit\Listeners;

use App\Events\AbsenceUpdated;
use App\Listeners\SendAbsenceWorkflowNotification;
use App\Models\Leave\Absence;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AbsenceListenerUnitTest extends TestCase
{
    use RefreshDatabase;

    public function testSendAbsenceWorkflowNotificationCanBeInstantiated(): void
    {
        $listener = new SendAbsenceWorkflowNotification();
        $this->assertInstanceOf(SendAbsenceWorkflowNotification::class, $listener);
    }

    public function testHandleDoesNothingWhenNoWorkflowDefined(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $absence = Absence::factory()->create(['user_id' => $user->id]);
        // No vacationAdmins or vacationApprovers → workflow not active

        $event = new AbsenceUpdated($absence);
        $listener = new SendAbsenceWorkflowNotification();
        $listener->handle($event);

        Mail::assertNothingSent();
    }

    public function testHandleSendsMailForNewAbsenceWithWorkflow(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $absence = Absence::factory()->create([
            'user_id' => $owner->id,
            'workflow_status' => Absence::STATUS_NEW,
        ]);
        // Assign an admin so the workflow is active (pivot needs relation column)
        $owner->vacationAdmins()->attach($admin->id, ['relation' => 'vacation_admin']);

        $event = new AbsenceUpdated($absence);
        $listener = new SendAbsenceWorkflowNotification();
        $listener->handle($event);

        Mail::assertSent(\App\Mail\Absence\AbsenceRequested::class);
    }
}
