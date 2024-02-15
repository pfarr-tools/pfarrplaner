<?php

namespace App\Http\Resources;

use App\Models\Places\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarCityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var City $this */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'youtube_channel_url' => $this->youtube_channel_url,
            'default_ministries' => $this->defaultMinistries,
        ];
    }
}
