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

use App\Models\Seating\SeatingRow;
use App\Models\Seating\SeatingSection;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AdminLocationsPageLoadTest extends AbstractPageLoadTest
{
    protected SeatingSection $section;
    protected SeatingRow $row;

    protected function setUp(): void
    {
        parent::setUp();
        $this->section = SeatingSection::create([
            'title' => 'Testbereich',
            'seating_model' => 'row',
            'sorting' => 1,
        ]);
        $this->row = SeatingRow::create([
            'seating_section_id' => $this->section->id,
            'title' => 'Testreihe',
            'seats' => 10,
        ]);
    }

    public function testSeatingSectionEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('seatingSection.edit', $this->section->id));
        });
    }

    public function testSeatingRowEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('seatingRow.edit', $this->row->id));
        });
    }
}
