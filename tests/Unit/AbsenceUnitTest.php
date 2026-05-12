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

use App\Models\Leave\Absence;
use App\Models\People\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\AbstractSimpleModelUnitTest;

class AbsenceUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Absence::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testAbsenceStatusConstantsAreDefined(): void
    {
        $this->assertEquals(0, Absence::STATUS_NEW);
        $this->assertEquals(1, Absence::STATUS_CHECKED);
        $this->assertEquals(2, Absence::STATUS_APPROVED);
        $this->assertEquals(10, Absence::STATUS_SELF_ADMINISTERED);
        $this->assertEquals(11, Absence::STATUS_SELF_ADMINISTERED_AND_APPROVED);
    }

    public function testAbsenceHasUserRelationship(): void
    {
        $absence = Absence::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $absence->user());
    }

    public function testAbsenceHasReplacementsRelationship(): void
    {
        $absence = Absence::factory()->create();
        $this->assertInstanceOf(HasMany::class, $absence->replacements());
    }

    public function testAbsenceCanBeCreatedViaFactory(): void
    {
        $absence = Absence::factory()->create();
        $this->assertCount(1, Absence::all());
        $this->assertNotNull($absence->user_id);
    }
}
