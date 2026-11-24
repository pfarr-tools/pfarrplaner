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

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Accept only categorical passkey diagnostics, never arbitrary browser error text. */
class PasskeyDiagnosticRequest extends FormRequest
{
    /**
     * Restrict diagnostics to the signed-in user; route middleware also blocks impersonation.
     *
     * @return bool Whether an authenticated session exists.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Bound every logged field to an enum, UUID or HTTP status.
     *
     * @return array<string, mixed> Validation rules excluding unrecognized payload fields.
     */
    public function rules(): array
    {
        return [
            'correlation_id' => ['required', 'uuid'],
            'phase' => ['required', Rule::in(['options', 'authenticator', 'store'])],
            'error' => ['required', Rule::in([
                'NotAllowedError', 'InvalidStateError', 'NotSupportedError', 'SecurityError',
                'ConstraintError', 'UnknownError', 'AbortError', 'TypeError', 'Other',
            ])],
            'browser' => ['required', Rule::in(['Chrome', 'Edge', 'Firefox', 'Safari', 'Other'])],
            'platform' => ['required', Rule::in(['Android', 'iOS', 'Windows', 'macOS', 'Linux', 'Other'])],
            'status' => ['present', 'nullable', 'integer', 'between:400,599'],
        ];
    }
}
