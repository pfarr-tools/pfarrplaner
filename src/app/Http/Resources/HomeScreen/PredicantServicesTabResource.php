<?php

namespace App\Http\Resources\HomeScreen;

use App\Http\Resources\Calendar\CalendarServiceResource;
use App\Models\Places\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PredicantServicesTabResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{services: mixed, cities: Collection<int, City>} $data */
        $data = $this->resource;
        $data['services'] = CalendarServiceResource::collection($data['services']);
        $data['cities'] = $data['cities']->map(fn ($city) => [
            'id' => $city->id,
            'name' => $city->name,
        ]);

        return $data;
    }
}
