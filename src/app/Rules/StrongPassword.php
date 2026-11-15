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
 */

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the password strength required for user-selected passwords.
 */
class StrongPassword implements ValidationRule
{
    /**
     * Validate length and the required character classes of a password.
     *
     * @param  string  $attribute  The validated attribute name.
     * @param  mixed  $value  The submitted password.
     * @param  Closure(string): void  $fail  Validation failure callback.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || strlen($value) < 8
            || ! preg_match('/[A-Z]/', $value)
            || ! preg_match('/[a-z]/', $value)
            || ! preg_match('/\d/', $value)
            || ! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('validation.strong_password')->translate();
        }
    }
}
