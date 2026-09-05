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

namespace Tests\Feature;

use App\Models\Liturgy\Block;
use App\Models\Liturgy\Item;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiturgyApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testStoreBlockCreatesBlock()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.liturgy.block.store', $service), [
                'title' => 'Eröffnung',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Eröffnung']);
        $this->assertDatabaseHas('liturgy_blocks', ['title' => 'Eröffnung', 'service_id' => $service->id]);
    }

    /**
     * @return void
     */
    public function testStoreBlockRequiresTitle()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.liturgy.block.store', $service), []);

        $response->assertUnprocessable();
    }

    /**
     * @return void
     */
    public function testStoreBlockRequiresAuth()
    {
        $service = Service::factory()->create();

        $response = $this->postJson(route('api.liturgy.block.store', $service), ['title' => 'Test']);
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testUpdateBlockModifiesBlock()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Alt', 'service_id' => $service->id, 'sortable' => 1]);

        $response = $this->actingAs($user, 'api')
            ->patchJson(route('api.liturgy.block.update', $block), [
                'title' => 'Neu',
            ]);

        $response->assertOk();
        $this->assertSame('Neu', $block->fresh()->title);
    }

    /**
     * @return void
     */
    public function testDestroyBlockDeletesBlock()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Zu löschen', 'service_id' => $service->id, 'sortable' => 1]);
        $id = $block->id;

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.liturgy.block.destroy', $block));

        $response->assertOk();
        $this->assertNull(Block::find($id));
    }

    /**
     * @return void
     */
    public function testStoreItemCreatesItem()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Block', 'service_id' => $service->id, 'sortable' => 1]);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.liturgy.item.store', $block), [
                'title' => 'Lied',
                'data_type' => 'song',
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['item', 'focusBlock', 'focusItem']);
        $this->assertDatabaseHas('liturgy_items', ['title' => 'Lied', 'liturgy_block_id' => $block->id]);
    }

    /**
     * @return void
     */
    public function testStoreItemRequiresAuth()
    {
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Block', 'service_id' => $service->id, 'sortable' => 1]);

        $response = $this->postJson(route('api.liturgy.item.store', $block), ['title' => 'Test', 'data_type' => 'song']);
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testDestroyItemDeletesItem()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Block', 'service_id' => $service->id, 'sortable' => 1]);
        $item = Item::create([
            'title' => 'Item',
            'liturgy_block_id' => $block->id,
            'sortable' => 1,
            'data_type' => 'song',
        ]);
        $id = $item->id;

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.liturgy.item.destroy', $item));

        $response->assertOk();
        $this->assertNull(Item::find($id));
    }

    /**
     * @return void
     */
    public function testSaveTreeStateUpdatesBlockOrder()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();
        $block = Block::create(['title' => 'Block', 'service_id' => $service->id, 'sortable' => 5]);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.liturgy.tree.save', $service), [
                'blocks' => [
                    ['id' => $block->id, 'sortable' => 1, 'items' => []],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['tree']);
        $this->assertSame(1, $block->fresh()->sortable);
    }

    /**
     * @return void
     */
    public function testSourcesReturnsImportableSourcesForServiceIdRouteParameter()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.liturgy.sources', ['serviceId' => $service->id]));

        $response->assertOk();
        $response->assertJsonIsArray();
    }
}
