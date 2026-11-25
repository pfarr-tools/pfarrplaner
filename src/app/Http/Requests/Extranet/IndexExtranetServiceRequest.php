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
use App\Services\ExtranetTokenScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates and authorizes Extranet service and event listing queries.
 */
class IndexExtranetServiceRequest extends FormRequest
{
    /**
     * Authorize listing services for the user's accessible churches and optional city filter.
     *
     * @return bool Whether the user may query the requested church scope.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        $visibleCityIds = ExtranetTokenScope::effectiveVisibleCityIds($user, $user->currentAccessToken());
        if ($visibleCityIds->isEmpty()) {
            return false;
        }

        $requestedCityId = $this->input('city_id');
        if ($requestedCityId !== null && $requestedCityId !== '' && is_numeric($requestedCityId)) {
            return $visibleCityIds->contains((int) $requestedCityId);
        }

        return true;
    }

    /**
     * Define validation rules for listing filters and pagination parameters.
     *
     * @return array<string, list<mixed>> Validation rules.
     */
    public function rules(): array
    {
        return [
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'event_class' => ['nullable', 'string', Rule::in(['service', 'event', 'all'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'include_hidden' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
