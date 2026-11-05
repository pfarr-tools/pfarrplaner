<?php

namespace App\Http\Resources\HomeScreen;

use App\Http\Resources\Calendar\CalendarServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PredicantServicesTabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource;
        $data['services'] = CalendarServiceResource::collection($data['services']);
        $data['cities'] = $data['cities']->map(fn ($city) => [
            'id' => $city->id,
            'name' => $city->name,
        ]);

        return $data;
    }
}
