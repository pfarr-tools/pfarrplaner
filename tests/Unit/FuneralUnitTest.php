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

use App\Models\Rites\Funeral;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\AbstractSimpleModelUnitTest;

class FuneralUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Funeral::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = true;

    public function testFuneralHasServiceRelationship(): void
    {
        $funeral = Funeral::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $funeral->service());
    }

    public function testFuneralCanBeCreatedViaFactory(): void
    {
        $funeral = Funeral::factory()->create();
        $this->assertCount(1, Funeral::all());
        $this->assertNotNull($funeral->service_id);
    }
}
