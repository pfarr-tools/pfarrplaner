<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
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
            'titleText' => $this->titleText(false),
            'date' => $this->date,
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
            'youtube_url' => $this->youtube_url,
            'isAlternateProprium' => $this->isAlternateProprium,
            'liturgicalInfo' => new CalendarLiturgicalInfoResource($this->liturgicalInfo),
            'hidden' => $this->hidden,
            'ministriesByCategory' => new CalendarMinistriesResource($this),
            'pastors' => CalendarParticipantResource::collection($this->pastors),
            'need_predicant' => $this->need_predicant,
            'organists' => CalendarParticipantResource::collection($this->organists),
            'sacristans' => CalendarParticipantResource::collection($this->sacristans),
            'isMine' => $this->isMine,
            'isEditable' => $this->isEditable,
        ];
    }
}
