<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarServiceIdResource extends JsonResource
{

    public $preserveKeys = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Service $this */
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'city_id' => $this->city_id,
            'isEditable' => $this->isEditable,
        ];
    }
}
