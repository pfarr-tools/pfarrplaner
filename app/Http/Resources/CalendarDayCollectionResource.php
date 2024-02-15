<?php

namespace App\Http\Resources;

use App\Services\RedirectorService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CalendarDayCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = [];
        foreach ($this->collection as $item) {
            $items[$item] = new CalendarDayResource($item);
        }
        return ['data' => $items, 'returnRoute' => RedirectorService::backRoute()];
    }
}
