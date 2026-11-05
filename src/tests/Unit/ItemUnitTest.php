<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Unit;

use App\Models\Liturgy\Block;
use App\Models\Liturgy\Item;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    public function test_item_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('liturgy_items'));
    }

    public function test_item_can_be_created(): void
    {
        $item = Item::create([
            'liturgy_block_id' => $this->block->id,
            'title' => 'Test-Element',
            'data_type' => 'text',
            'sortable' => 1,
        ]);
        $this->assertNotNull($item->id);
    }

    public function test_item_has_block_relationship(): void
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

    public function test_data_attribute_round_trips(): void
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

    public function test_data_attribute_returns_empty_array_when_serialized_empty(): void
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

    public function test_recipients_resolve_custom_ministry_containing_colon(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Muster',
        ]);

        $service = $this->block->service;
        $service->participants()->attach($user->id, ['category' => 'Lektor:innen']);
        $service->refresh();

        $item = Item::create([
            'liturgy_block_id' => $this->block->id,
            'title' => 'Test-Element',
            'data_type' => 'text',
            'serialized_data' => serialize([
                'responsible' => ['ministry:Lektor:innen'],
            ]),
            'sortable' => 1,
        ]);

        $this->assertSame(['Alex Muster'], $item->recipients());
    }
}
