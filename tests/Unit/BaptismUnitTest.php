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

use App\Models\Rites\Baptism;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\AbstractSimpleModelUnitTest;

class BaptismUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Baptism::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testBaptismHasServiceRelationship(): void
    {
        $baptism = Baptism::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $baptism->service());
    }

    public function testBaptismCanBeCreatedViaFactory(): void
    {
        $baptism = Baptism::factory()->create();
        $this->assertCount(1, Baptism::all());
        $this->assertNotNull($baptism->service_id);
    }
}
