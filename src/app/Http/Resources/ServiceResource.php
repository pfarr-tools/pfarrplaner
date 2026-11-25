<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'id' => $service->id,
            'slug' => $service->slug,
            'title' => $service->titleText(false),
            'body' => $service->description,
            'start' => $service->date,
            'end' => $service->date?->copy()->addHour(),
            'calendar_id' => $service->city_id,
            'state' => 'Busy',
            'location' => $service->location,
            'funerals' => $service->funerals,
            'weddings' => $service->weddings,
            'baptisms' => $service->baptisms,
            'cc' => $service->cc,
            'cc_location' => $service->cc_location,
            'cc_lesson' => $service->cc_lesson,
        ];
    }
}
