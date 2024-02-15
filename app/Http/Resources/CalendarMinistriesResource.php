<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarMinistriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];
        foreach ($this->participantsWithMinistry->groupBy('pivot.category') as $category => $participants) {
            $data[$category] = [];
            foreach ($participants as $participant) $data[$category][] = new CalendarParticipantResource($participant);
        }
        return $data;
    }
}
