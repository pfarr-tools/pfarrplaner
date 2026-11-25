<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Resources\Calendar;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Transforms a service into the detailed calendar representation.
 */
class CalendarServiceResource extends JsonResource
{
    public bool $preserveKeys = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Service $service */
        $service = $this->resource;
        $gate = Gate::forUser($request->user('api') ?? $request->user() ?? Auth::user());
        $canUpdate = $gate->allows('update', $service);

        return [
            'id' => $service->id,
            'slug' => $service->slug,
            'title' => $service->titleText(false),
            'titleText' => $service->titleText(false),
            'date' => $service->date,
            'description' => $service->description,
            'descriptionText' => $service->descriptionText(),
            'internal_remarks' => $service->internal_remarks,
            'offering_goal' => $service->offeringGoal(),
            'offering_description' => $service->offeringDescription(),
            'locationText' => $service->locationText(),
            'locationTextWithCity' => $service->locationTextWithCity,
            'location' => new CalendarLocationResource($service->location),
            'funerals' => $service->funerals,
            'weddings' => $service->weddings,
            'baptisms' => $service->baptisms,
            'cc' => $service->cc,
            'cc_location' => $service->cc_location,
            'cc_lesson' => $service->cc_lesson,
            'cc_staff' => $service->cc_staff,
            'city' => new CalendarCityResource($service->city),
            'city_id' => $service->city_id,
            'timeText' => $service->timeText(),
            'liveDashboardUrl' => $service->liveDashboardUrl,
            'youtube_url' => $service->youtube_url,
            'isAlternateProprium' => $service->isAlternateProprium,
            'liturgicalInfo' => $service->liturgical_info,
            'hidden' => $service->hidden,
            'ministriesByCategory' => new CalendarMinistriesResource($service),
            'pastors' => CalendarParticipantResource::collection($service->pastors),
            'need_predicant' => $service->need_predicant,
            'organists' => CalendarParticipantResource::collection($service->organists),
            'sacristans' => CalendarParticipantResource::collection($service->sacristans),
            'isMine' => $service->isMine,
            'isEditable' => $service->isEditable,
            'canSelfAssign' => $gate->allows('selfAssign', $service),
            'selfAssignViaPredicantClaim' => ! $canUpdate
                && $gate->allows('claimPredicant', $service),
            'hasSermon' => $service->sermon_id !== null,
            'hasLiturgy' => $service->liturgyBlocks->count() > 0,
        ];
    }
}
