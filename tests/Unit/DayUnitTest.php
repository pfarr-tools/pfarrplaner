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

use App\Models\Calendar\Day;
use Tests\AbstractSimpleModelUnitTest;

class DayUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Day::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testDayCanBeCreatedViaFactory(): void
    {
        $day = Day::factory()->create();
        $this->assertCount(1, Day::all());
    }
}
