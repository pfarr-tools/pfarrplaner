<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarServiceResource extends JsonResource
{
    public $preserveKeys = true;


    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Service $this */
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->titleText(false),
            'description' => $this->description,
            'descriptionText' => $this->descriptionText(),
            'internal_remarks' => $this->internal_remarks,
            'locationText' => $this->locationText(),
            'location' => new CalendarLocationResource($this->location),
            'funerals' => $this->funerals,
            'weddings' => $this->weddings,
            'baptisms' => $this->baptisms,
            'cc' => $this->cc,
            'cc_location' => $this->cc_location,
            'cc_lesson' => $this->cc_lesson,
            'cc_staff' => $this->cc_staff,
            'city' => new CalendarCityResource($this->city),
            'city_id' => $this->city_id,
            'timeText' => $this->timeText(),
            'liveDashboardUrl' => $this->liveDashboardUrl,
            'isAlternateProprium' => $this->isAlternateProprium,
            'liturgicalInfo' => $this->liturgicalInfo ? [
                'title' => $this->liturgicalInfo['title'],
                'litColor' => $this->liturgicalInfo['litColor'],
            ] : [],
            'hidden' => $this->hidden,
            'ministriesByCategory' => new CalendarMinistriesResource($this),
            'pastors' => CalendarParticipantResource::collection($this->pastors),
            'organists' => CalendarParticipantResource::collection($this->organists),
            'sacristans' => CalendarParticipantResource::collection($this->sacristans),
            'isMine' => $this->isMine,
            'isEditable' => $this->isEditable,
        ];
    }
}
