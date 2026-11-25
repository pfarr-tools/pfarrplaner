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
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and authorizes ministry participant assignments via the Extranet API.
 */
class AssignExtranetServiceRequest extends FormRequest
{
    /**
     * Authorize assigning participants on the bound service or event.
     *
     * @return bool Whether participant assignment is permitted.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $service = $this->route('service');
        if (! $user instanceof User || ! $service instanceof Service) {
            return false;
        }

        return ExtranetTokenScope::canManageService($user, $user->currentAccessToken(), $service, 'update');
    }

    /**
     * Define validation rules for ministry assignment payloads.
     *
     * @return array<string, list<string>> Validation rules.
     */
    public function rules(): array
    {
        return [
            'ministry' => ['required'],
            'users' => ['present', 'array'],
            'users.*' => ['required', 'integer', 'exists:users,id'],
            'exclusive' => ['required', 'boolean'],
            'no-toggle' => ['nullable', 'boolean'],
        ];
    }
}
