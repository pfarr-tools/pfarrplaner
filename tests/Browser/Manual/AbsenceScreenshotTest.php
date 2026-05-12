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

namespace Tests\Browser\Manual;

use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\Leave\Poolmaster;
use App\Models\Places\City;
use Laravel\Dusk\Browser;

class AbsenceScreenshotTest extends ManualScreenshotTestCase
{
    protected Absence $absence;
    protected Pool $pool;
    protected Poolmaster $poolmaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdminUser->update([
            'manage_absences' => 1,
            'needs_replacement' => 1,
            'show_vacations_with_services' => 1,
        ]);
        $city = City::factory()->create(['name' => 'Musterstadt']);
        $this->superAdminUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->superAdminUser->homeCities()->attach($city->id);

        $this->pool = Pool::factory()->create(['name' => 'Bestattungsvertretung']);
        $this->pool->cities()->attach($city->id);
        $this->pool->users()->attach($this->superAdminUser->id);
        $this->poolmaster = Poolmaster::factory()->create([
            'pool_id' => $this->pool->id,
            'user_id' => $this->superAdminUser->id,
            'start' => now()->startOfMonth(),
            'end' => now()->endOfMonth(),
        ]);

        $this->absence = Absence::factory()->create([
            'user_id' => $this->superAdminUser->id,
            'from' => now()->addDays(3)->startOfDay(),
            'to' => now()->addDays(9)->endOfDay(),
            'reason' => 'Urlaub',
        ]);
        Absence::factory()->count(2)->create(['user_id' => $this->superAdminUser->id]);
    }

    public function testCaptureAbsencePlanner(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('absences.index'),
                'urlaubsplan-uebersicht',
                800
            );
        });
    }

    public function testCaptureAbsenceEditorTabs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('absence.edit', $this->absence->id),
                'urlaubsplan-editor-abwesenheit',
                800
            );
            $this->captureTab($browser, 'replacement', 'urlaubsplan-editor-vertretung');
            $this->captureTab($browser, 'attachments', 'urlaubsplan-editor-dateien');
        });
    }

    public function testCapturePoolmasterEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('admin.poolmaster.edit', ['modelId' => $this->poolmaster->id]),
                'urlaubsplan-poolmaster-editor',
                800
            );
        });
    }

    public function testCapturePoolPublicPage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('pool.public', $this->pool->slug))
                    ->pause(800)
                    ->assertSourceHas('<body')
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');

            $this->captureCurrentManualScreenshot($browser, 'urlaubsplan-pool-oeffentlich', 800);
        });
    }
}
