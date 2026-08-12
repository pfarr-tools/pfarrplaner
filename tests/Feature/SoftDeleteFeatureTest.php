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

use App\Models\Ads\AdConfig;
use App\Models\Attachment;
use App\Models\Calendar\Occurence;
use App\Models\Leave\Absence;
use App\Models\Leave\Replacement;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoftDeleteFeatureTest extends TestCase
{
    /**
     * @return void
     */
    public function testServiceSoftDeleteCascadesToRitesAndAdConfigsAndRestoreRebuildsOccurrences()
    {
        Storage::fake();

        $user = User::factory()->create();
        $service = Service::factory()->create();
        $baptism = Baptism::factory()->create(['service_id' => $service->id, 'city_id' => $service->city_id]);
        $funeral = Funeral::factory()->create(['service_id' => $service->id]);
        $wedding = Wedding::factory()->create(['service_id' => $service->id]);
        $adConfig = AdConfig::factory()->create(['service_id' => $service->id]);
        $attachmentPath = 'attachments/service-soft-delete-test.pdf';
        Storage::put($attachmentPath, 'attachment');
        $attachment = Attachment::create([
            'title' => 'Ablauf',
            'file' => $attachmentPath,
            'attachable_id' => $service->id,
            'attachable_type' => Service::class,
        ]);

        Occurence::create([
            'service_id' => $service->id,
            'start' => $service->date,
            'end' => $service->date->copy()->addHour(),
        ]);

        $this->actingAs($user);
        $service->delete();

        $this->assertSoftDeleted($service);
        $this->assertSoftDeleted($baptism);
        $this->assertSoftDeleted($funeral);
        $this->assertSoftDeleted($wedding);
        $this->assertSoftDeleted($adConfig);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_by' => $user->id]);
        $this->assertDatabaseHas('baptisms', ['id' => $baptism->id, 'deleted_by' => $user->id]);
        $this->assertDatabaseHas('funerals', ['id' => $funeral->id, 'deleted_by' => $user->id]);
        $this->assertDatabaseHas('weddings', ['id' => $wedding->id, 'deleted_by' => $user->id]);
        $this->assertDatabaseHas('ad_configs', ['id' => $adConfig->id, 'deleted_by' => $user->id]);
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id, 'attachable_id' => $service->id]);
        Storage::assertExists($attachmentPath);
        $this->assertDatabaseMissing('occurences', ['service_id' => $service->id]);

        $service->restore();

        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('baptisms', ['id' => $baptism->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('funerals', ['id' => $funeral->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('weddings', ['id' => $wedding->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('ad_configs', ['id' => $adConfig->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_by' => null]);
        $this->assertDatabaseHas('baptisms', ['id' => $baptism->id, 'deleted_by' => null]);
        $this->assertDatabaseHas('funerals', ['id' => $funeral->id, 'deleted_by' => null]);
        $this->assertDatabaseHas('weddings', ['id' => $wedding->id, 'deleted_by' => null]);
        $this->assertDatabaseHas('ad_configs', ['id' => $adConfig->id, 'deleted_by' => null]);
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id, 'attachable_id' => $service->id]);
        Storage::assertExists($attachmentPath);
        $this->assertGreaterThan(0, Occurence::where('service_id', $service->id)->count());
    }

    /**
     * @return void
     */
    public function testAbsenceSoftDeleteCascadesToReplacementAndRestoreKeepsAssignedUsers()
    {
        $actingUser = User::factory()->create();
        $absence = Absence::factory()->create();
        $replacement = Replacement::factory()->create(['absence_id' => $absence->id]);
        $user = User::factory()->create();
        $replacement->users()->attach($user->id);

        $this->actingAs($actingUser);
        $absence->delete();

        $this->assertSoftDeleted($absence);
        $this->assertSoftDeleted($replacement);
        $this->assertDatabaseHas('absences', ['id' => $absence->id, 'deleted_by' => $actingUser->id]);
        $this->assertDatabaseHas('replacements', ['id' => $replacement->id, 'deleted_by' => $actingUser->id]);
        $this->assertDatabaseHas('replacement_user', [
            'replacement_id' => $replacement->id,
            'user_id' => $user->id,
        ]);

        $absence->restore();

        $this->assertDatabaseHas('absences', ['id' => $absence->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('replacements', ['id' => $replacement->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('absences', ['id' => $absence->id, 'deleted_by' => null]);
        $this->assertDatabaseHas('replacements', ['id' => $replacement->id, 'deleted_by' => null]);
        $this->assertSame(
            1,
            Replacement::query()->findOrFail($replacement->id)->users()->count()
        );
    }

    /**
     * @return void
     */
    public function testServiceForceDeleteRemovesAttachmentsAndStoredFiles()
    {
        Storage::fake();

        $service = Service::factory()->create();
        $attachmentPath = 'attachments/service-force-delete-test.pdf';
        Storage::put($attachmentPath, 'attachment');
        $attachment = Attachment::create([
            'title' => 'Ablauf',
            'file' => $attachmentPath,
            'attachable_id' => $service->id,
            'attachable_type' => Service::class,
        ]);

        $service->delete();
        $service->forceDelete();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::assertMissing($attachmentPath);
    }
}
