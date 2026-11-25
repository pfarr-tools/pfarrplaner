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

use App\Models\People\User;
use App\Models\Service;
use App\Services\ExtranetTokenScope;
use Illuminate\Validation\Validator;

/**
 * Validates, authorizes, and normalizes partial Extranet service/event updates.
 */
class UpdateExtranetServiceRequest extends AbstractExtranetServiceWriteRequest
{
    /**
     * Authorize updating the target service and optional target church reassignment.
     *
     * @return bool Whether the current request may update the bound service.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $service = $this->route('service');
        if (! $user instanceof User || ! $service instanceof Service) {
            return false;
        }

        $token = $user->currentAccessToken();
        if (! ExtranetTokenScope::canManageService($user, $token, $service, 'update')) {
            return false;
        }

        $newCityId = $this->input('city_id');
        if ($newCityId !== null && $newCityId !== '' && is_numeric($newCityId)
            && (int) $newCityId !== (int) $service->city_id) {
            return ExtranetTokenScope::canCreateInCity($user, $token, (int) $newCityId);
        }

        return true;
    }

    /**
     * Define validation rules for partial service or event updates.
     *
     * @return array<string, list<mixed>> Validation rules.
     */
    public function rules(): array
    {
        /** @var Service|null $service */
        $service = $this->route('service');
        $cityInput = $this->input('city_id');
        $targetCityId = is_numeric($cityInput)
            ? (int) $cityInput
            : ($service?->city_id !== null ? (int) $service->city_id : null);

        return array_merge(
            [
                'city_id' => ['sometimes', 'required', 'integer', 'exists:cities,id'],
                'date' => ['sometimes', 'required', 'date'],
            ],
            $this->sharedRules($targetCityId)
        );
    }

    /**
     * Validate chronological consistency between updated start and end timestamps.
     *
     * @param  Validator  $validator  Active validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('end')) {
                return;
            }

            /** @var Service $service */
            $service = $this->route('service');
            $effectiveStart = $this->filled('date')
                ? $this->parseToUtc((string) $this->input('date'), '00:00:00')
                : $service->date->copy()->setTimezone('UTC');
            $end = $this->parseToUtc((string) $this->input('end'), '23:59:59');

            if ($end->lt($effectiveStart)) {
                $validator->errors()->add('end', 'Das Enddatum darf nicht vor dem Startdatum liegen.');
            }
        });
    }

    /**
     * Build the normalized partial attribute array without clobbering omitted fields.
     *
     * @param  Service  $service  Existing service or event being updated.
     * @return array<string, mixed> Normalized attributes for $service->fill().
     */
    public function serviceAttributes(Service $service): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();
        $attributes = $this->normalizeOptionalAttributes($validated);
        $durationMinutes = max(1, (int) ($service->duration ?: 60));

        if (isset($validated['date']) && is_string($validated['date'])) {
            $existingBerlinTime = $service->date->copy()->setTimezone('Europe/Berlin')->format('H:i:s');
            $fallbackTime = isset($validated['time']) && is_string($validated['time']) && $validated['time'] !== ''
                ? $validated['time'].':00'
                : $existingBerlinTime;
            $utcDate = $this->parseToUtc($validated['date'], $fallbackTime);
            $attributes['date'] = $utcDate;
            $attributes['time'] = $utcDate->copy()->setTimezone('Europe/Berlin')->format('H:i:s');

            if (! array_key_exists('end', $validated)) {
                $attributes['end'] = $utcDate->copy()->addMinutes($durationMinutes);
            }
        } elseif (isset($validated['time']) && is_string($validated['time']) && $validated['time'] !== '') {
            $berlinDate = $service->date->copy()->setTimezone('Europe/Berlin')->format('Y-m-d');
            $utcDate = $this->parseToUtc($berlinDate, $validated['time'].':00');
            $attributes['date'] = $utcDate;
            $attributes['time'] = $validated['time'].':00';

            if (! array_key_exists('end', $validated)) {
                $attributes['end'] = $utcDate->copy()->addMinutes($durationMinutes);
            }
        }

        if (array_key_exists('end', $validated) && is_string($validated['end']) && $validated['end'] !== '') {
            $attributes['end'] = $this->parseToUtc($validated['end'], '23:59:59');
        }

        return $attributes;
    }
}
