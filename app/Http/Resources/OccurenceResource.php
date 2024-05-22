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

namespace App\Http\Resources;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OccurenceResource extends JsonResource
{

    protected function getBackgroundColor() {
        if ($this->event->event_class != 'service') return 'lightgray';
        return '#yellow';
    }

    protected function getColor()
    {
        return 'red';
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Occurence $this */
        return [
            'id' => $this->event->slug,
            'title' => $this->event->titleText(false),
            'body' => $this->event->description ?? '',
            'location' => $this->event->locationText(),
            'start' => $this->start->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s'),
            'end' => $this->end->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s'),
            'calendar_id' => $this->event->city_id,
            'state' => 'Busy',
            'isReadOnly' => !$this->event->isEditable,
            'backgroundColor' => $this->getBackgroundColor(),
            'color' => $this->getColor(),
            'raw' => [
                'occurence_id' => $this->id,
                'isRecurring' => $this->event->rrule != '',
                'isFirstOccurence' => ($this->start == $this->event->date) && ($this->end == $this->event->end),
                'rrule' => $this->event->rrule,
                'event_id' => $this->event->id,
                'event_slug' => $this->event->slug,
                'event_class' => $this->event->event_class,
                'description' => $this->event->descriptionText(),
            ]
        ];
    }


}
