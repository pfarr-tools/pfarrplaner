<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CalendarServiceIdCollectionResource extends ResourceCollection
{

    public $preserveKeys = true;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = [];
        foreach ($this->collection as $item) {
            $items[$item->city_id][] = $item;
        }
        return $items;
    }

}
