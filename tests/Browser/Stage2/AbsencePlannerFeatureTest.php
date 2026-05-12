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

namespace Tests\Browser\Stage2;

use App\Models\Leave\Absence;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\AbsencePlannerPage;

class AbsencePlannerFeatureTest extends AbstractPageLoadTest
{
    protected Absence $absence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->absence = Absence::factory()->create([
            'user_id' => $this->superAdminUser->id,
        ]);
    }

    public function testAbsencePlannerRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new AbsencePlannerPage())
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testAbsenceEditorRendersReasonField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('absence.edit', $this->absence->id))
                    ->waitFor('[name="reason"]', 10)
                    ->assertPresent('[name="reason"]');
        });
    }

    public function testAbsenceEditorCanEditReason(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('absence.edit', $this->absence->id))
                    ->waitFor('[name="reason"]', 10)
                    ->clear('[name="reason"]')
                    ->type('[name="reason"]', 'Urlaub')
                    ->assertInputValue('[name="reason"]', 'Urlaub');
        });
    }

    public function testAbsenceEditorHasSaveButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('absence.edit', $this->absence->id))
                    ->waitFor('#app', 10)
                    ->assertPresent('button.btn-primary, .save-button');
        });
    }
}
