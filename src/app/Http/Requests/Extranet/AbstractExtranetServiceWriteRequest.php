<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace App\Http\Requests\Extranet;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation rules and UTC date normalization for Extranet service write requests.
 */
abstract class AbstractExtranetServiceWriteRequest extends FormRequest
{
    /**
     * Build common field validation rules shared between create and update requests.
     *
     * @param  int|null  $targetCityId  Target church ID used to constrain location_id.
     * @return array<string, list<mixed>> Field validation rules.
     */
    protected function sharedRules(?int $targetCityId): array
    {
        $locationRule = Rule::exists('locations', 'id');
        if ($targetCityId !== null) {
            $locationRule = $locationRule->where('city_id', $targetCityId);
        }

        return [
            'event_class' => ['nullable', 'string', Rule::in(['service', 'event'])],
            'location_id' => ['nullable', 'integer', $locationRule],
            'special_location' => ['nullable', 'string', 'max:255'],
            'time' => ['nullable', 'date_format:H:i'],
            'end' => ['nullable', 'date'],
            'is_allday' => ['nullable', 'boolean'],
            'rrule' => ['nullable', 'string', 'max:1000'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'internal_remarks' => ['nullable', 'string'],
            'announcements' => ['nullable', 'string'],
            'ad_text' => ['nullable', 'string'],
            'others' => ['nullable', 'string'],
            'need_predicant' => ['nullable', 'boolean'],
            'baptism' => ['nullable', 'boolean'],
            'eucharist' => ['nullable', 'boolean'],
            'cc' => ['nullable', 'boolean'],
            'cc_location' => ['nullable', 'string', 'max:255'],
            'cc_lesson' => ['nullable', 'string', 'max:255'],
            'cc_staff' => ['nullable', 'string', 'max:255'],
            'cc_alt_time' => ['nullable', 'date_format:H:i'],
            'cc_streaming_url' => ['nullable', 'string', 'max:2048'],
            'offering_goal' => ['nullable', 'string', 'max:255'],
            'offering_description' => ['nullable', 'string'],
            'offering_text' => ['nullable', 'string'],
            'offering_type' => ['nullable', 'string', Rule::in(['eO', 'PO', ''])],
            'offering_amount' => ['nullable', 'numeric'],
            'offerings_counter1' => ['nullable', 'string', 'max:255'],
            'offerings_counter2' => ['nullable', 'string', 'max:255'],
            'offerings_url' => ['nullable', 'string', 'max:2048'],
            'youtube_url' => ['nullable', 'string', 'max:2048'],
            'youtube_prefix_description' => ['nullable', 'string'],
            'youtube_postfix_description' => ['nullable', 'string'],
            'meeting_url' => ['nullable', 'string', 'max:2048'],
            'recording_url' => ['nullable', 'string', 'max:2048'],
            'external_url' => ['nullable', 'string', 'max:2048'],
            'sermon_id' => ['nullable', 'integer', 'exists:sermons,id'],
            'sermon_title' => ['nullable', 'string', 'max:255'],
            'sermon_reference' => ['nullable', 'string', 'max:255'],
            'sermon_description' => ['nullable', 'string'],
            'konfiapp_event_type' => ['nullable', 'integer'],
            'hidden' => ['nullable', 'boolean'],
            'needs_reservations' => ['nullable', 'boolean'],
            'registration_active' => ['nullable', 'boolean'],
            'registration_phone' => ['nullable', 'string', 'max:255'],
            'registration_online_start' => ['nullable', 'date'],
            'registration_online_end' => ['nullable', 'date'],
            'registration_max' => ['nullable', 'integer', 'min:0'],
            'exclude_sections' => ['nullable', 'string'],
            'exclude_places' => ['nullable', 'string'],
            'reserved_places' => ['nullable', 'string'],
            'wtc_category' => ['nullable', 'string', 'max:255'],
            'alt_proprium' => ['nullable', 'string', 'max:6'],
            'participants' => ['nullable', 'array'],
            'ministries' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'serviceGroups' => ['nullable', 'array'],
        ];
    }

    /**
     * Parse an incoming date/time string into UTC, interpreting naive values as Europe/Berlin.
     *
     * @param  string  $value  Raw date or datetime value.
     * @param  string|null  $fallbackBerlinTime  Default HH:MM:SS time used when value is YYYY-MM-DD.
     * @return Carbon Normalized UTC timestamp.
     */
    protected function parseToUtc(string $value, ?string $fallbackBerlinTime = null): Carbon
    {
        $trimmed = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) === 1 && $fallbackBerlinTime !== null) {
            return Carbon::parse($trimmed.' '.$fallbackBerlinTime, 'Europe/Berlin')->setTimezone('UTC');
        }

        $hasExplicitTimezone = preg_match('/(Z|[+-]\d{2}:?\d{2})$/i', $trimmed) === 1;
        if ($hasExplicitTimezone) {
            return Carbon::parse($trimmed)->setTimezone('UTC');
        }

        return Carbon::parse($trimmed, 'Europe/Berlin')->setTimezone('UTC');
    }

    /**
     * Normalize optional seating strings and online registration timestamps in validated data.
     *
     * @param  array<string, mixed>  $attributes  Validated model attributes.
     * @return array<string, mixed> Normalized model attributes.
     */
    protected function normalizeOptionalAttributes(array $attributes): array
    {
        foreach (['participants', 'ministries', 'tags', 'serviceGroups'] as $relationKey) {
            unset($attributes[$relationKey]);
        }

        foreach (['hidden', 'needs_reservations', 'registration_active', 'is_allday'] as $intFlag) {
            if (array_key_exists($intFlag, $attributes) && $attributes[$intFlag] !== null) {
                $attributes[$intFlag] = $attributes[$intFlag] ? 1 : 0;
            }
        }

        foreach (['exclude_places', 'reserved_places'] as $seatKey) {
            if (array_key_exists($seatKey, $attributes) && is_string($attributes[$seatKey])) {
                $attributes[$seatKey] = strtoupper($attributes[$seatKey]);
            }
        }

        foreach (['registration_online_start', 'registration_online_end'] as $dateKey) {
            if (! empty($attributes[$dateKey]) && is_string($attributes[$dateKey])) {
                $attributes[$dateKey] = $this->parseToUtc($attributes[$dateKey]);
            }
        }

        if (! empty($attributes['special_location'])) {
            $attributes['location_id'] = null;
        }

        if (array_key_exists('meeting_url', $attributes) && $attributes['meeting_url'] === null) {
            $attributes['meeting_url'] = '';
        }

        return $attributes;
    }
}
