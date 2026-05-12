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

use App\Models\Liturgy\Block;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\AbstractSimpleModelUnitTest;

class BlockUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Block::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = false;

    public function testBlockCanBeCreatedDirectly(): void
    {
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Test Block', 'service_id' => $service->id, 'sortable' => 1]);
        $this->assertCount(1, Block::all());
        $this->assertEquals('Test Block', Block::first()->title);
    }

    public function testBlockHasServiceRelationship(): void
    {
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Test Block', 'service_id' => $service->id, 'sortable' => 1]);
        $this->assertInstanceOf(BelongsTo::class, $block->service());
    }

    public function testBlockHasItemsRelationship(): void
    {
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Test Block', 'service_id' => $service->id, 'sortable' => 1]);
        $this->assertInstanceOf(HasMany::class, $block->items());
    }
}
