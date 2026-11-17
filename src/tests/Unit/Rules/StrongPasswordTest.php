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

namespace Tests\Unit\Rules;

use App\Rules\StrongPassword;
use App\Services\PasswordService;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StrongPasswordTest extends TestCase
{
    public function test_password_with_all_required_character_classes_is_valid(): void
    {
        $validator = Validator::make(
            ['password' => 'Secure1!'],
            ['password' => [new StrongPassword]],
        );

        $this->assertFalse($validator->fails());
    }

    /** The temporary initial-password format is opt-in and remains invalid for permanent passwords. */
    public function test_temporary_initial_password_is_only_allowed_when_explicitly_enabled(): void
    {
        $password = PasswordService::temporaryInitialPassword();
        $temporaryValidator = Validator::make(
            ['password' => $password],
            ['password' => [new StrongPassword(true)]],
        );
        $permanentValidator = Validator::make(
            ['password' => $password],
            ['password' => [new StrongPassword]],
        );

        $this->assertFalse($temporaryValidator->fails());
        $this->assertTrue($permanentValidator->fails());
    }

    #[DataProvider('invalidPasswordProvider')]
    public function test_password_missing_a_required_character_class_is_invalid(string $password): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => [new StrongPassword]],
        );

        $this->assertTrue($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPasswordProvider(): array
    {
        return [
            'too short' => ['Se1!'],
            'missing uppercase' => ['secure1!'],
            'missing lowercase' => ['SECURE1!'],
            'missing number' => ['Secure!!'],
            'missing symbol' => ['Secure12'],
        ];
    }
}
