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

namespace Tests\Browser\Stage1;

use App\Models\Leave\Absence;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AbsencesPageLoadTest extends AbstractPageLoadTest
{
    protected Absence $absence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->absence = Absence::factory()->create(['user_id' => $this->superAdminUser->id]);
    }

    public function testAbsencePlannerLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('absences.index'));
        });
    }

    public function testAbsenceEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('absence.edit', $this->absence->id));
        });
    }
}
