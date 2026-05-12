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

use App\Models\Leave\Replacement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\AbstractSimpleModelUnitTest;

class ReplacementUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Replacement::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = true;

    public function testReplacementHasAbsenceRelationship(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $replacement->absence());
    }

    public function testReplacementCanBeCreatedViaFactory(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertCount(1, Replacement::all());
    }
}
