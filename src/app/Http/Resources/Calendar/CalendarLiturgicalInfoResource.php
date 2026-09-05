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
        return [
            'title' => $this->resource['title'] ?? '',
            'litColor' => $this->resource['litColor'] ?? '',
            'litTextsPerikope1' => $this->resource['litTextsPerikope1'] ?? '',
            'litTextsPerikope2' => $this->resource['litTextsPerikope2'] ?? '',
            'litTextsPerikope3' => $this->resource['litTextsPerikope3'] ?? '',
            'litTextsPerikope4' => $this->resource['litTextsPerikope4'] ?? '',
            'litTextsPerikope5' => $this->resource['litTextsPerikope5'] ?? '',
            'litTextsPerikope6' => $this->resource['litTextsPerikope6'] ?? '',
            'litTextsPerikope1Link' => $this->resource['litTextsPerikope1Link'] ?? '',
            'litTextsPerikope2Link' => $this->resource['litTextsPerikope2Link'] ?? '',
            'litTextsPerikope3Link' => $this->resource['litTextsPerikope3Link'] ?? '',
            'litTextsPerikope4Link' => $this->resource['litTextsPerikope4Link'] ?? '',
            'litTextsPerikope5Link' => $this->resource['litTextsPerikope5Link'] ?? '',
            'litTextsPerikope6Link' => $this->resource['litTextsPerikope6Link'] ?? '',
            'perikope' => $this->resource['perikope'] ?? '',
        ];
    }
}
