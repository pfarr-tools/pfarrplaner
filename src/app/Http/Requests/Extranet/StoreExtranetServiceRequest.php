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

        $tokenCityIds = ExtranetTokenScope::allowedCityIds($this->user()?->currentAccessToken());
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

        if (! is_numeric($cityId)) {
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
        $targetCityId = is_numeric($cityInput) ? (int) $cityInput : null;

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

            $start = $this->parseToUtc((string) $this->input('date'), '00:00:00');
            $end = $this->parseToUtc((string) $this->input('end'), '23:59:59');
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
        $cityId = (int) $validated['city_id'];
        /** @var City $city */
        $city = City::query()->findOrFail($cityId);

        $hasSpecialLocation = ! empty($validated['special_location']);
        $location = null;
        if (! $hasSpecialLocation) {
            $location = isset($validated['location_id'])
                ? Location::query()->find((int) $validated['location_id'])
                : $city->locations()->first();
        }

        $isAllDay = ! empty($validated['is_allday']);
        $fallbackTime = isset($validated['time']) && is_string($validated['time']) && $validated['time'] !== ''
            ? $validated['time'].':00'
            : ($isAllDay ? '00:00:00' : ($location?->default_time ?: '10:00:00'));

        $utcDate = $this->parseToUtc((string) $validated['date'], $fallbackTime);
        if (isset($validated['time']) && is_string($validated['time']) && $validated['time'] !== ''
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) $validated['date'])) !== 1) {
            $berlinDate = $utcDate->copy()->setTimezone('Europe/Berlin')->format('Y-m-d');
            $utcDate = $this->parseToUtc($berlinDate, $validated['time'].':00');
        }

        $utcEnd = ! empty($validated['end']) && is_string($validated['end'])
            ? $this->parseToUtc($validated['end'], '23:59:59')
            : $utcDate->copy()->addHour();

        $attributes = $this->normalizeOptionalAttributes($validated);
        $attributes['city_id'] = $cityId;
        $attributes['event_class'] = isset($validated['event_class']) && is_string($validated['event_class'])
            ? $validated['event_class']
            : 'service';
        $attributes['location_id'] = $hasSpecialLocation ? null : $location?->id;
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
