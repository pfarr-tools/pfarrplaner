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

namespace Tests\Unit\Services;

use App\Services\PasswordService;
use Tests\TestCase;

class PasswordServiceUnitTest extends TestCase
{
    /** The temporary credential has the documented string type. */
    public function test_temporary_initial_password_returns_string(): void
    {
        $this->assertIsString(PasswordService::temporaryInitialPassword());
    }

    /** The temporary credential has the documented eight-character length. */
    public function test_temporary_initial_password_has_minimum_length(): void
    {
        $this->assertSame(8, strlen(PasswordService::temporaryInitialPassword()));
    }

    /** The temporary credential ends with four digits. */
    public function test_temporary_initial_password_contains_digits(): void
    {
        $password = PasswordService::temporaryInitialPassword();
        $this->assertMatchesRegularExpression('/\d{4}$/', $password);
    }

    /** The temporary credential follows the consonant-vowel pattern. */
    public function test_temporary_initial_password_keeps_the_initial_credential_pattern(): void
    {
        $this->assertMatchesRegularExpression(
            '/^[A-Z][aeiouy][bcdfghjklmnpqrstvwxz][aeiouy]\d{4}$/',
            PasswordService::temporaryInitialPassword(),
        );
    }

    /** Repeated temporary credentials are not all identical. */
    public function test_temporary_initial_password_is_non_deterministic(): void
    {
        $passwords = array_map(fn () => PasswordService::temporaryInitialPassword(), range(1, 10));
        // At least 2 distinct values in 10 attempts
        $this->assertGreaterThan(1, count(array_unique($passwords)));
    }
}
