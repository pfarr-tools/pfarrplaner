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
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Liturgy\Block;
use App\Models\Liturgy\Item;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/** Home screen tab or API controller. */
class LiturgyController extends Controller
{
    /**
     *   construct.
     *
     * @return mixed
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum,api');
    }

    /**
     * Save the sort order of liturgy blocks and items for a service.
     */
    public function saveTreeState(Request $request, Service $service): JsonResponse
    {
        Gate::authorize('update', $service);
        $blockIds = $service->liturgyBlocks()->pluck('id');
        $blocks = $request->get('blocks');
        foreach (is_iterable($blocks) ? $blocks : [] as $block) {
            if (! is_array($block)) {
                continue;
            }
            $currentBlock = $service->liturgyBlocks()->whereKey($block['id'])->firstOrFail();
            $currentBlock->update(['sortable' => $block['sortable']]);
            $items = $block['items'] ?? [];
            foreach (is_iterable($items) ? $items : [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                Item::whereIn('liturgy_block_id', $blockIds)->whereKey($item['id'])->firstOrFail()->update(
                    [
                        'sortable' => $item['sortable'],
                        'liturgy_block_id' => $currentBlock->id,
                    ]
                );
            }
        }
        $service->refresh();
        $tree = $service->liturgyBlocks;

        return response()->json(compact('tree'));
    }

    /**
     * Import all liturgy blocks and items of a source service into a target service.
     */
    public function importToTree(Request $request, Service $service, Service $source): JsonResponse
    {
        Gate::authorize('update', $service);
        Gate::authorize('update', $source);
        $ct = 0;
        foreach ($service->liturgyBlocks as $block) {
            $block->update(['sortable' => ++$ct]);
        }

        foreach ($source->liturgyBlocks as $sourceBlock) {
            $ct++;
            unset($sourceBlock->id);
            $newBlock = $sourceBlock->replicate();
            $newBlock->sortable = $ct;
            $newBlock->service_id = $service->id;
            $newBlock->save();
            $itemCtr = 0;
            foreach ($sourceBlock->items as $sourceItem) {
                $itemCtr++;
                $newItem = $sourceItem->replicate();
                $newItem->liturgy_block_id = $newBlock->id;
                $newItem->sortable = $itemCtr;
                $newItem->save();
            }
        }
        $service->refresh();

        return response()->json(['tree' => $service->liturgyBlocks]);
    }

    /**
     * Store a new block
     *
     * @return JsonResponse
     */
    public function storeBlock(Request $request, Service $service)
    {
        Gate::authorize('update', $service);
        $data = $this->validateBlockRequest($request);
        $data['service_id'] = $service->id;
        $data['sortable'] = count($service->liturgyBlocks);
        $block = Block::create($data);

        return response()->json($block);
    }

    /**
     * Update a block
     *
     * @return JsonResponse
     */
    public function updateBlock(Request $request, Block $block)
    {
        Gate::authorize('update', $block->service);
        $block->update($this->validateBlockRequest($request));

        return response()->json($block);
    }

    /**
     * Delete a block
     *
     * @return JsonResponse
     */
    public function destroyBlock(Block $block)
    {
        Gate::authorize('update', $block->service);
        $block->delete();

        return response()->json();
    }

    /**
     * Store a new item
     *
     * @return JsonResponse
     */
    public function storeItem(Request $request, Block $block)
    {
        Gate::authorize('update', $block->service);
        $data = $this->validateItemRequest($request);
        $data['liturgy_block_id'] = $block->id;
        $data['sortable'] = count($block->items);
        if (! isset($data['responsible'])) {
            $data['responsible'] = [];
        }

        $item = Item::create($data);
        if (isset($data['data'])) {
            /** @var array<string, mixed> $itemData */
            $itemData = $data['data'];
            $item->data = $itemData;
            $item->performTypeSpecificActionsOnCreate();
            $item->save();
        }
        $item->refresh();

        return response()->json(
            [
                'item' => $item,
                'focusBlock' => $block->id,
                'focusItem' => $item->id,
            ]
        );
    }

    /**
     * Assign responsible people to item
     *
     * @return JsonResponse
     */
    public function assignToItem(Request $request, Item $item)
    {
        $block = $item->liturgyBlock;
        abort_unless($block !== null && $block->service !== null, 404);
        Gate::authorize('update', $block->service);
        $data = $item->data;
        $data['responsible'] = $request->all();
        unset($data['responsible']['api_token']);
        $item->data = $data;
        $item->save();

        return response()->json($item->data);
    }

    /**
     * Update an item
     *
     * @return JsonResponse
     */
    public function updateItem(Request $request, Item $item)
    {
        $block = $item->liturgyBlock;
        abort_unless($block !== null && $block->service !== null, 404);
        Gate::authorize('update', $block->service);
        $data = $this->validateItemRequest($request);
        $item->update($data);
        /** @var array<string, mixed> $itemData */
        $itemData = $data['data'];
        $item->data = $itemData;
        $item->save();
        $item->refresh();

        return response()->json(compact('item'));
    }

    /**
     * Delete a liturgy item
     */
    public function destroyItem(Item $item): JsonResponse
    {
        $block = $item->liturgyBlock;
        abort_unless($block !== null && $block->service !== null, 404);
        Gate::authorize('update', $block->service);
        $item->delete();

        return response()->json();
    }

    /**
     * Return all import sources for a service.
     *
     * @return JsonResponse
     */
    public function sources(int $serviceId)
    {
        $service = Service::findOrFail($serviceId);
        Gate::authorize('view', $service);
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $serviceGroups = [
            Service::setEagerLoads([])->with(['location'])
                ->select(['id', 'title', 'date', 'location_id'])
                ->whereHas('liturgyBlocks')
                ->userParticipates($user, 'P')
                ->get(),
            Service::setEagerLoads([])->with(['location'])
                ->select(['id', 'title', 'date', 'location_id'])
                ->whereHas('liturgyBlocks')
                ->writable()
                ->get(),
            Service::setEagerLoads([])->with([])
                ->select(['id', 'title', 'date', 'description', 'special_location'])
                ->isTemplate()
                ->get(),
        ];

        $sources = [];
        foreach ($serviceGroups as $services) {
            foreach ($services as $service) {
                if ($service->date?->format('Ymd') == '19780305') {
                    $sources[] = [
                        'id' => $service->id,
                        'date' => 0,
                        'name' => $service->title,
                        'description' => $service->description,
                        'source' => $service->special_location,
                        'group' => 'Vorlagen',
                    ];
                } else {
                    $sources[] = [
                        'id' => $service->id,
                        'date' => $service->date?->format('YmdHi'),
                        'name' => $service->date?->setTimezone('Europe/Berlin')->isoFormat('L, LT').' Uhr, '.$service->locationText(),
                        'group' => 'Gottesdienste',
                    ];
                }
            }
        }

        usort($sources, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return response()->json($sources);
    }

    /**
     * Validate the request data for creating or updating a liturgy item.
     *
     * @return array<string, mixed>
     */
    protected function validateItemRequest(Request $request): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validate(
            [
                'title' => 'required|string',
                'instructions' => 'nullable|string',
                'data_type' => 'required|string',
                'serialized_data' => 'nullable',
                'data' => 'nullable',
            ]
        );

        return $validated;
    }

    /**
     * Validate the request data for creating or updating a liturgy block.
     *
     * @return array<string, mixed>
     */
    protected function validateBlockRequest(Request $request): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validate(
            [
                'title' => 'required|string',
                'instructions' => 'nullable|string',
                'service_id' => 'int|exists:services,id',
            ]
        );

        return $validated;
    }
}
