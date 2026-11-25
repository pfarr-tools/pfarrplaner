<?php

namespace App\Http\Resources\Calendar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarLiturgicalInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $resource */
        $resource = $this->resource;

        return [
            'title' => $resource['title'] ?? '',
            'litColor' => $resource['litColor'] ?? '',
            'litTextsPerikope1' => $resource['litTextsPerikope1'] ?? '',
            'litTextsPerikope2' => $resource['litTextsPerikope2'] ?? '',
            'litTextsPerikope3' => $resource['litTextsPerikope3'] ?? '',
            'litTextsPerikope4' => $resource['litTextsPerikope4'] ?? '',
            'litTextsPerikope5' => $resource['litTextsPerikope5'] ?? '',
            'litTextsPerikope6' => $resource['litTextsPerikope6'] ?? '',
            'litTextsPerikope1Link' => $resource['litTextsPerikope1Link'] ?? '',
            'litTextsPerikope2Link' => $resource['litTextsPerikope2Link'] ?? '',
            'litTextsPerikope3Link' => $resource['litTextsPerikope3Link'] ?? '',
            'litTextsPerikope4Link' => $resource['litTextsPerikope4Link'] ?? '',
            'litTextsPerikope5Link' => $resource['litTextsPerikope5Link'] ?? '',
            'litTextsPerikope6Link' => $resource['litTextsPerikope6Link'] ?? '',
            'perikope' => $resource['perikope'] ?? '',
        ];
    }
}
