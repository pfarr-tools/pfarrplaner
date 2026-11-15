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

namespace App\Services;

use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Random\RandomException;

/** Central password-policy and temporary-credential service. */
class PasswordService
{
    private const VOWELS = 'aeiouy';

    private const CONSONANTS = 'bcdfghjklmnpqrstvwxz';

    private const NUMBERS = '0123456789';

    /**
     * Generate the initial password format used for administrator-created accounts.
     *
     * This password is only a temporary transport credential. The caller must
     * persist it with the forced-password-change flag enabled.
     *
     * @throws RandomException If secure random generation fails.
     */
    public static function temporaryInitialPassword(): string
    {
        return strtoupper(self::randomCharsFromString(self::CONSONANTS))
            .self::randomCharsFromString(self::VOWELS)
            .self::randomCharsFromString(self::CONSONANTS)
            .self::randomCharsFromString(self::VOWELS)
            .self::randomCharsFromString(self::NUMBERS, 4);

    }

    /**
     * Validate a user-selected password against the central password policy.
     *
     * @param  string  $password  Password to validate.
     *
     * @throws ValidationException When the password does not meet the policy.
     */
    public static function validate(string $password): void
    {
        Validator::make(
            ['password' => $password],
            ['password' => ['required', new StrongPassword]],
        )->validate();
    }

    /**
     * Select cryptographically secure random characters from a template.
     *
     * @param  string  $template  Characters available for selection.
     * @param  int  $number  Number of characters to select.
     */
    private static function randomCharsFromString(string $template, int $number = 1): string
    {
        $s = '';
        for ($i = 0; $i < $number; $i++) {
            $s .= $template[random_int(0, strlen($template) - 1)];
        }

        return $s;
    }
}
