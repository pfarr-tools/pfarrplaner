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

use App\Models\Rites\Wedding;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\AbstractSimpleModelUnitTest;

class WeddingUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Wedding::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = true;

    public function testWeddingHasServiceRelationship(): void
    {
        $wedding = Wedding::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $wedding->service());
    }

    public function testWeddingCanBeCreatedViaFactory(): void
    {
        $wedding = Wedding::factory()->create();
        $this->assertCount(1, Wedding::all());
        $this->assertNotNull($wedding->service_id);
    }
}
