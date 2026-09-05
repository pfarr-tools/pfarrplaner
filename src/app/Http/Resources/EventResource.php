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
        /** @var Service $this */
        return [
            'id' => $this->slug,
            'title' => $this->titleText(false),
            'body' => $this->description,
            'location' => $this->locationText(),
            'start' => $this->date,
            'end' => $this->date->copy()->addHour(1),
            'calendar_id' => $this->city_id,
            'state' => 'Busy',
            'isReadOnly' => !$this->isEditable,
        ];
    }
}
