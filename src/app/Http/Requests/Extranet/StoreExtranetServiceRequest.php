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

use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Services\ExtranetTokenScope;
use Illuminate\Validation\Validator;

/**
 * Validates, authorizes, and normalizes Extranet service/event creation requests.
 */
class StoreExtranetServiceRequest extends AbstractExtranetServiceWriteRequest
{
    /**
     * Default city_id from a single-church Extranet token when omitted by the caller.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('city_id')) {
            return;
        }

        $user = $this->user();
        $tokenCityIds = ExtranetTokenScope::allowedCityIds(
            $user instanceof User ? $user->currentAccessToken() : null
        );
        if ($tokenCityIds !== null && count($tokenCityIds) === 1) {
            $this->merge(['city_id' => $tokenCityIds[0]]);
        }
    }

    /**
     * Authorize creating a service or event in the requested church.
     *
     * @return bool Whether creation is permitted for the authenticated user and token.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        $cityId = $this->input('city_id');
        if ($cityId === null || $cityId === '') {
            return ExtranetTokenScope::effectiveWritableCityIds($user, $user->currentAccessToken())->isNotEmpty();
        }

        if (! is_int($cityId) && ! (is_string($cityId) && is_numeric($cityId))) {
            return true;
        }

        return ExtranetTokenScope::canCreateInCity($user, $user->currentAccessToken(), (int) $cityId);
    }

    /**
     * Define validation rules for creating a church service or general event.
     *
     * @return array<string, list<mixed>> Validation rules.
     */
    public function rules(): array
    {
        $cityInput = $this->input('city_id');
        $targetCityId = is_int($cityInput) || (is_string($cityInput) && is_numeric($cityInput))
            ? (int) $cityInput
            : null;

        return array_merge(
            [
                'city_id' => ['required', 'integer', 'exists:cities,id'],
                'date' => ['required', 'date'],
            ],
            $this->sharedRules($targetCityId)
        );
    }

    /**
     * Ensure an explicit end timestamp is not earlier than the normalized start timestamp.
     *
     * @param  Validator  $validator  Active validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('end') || ! $this->filled('date')) {
                return;
            }

            $rawDate = $this->input('date');
            $rawEnd = $this->input('end');
            if (! is_string($rawDate) || ! is_string($rawEnd)) {
                return;
            }

            $start = $this->parseToUtc($rawDate, '00:00:00');
            $end = $this->parseToUtc($rawEnd, '23:59:59');
            if ($end->lt($start)) {
                $validator->errors()->add('end', 'Das Enddatum darf nicht vor dem Startdatum liegen.');
            }
        });
    }

    /**
     * Build the normalized UTC attribute payload ready for Service::create().
     *
     * @return array<string, mixed> Persistable Service model attributes.
     */
    public function serviceAttributes(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();
        $rawCityId = $validated['city_id'] ?? null;
        $cityId = is_int($rawCityId) || (is_string($rawCityId) && is_numeric($rawCityId))
            ? (int) $rawCityId
            : 0;
        $city = City::query()->findOrFail($cityId);

        $hasSpecialLocation = ! empty($validated['special_location']);
        $location = null;
        if (! $hasSpecialLocation) {
            $rawLocationId = $validated['location_id'] ?? null;
            if (is_int($rawLocationId) || (is_string($rawLocationId) && is_numeric($rawLocationId))) {
                $location = Location::query()->find((int) $rawLocationId);
            } else {
                $location = $city->locations()->first();
            }
        }

        $isAllDay = filter_var($validated['is_allday'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $rawTime = $validated['time'] ?? null;
        $timeInput = is_string($rawTime) && $rawTime !== '' ? $rawTime : null;
        $defaultLocationTime = $location instanceof Location
            && is_string($location->default_time)
            && $location->default_time !== ''
            ? $location->default_time
            : '10:00:00';
        $fallbackTime = $timeInput !== null
            ? $timeInput.':00'
            : ($isAllDay ? '00:00:00' : $defaultLocationTime);

        $rawDate = $validated['date'] ?? '';
        $dateInput = is_string($rawDate) ? $rawDate : '';
        $utcDate = $this->parseToUtc($dateInput, $fallbackTime);
        if ($timeInput !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($dateInput)) !== 1) {
            $berlinDate = $utcDate->copy()->setTimezone('Europe/Berlin')->format('Y-m-d');
            $utcDate = $this->parseToUtc($berlinDate, $timeInput.':00');
        }

        $rawEnd = $validated['end'] ?? null;
        $utcEnd = is_string($rawEnd) && $rawEnd !== ''
            ? $this->parseToUtc($rawEnd, '23:59:59')
            : $utcDate->copy()->addHour();

        $attributes = $this->normalizeOptionalAttributes($validated);
        $attributes['city_id'] = $cityId;
        $attributes['event_class'] = isset($validated['event_class']) && is_string($validated['event_class'])
            ? $validated['event_class']
            : 'service';
        $attributes['location_id'] = $hasSpecialLocation ? null : ($location instanceof Location ? $location->id : null);
        $attributes['date'] = $utcDate;
        $attributes['end'] = $utcEnd;
        $attributes['time'] = $utcDate->copy()->setTimezone('Europe/Berlin')->format('H:i:s');
        $attributes['meeting_url'] ??= '';

        if (! isset($attributes['konfiapp_event_type'])
            && $attributes['event_class'] === 'service'
            && is_numeric($city->konfiapp_default_type)) {
            $attributes['konfiapp_event_type'] = (int) $city->konfiapp_default_type;
        }

        return $attributes;
    }
}
