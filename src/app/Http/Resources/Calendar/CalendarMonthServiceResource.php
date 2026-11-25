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

use App\Models\People\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Transforms a service into the compact month-calendar representation.
 */
class CalendarMonthServiceResource extends JsonResource
{
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
            'city_id' => $service->city_id,
            'titleText' => $service->titleText(false),
            'date' => $service->date,
            'descriptionText' => $service->descriptionText(),
            'internal_remarks' => $service->internal_remarks,
            'locationText' => $service->locationText(),
            'locationTextWithCity' => $service->locationTextWithCity,
            'timeText' => $service->timeText(),
            'isSpecialTime' => $service->location && $service->location->default_time && $service->date
                ? $service->date->format('H:i') !== substr($service->location->default_time, 0, 5)
                : true,
            'isSpecialLocation' => $service->location === null,
            'funeral' => $service->funerals->count() > 0,
            'funeralSummary' => $service->funeralsText(),
            'baptismCount' => $service->baptisms->count(),
            'baptismSummary' => $service->baptismsText(true, true),
            'cc' => $service->cc,
            'cc_location' => $service->cc_location,
            'cc_lesson' => $service->cc_lesson,
            'cc_staff' => $service->cc_staff,
            'city' => new CalendarCityResource($service->city),
            'controlled_access' => $service->controlled_access,
            'youtube_url' => $service->youtube_url,
            'liveDashboardUrl' => $service->liveDashboardUrl,
            'isAlternateProprium' => $service->isAlternateProprium,
            'liturgicalInfo' => $service->isAlternateProprium ? $service->liturgicalInfo : [],
            'hidden' => $service->hidden,
            'participantText' => $service->participantText
                ?? self::buildCompactParticipantText($service),
            'need_predicant' => $service->need_predicant,
            'isMine' => $service->isMine,
            'isEditable' => $service->isEditable,
            'canSelfAssign' => $service->getAttribute('can_self_assign') ?? $gate->allows('selfAssign', $service),
            'selfAssignViaPredicantClaim' => $service->getAttribute('self_assign_via_predicant_claim')
                ?? (! $canUpdate && $gate->allows('claimPredicant', $service)),
        ];
    }

    /**
     * Build compact participant strings when the resource was loaded separately.
     *
     * @return array<string, string> Participant names grouped by ministry.
     */
    private static function buildCompactParticipantText(Service $service): array
    {
        $result = [];

        foreach ($service->participants->groupBy('pivot.category') as $category => $participants) {
            $result[$category] = $participants->map(function ($participant) {
                /** @var User $participant */
                return trim(implode(' ', array_filter([
                    $participant->title,
                    $participant->first_name,
                    $participant->last_name,
                ]))) ?: $participant->name;
            })->join(' | ');
        }

        return $result;
    }
}
