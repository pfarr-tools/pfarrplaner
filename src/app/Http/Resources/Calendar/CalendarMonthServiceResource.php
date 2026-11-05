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

class CalendarMonthServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Service $this */
        $service = $this;
        $gate = Gate::forUser($request->user('api') ?? $request->user() ?? Auth::user());
        $canUpdate = $gate->allows('update', $service);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'city_id' => $this->city_id,
            'titleText' => $this->titleText(false),
            'date' => $this->date,
            'descriptionText' => $this->descriptionText(),
            'internal_remarks' => $this->internal_remarks,
            'locationText' => $this->locationText(),
            'locationTextWithCity' => $this->locationTextWithCity,
            'timeText' => $this->timeText(),
            'isSpecialTime' => $this->location && $this->location->default_time
                ? $this->date->format('H:i') !== substr($this->location->default_time, 0, 5)
                : true,
            'isSpecialLocation' => $this->location === null,
            'funeral' => $this->funerals->count() > 0,
            'funeralSummary' => $this->funeralsText(),
            'baptismCount' => $this->baptisms->count(),
            'baptismSummary' => $this->baptismsText(true, true),
            'cc' => $this->cc,
            'cc_location' => $this->cc_location,
            'cc_lesson' => $this->cc_lesson,
            'cc_staff' => $this->cc_staff,
            'city' => new CalendarCityResource($this->city),
            'controlled_access' => $this->controlled_access,
            'youtube_url' => $this->youtube_url,
            'liveDashboardUrl' => $this->liveDashboardUrl,
            'isAlternateProprium' => $this->isAlternateProprium,
            'liturgicalInfo' => $this->isAlternateProprium ? $this->liturgicalInfo : [],
            'hidden' => $this->hidden,
            'participantText' => $this->resource->participantText ?? [],
            'need_predicant' => $this->need_predicant,
            'isMine' => $this->isMine,
            'isEditable' => $this->isEditable,
            'canSelfAssign' => $service->getAttribute('can_self_assign') ?? $gate->allows('selfAssign', $service),
            'selfAssignViaPredicantClaim' => $service->getAttribute('self_assign_via_predicant_claim')
                ?? (! $canUpdate && $gate->allows('claimPredicant', $service)),
        ];
    }
}
