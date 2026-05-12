<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Unit\Services;

use App\Services\PasswordService;
use Tests\TestCase;

class PasswordServiceUnitTest extends TestCase
{
    public function testRandomPasswordReturnsString(): void
    {
        $this->assertIsString(PasswordService::randomPassword());
    }

    public function testRandomPasswordHasMinimumLength(): void
    {
        // Pattern: consonant + vowel + consonant + vowel + 4 digits = 8 chars minimum
        $this->assertGreaterThanOrEqual(8, strlen(PasswordService::randomPassword()));
    }

    public function testRandomPasswordContainsDigits(): void
    {
        $password = PasswordService::randomPassword();
        $this->assertMatchesRegularExpression('/\d{4}$/', $password);
    }

    public function testRandomPasswordIsNonDeterministic(): void
    {
        $passwords = array_map(fn() => PasswordService::randomPassword(), range(1, 10));
        // At least 2 distinct values in 10 attempts
        $this->assertGreaterThan(1, count(array_unique($passwords)));
    }
}
