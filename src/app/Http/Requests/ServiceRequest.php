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

namespace App\Http\Requests;

use App\Models\Location;
use App\Models\People\User;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class ServiceRequest
 */
class ServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $actor = $this->user();
        if (! $actor instanceof User) {
            return false;
        }

        if ($service = $this->route('service')) {
            return $actor->can('update', $service);
        } else {
            return $actor->can('create', Service::class);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'day_id' => 'nullable|integer|exists:days,id',
            'location_id' => 'nullable',
            'time' => 'nullable|date_format:"H:i"',
            'description' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'special_location' => 'nullable|string',
            'need_predicant' => 'nullable|bool',
            'baptism' => 'nullable|bool',
            'eucharist' => 'nullable|bool',
            'offerings_counter1' => 'nullable|string',
            'offerings_counter2' => 'nullable|string',
            'offering_goal' => 'nullable|string',
            'offering_description' => 'nullable|string',
            'offering_amount' => 'nullable',
            'offering_type' => [
                Rule::in(['eO', 'PO', '']),
            ],
            'others' => 'nullable|string',
            'cc' => 'nullable|bool',
            'cc_location' => 'nullable|string',
            'cc_lesson' => 'nullable|string',
            'cc_staff' => 'nullable|string',
            'cc_alt_time' => 'nullable|date_format:"H:i"',
            'internal_remarks' => 'nullable|string',
            'title' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'cc_streaming_url' => 'nullable|string',
            'offerings_url' => 'nullable|string',
            'meeting_url' => 'nullable|string',
            'recording_url' => 'nullable|string',
            'external_url' => 'nullable|string',
            'sermon_title' => 'nullable|string',
            'sermon_reference' => 'nullable|string',
            'sermon_description' => 'nullable|string',
            'konfiapp_event_type' => 'nullable|int',
            'hidden' => 'nullable|int|in:0,1',
            'needs_reservations' => 'nullable|int|in:0,1',
            'exclude_sections' => 'nullable|string',
            'registration_active' => 'nullable|int|in:0,1',
            'exclude_places' => 'nullable|string',
            'registration_phone' => 'nullable|string',
            'registration_online_start' => 'nullable',
            'registration_online_end' => 'nullable',
            'registration_max' => 'nullable|int',
            'reserved_places' => 'nullable|string',
            'youtube_prefix_description' => 'nullable|string',
            'youtube_postfix_description' => 'nullable|string',
            'sermon_id' => 'nullable|int|exists:sermons,id',
            'announcements' => 'nullable|string',
            'offering_text' => 'nullable|string',
            'controlled_access' => 'nullable|int',
            'alt_liturgy_date' => 'nullable|date_format:d.m.Y',
            'date' => 'nullable|date',
            'wtc_category' => 'nullable|string',
            'liturgy_info_id' => 'nullable|int|exists:liturgy_info,id',
            'event_class' => 'nullable|string|in:service,event',
            'end' => 'nullable|date',
            'rrule' => 'nullable|string',
            'is_allday' => 'nullable|int|in:0,1',
            'alt_proprium' => 'nullable|string|max:6',
            'ad_text' => 'nullable|string',
        ];
    }

    /**
     * Return validated data with sensible defaults set
     *
     * @param  string|null  $key  Optional validated value key.
     * @param  mixed  $default  Fallback value kept for FormRequest compatibility.
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        if (isset($data['location_id']) || isset($data['special_location'])) {
            // set location
            if ($data['special_location'] ?? false) {
                $data['location_id'] = null;
            } else {
                $location = Location::query()->whereKey($data['location_id'])->first();
                if ($location === null) {
                    $data['special_location'] = $data['location_id'];
                    $data['location_id'] = null;
                }
            }

            // set time and place
            if ($data['special_location'] = ($data['special_location'] ?? '')) {
                $data['location_id'] = 0;
                $data['time'] = $data['time'] ?: '';
                $data['cc_location'] = $data['cc_location'] ?: '';
            } elseif (isset($data['location_id'])) {
                /** @var int|string $locationId */
                $locationId = $data['location_id'] ?: 0;
                if ($locationId) {
                    $location = Location::query()->whereKey($locationId)->first();
                    if ($location === null) {
                        $data['special_location'] = (string) $locationId;
                        $data['location_id'] = null;
                        $data['time'] = $data['time'] ?: '';
                        $data['cc_location'] = $data['cc_location'] ?: '';

                        return $data;
                    }
                    $data['location_id'] = $locationId;
                    $data['time'] = $data['time'] ?: $location->default_time;
                    $data['cc_location'] = $data['cc_location'] ?? ($data['cc'] ? $location->cc_default_location : '');
                } else {
                    $data['time'] = $data['time'] ?: '';
                    $data['cc_location'] = $data['cc_location'] ?: '';
                }
            }
        }

        $data['hidden'] = $data['hidden'] ?? 0;
        $data['needs_reservations'] = $data['needs_reservations'] ?? 0;
        $data['registration_active'] = $data['registration_active'] ?? 0;

        /** @var string $excludePlaces */
        $excludePlaces = $data['exclude_places'] ?? '';
        $data['exclude_places'] = strtoupper($excludePlaces);
        /** @var string $reservedPlaces */
        $reservedPlaces = $data['reserved_places'] ?? '';
        $data['reserved_places'] = strtoupper($reservedPlaces);

        if (isset($data['registration_online_start'])) {
            $data['registration_online_start'] = $this->getDateTime($data['registration_online_start']);
        }
        if (isset($data['registration_online_end'])) {
            $data['registration_online_end'] = $this->getDateTime($data['registration_online_end']);
        }

        if (isset($data['date'])) {
            /** @var string $date */
            $date = $data['date'];
            $data['time'] = Carbon::parse($date)->setTimezone('Europe/Berlin')->format('H:i:s');
        }

        if (($data['event_class'] ?? null) == 'service') {
            /** @var string $date */
            $date = $data['date'];
            $data['end'] = Carbon::parse($date)->addHour();
        }

        if (($data['location_id'] ?? null) === 0) {
            $data['location_id'] = null;
        }

        return $data;
    }

    /**
     * Parse a request date value given in Berlin local time as UTC.
     */
    protected function getDateTime(mixed $data): Carbon
    {
        /** @var string|int|float|\DateTimeInterface|null $data */
        return Carbon::parse($data, 'Europe/Berlin')->setTimezone('UTC');
    }
}
