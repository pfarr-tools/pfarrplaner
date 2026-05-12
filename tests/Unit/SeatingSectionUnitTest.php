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

namespace Tests\Unit;

use App\Models\Seating\SeatingSection;
use Tests\AbstractSimpleModelUnitTest;

class SeatingSectionUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = SeatingSection::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = true;

    public function testSeatingSectionCanBeCreatedViaFactory(): void
    {
        $section = SeatingSection::factory()->create();
        $this->assertCount(1, SeatingSection::all());
    }
}
