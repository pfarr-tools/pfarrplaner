<?php

namespace App\Http\Resources\HomeScreen;

use App\Http\Resources\Calendar\CalendarServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NextServicesTabResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;
        $data['services'] = CalendarServiceResource::collection($data['services']);
        return $data;
    }
}
