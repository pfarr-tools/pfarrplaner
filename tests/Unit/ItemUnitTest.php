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
use App\Models\Liturgy\Item;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemUnitTest extends TestCase
{
    use RefreshDatabase;

    private Block $block;

    protected function setUp(): void
    {
        parent::setUp();
        $service = Service::factory()->create();
        $this->block = Block::create([
            'service_id' => $service->id,
            'title' => 'Test-Block',
        ]);
    }

    public function testItemTableExists(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('liturgy_items'));
    }

    public function testItemCanBeCreated(): void
    {
        $item = Item::create([
            'liturgy_block_id' => $this->block->id,
            'title' => 'Test-Element',
            'data_type' => 'text',
            'sortable' => 1,
        ]);
        $this->assertNotNull($item->id);
    }

    public function testItemHasBlockRelationship(): void
    {
        $item = Item::create([
            'liturgy_block_id' => $this->block->id,
            'title' => 'Test-Element',
            'data_type' => 'text',
            'sortable' => 1,
        ]);
        $this->assertInstanceOf(Block::class, $item->block);
        $this->assertEquals($this->block->id, $item->block->id);
    }

    public function testDataAttributeRoundTrips(): void
    {
        $data = ['key' => 'value', 'num' => 42];
        $item = Item::create([
            'liturgy_block_id' => $this->block->id,
            'title' => 'Test-Element',
            'data_type' => 'text',
            'sortable' => 1,
        ]);
        $item->data = $data;
        $item->save();

        $fresh = Item::find($item->id);
        $this->assertEquals($data, $fresh->data);
    }

    public function testDataAttributeReturnsEmptyArrayWhenSerializedEmpty(): void
    {
        $item = Item::create([
            'liturgy_block_id' => $this->block->id,
            'title' => 'Test-Element',
            'data_type' => 'text',
            'serialized_data' => serialize([]),
            'sortable' => 1,
        ]);
        $fresh = Item::find($item->id);
        $this->assertIsArray($fresh->data);
        $this->assertEmpty($fresh->data);
    }
}
