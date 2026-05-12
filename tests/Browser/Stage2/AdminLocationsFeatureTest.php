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

use App\Models\Seating\SeatingRow;
use App\Models\Seating\SeatingSection;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AdminLocationsFeatureTest extends AbstractPageLoadTest
{
    protected SeatingSection $section;
    protected SeatingRow $row;

    protected function setUp(): void
    {
        parent::setUp();
        $this->section = SeatingSection::create([
            'title'         => 'Testbereich',
            'seating_model' => 'row',
            'sorting'       => 1,
        ]);
        $this->row = SeatingRow::create([
            'seating_section_id' => $this->section->id,
            'title'              => 'Testreihe',
            'seats'              => 10,
        ]);
    }

    public function testSeatingSectionEditorHasTitleField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('seatingSection.edit', $this->section->id))
                    ->waitFor('[name="title"]', 10)
                    ->assertInputValue('[name="title"]', 'Testbereich');
        });
    }

    public function testSeatingSectionTitleCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('seatingSection.edit', $this->section->id))
                    ->waitFor('[name="title"]', 10)
                    ->clear('[name="title"]')
                    ->type('[name="title"]', 'Geänderter Bereich')
                    ->assertInputValue('[name="title"]', 'Geänderter Bereich');
        });
    }

    public function testSeatingRowEditorHasTitleField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('seatingRow.edit', $this->row->id))
                    ->waitFor('[name="title"]', 10)
                    ->assertInputValue('[name="title"]', 'Testreihe');
        });
    }

    public function testSeatingRowEditorHasSeatsField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('seatingRow.edit', $this->row->id))
                    ->waitFor('[name="seats"]', 10)
                    ->assertPresent('[name="seats"]');
        });
    }
}
