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

use App\Http\Controllers\PapierkorbController;
use App\Models\Attachment;
use App\Models\Leave\Absence;
use App\Models\Leave\Replacement;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PapierkorbFeatureTest extends TestCase
{
    /**
     * @return void
     */
    public function testTrashModuleIsAvailableForAccessibleSoftDeletedRecords()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $user->givePermissionTo('gd-allgemein-bearbeiten');
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $service = Service::factory()->create(['city_id' => $city->id]);
        $service->delete();

        $this->actingAs($user);

        $config = PapierkorbController::getAdminModuleConfig();

        $this->assertIsArray($config);
        $this->assertSame('Papierkorb', $config['text']);
        $this->assertSame(route('admin.trash.index'), $config['url']);
    }

    /**
     * @return void
     */
    public function testTrashRestoreForBaptismAlsoRestoresDeletedParentService()
    {
        $this->withoutMiddleware();

        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $user->givePermissionTo('gd-allgemein-bearbeiten');
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $service = Service::factory()->create(['city_id' => $city->id]);
        $baptism = Baptism::factory()->create(['service_id' => $service->id, 'city_id' => $city->id]);

        $this->actingAs($user);
        $service->delete();

        $this->assertSoftDeleted($service);
        $this->assertSoftDeleted($baptism);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_by' => $user->id]);
        $this->assertDatabaseHas('baptisms', ['id' => $baptism->id, 'deleted_by' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->patch(route('admin.trash.restore', [
                'type' => 'baptism',
                'id' => $baptism->id,
            ]));

        $response->assertRedirect(route('admin.trash.index'));
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('baptisms', ['id' => $baptism->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_by' => null]);
        $this->assertDatabaseHas('baptisms', ['id' => $baptism->id, 'deleted_by' => null]);
    }

    /**
     * @return void
     */
    public function testTrashForceDeleteRemovesAbsenceAndReplacementPermanently()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);
        $replacement = Replacement::factory()->create(['absence_id' => $absence->id]);

        $absence->delete();

        $this->assertSoftDeleted($absence);
        $this->assertSoftDeleted($replacement);

        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->delete(route('admin.trash.destroy', [
                'type' => 'absence',
                'id' => $absence->id,
            ]));

        $response->assertRedirect(route('admin.trash.index'));
        $this->assertDatabaseMissing('absences', ['id' => $absence->id]);
        $this->assertDatabaseMissing('replacements', ['id' => $replacement->id]);
    }

    /**
     * @return void
     */
    public function testTrashUiReceivesRetentionPolicy()
    {
        $this->withoutMiddleware();

        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('admin.trash.index'));

        $response->assertOk();
        $response->assertSee('"retentionDays":' . PapierkorbController::RETENTION_DAYS, false);
    }

    /**
     * @return void
     */
    public function testOldTrashEntriesArePrunedAfterRetentionPeriod()
    {
        Storage::fake();
        Carbon::setTestNow('2026-08-12 12:00:00');

        $oldService = Service::factory()->create();
        $recentService = Service::factory()->create();

        $oldAttachmentPath = 'attachments/old-trash-service.pdf';
        Storage::put($oldAttachmentPath, 'old');
        Attachment::create([
            'title' => 'Alt',
            'file' => $oldAttachmentPath,
            'attachable_id' => $oldService->id,
            'attachable_type' => Service::class,
        ]);

        $oldService->delete();
        $recentService->delete();

        $oldService->newQueryWithoutScopes()->whereKey($oldService->id)->update([
            'deleted_at' => Carbon::now()->subDays(PapierkorbController::RETENTION_DAYS + 1),
        ]);
        $recentService->newQueryWithoutScopes()->whereKey($recentService->id)->update([
            'deleted_at' => Carbon::now()->subDays(PapierkorbController::RETENTION_DAYS - 1),
        ]);

        $this->artisan('trash:prune')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('services', ['id' => $oldService->id]);
        $this->assertDatabaseHas('services', ['id' => $recentService->id]);
        Storage::assertMissing($oldAttachmentPath);

        Carbon::setTestNow();
    }
}
