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

namespace App\Http\Requests;

use App\Models\People\User;
use App\Services\ExtranetTokenScope;
use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and authorizes personal or church-scoped Sanctum token creation.
 */
class StorePersonalTokenRequest extends FormRequest
{
    /**
     * Authorize personal token management and optional church-scoped key creation.
     *
     * @return bool Whether the current user may issue the requested token.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User || ! $user->can('update', $user)) {
            return false;
        }

        $cityId = $this->input('city_id');
        if ($cityId === null || $cityId === '') {
            return true;
        }

        if (! $user->can(RoleService::PERMISSION_CREATE_CHURCH_API_TOKENS)) {
            return false;
        }

        if (! is_numeric($cityId)) {
            return true;
        }

        return ExtranetTokenScope::directlyWritableCityIds($user)->contains((int) $cityId);
    }

    /**
     * Define validation rules for token creation payloads.
     *
     * @return array<string, list<string>> Validation rule definitions.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
