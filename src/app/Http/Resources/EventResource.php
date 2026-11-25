<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Service $service */
        $service = $this->resource;

        return [
            'id' => $service->slug,
            'title' => $service->titleText(false),
            'body' => $service->description,
            'location' => $service->locationText(),
            'start' => $service->date,
            'end' => $service->date?->copy()->addHour(),
            'calendar_id' => $service->city_id,
            'state' => 'Busy',
            'isReadOnly' => ! $service->isEditable,
        ];
    }
}
