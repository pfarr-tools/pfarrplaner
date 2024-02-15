<?php

namespace App\Http\Resources;

use App\Models\Leave\Absence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarAbsenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Absence $this */
        return [
            'id' => $this->id,
            'name' => $this->user->last_name ?? $this->user->name,
            'label' => $this->user->name.': '.$this->reason.$this->replacementText(', V: '),
        ];
    }
}
