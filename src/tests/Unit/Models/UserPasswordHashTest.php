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

namespace Tests\Unit\Models;

use App\Models\People\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordHashTest extends TestCase
{
    public function testAssigningAnExistingPasswordHashDoesNotHashItAgain(): void
    {
        $user = User::factory()->create();
        $hash = $user->getRawOriginal('password');

        $user->password = $hash;

        $this->assertSame($hash, $user->getAttributes()['password']);
    }

    public function testAssigningPlainTextPasswordAlwaysHashesIt(): void
    {
        $user = User::factory()->make();

        $user->password = 'Wyqi1234';

        $this->assertNotSame('Wyqi1234', $user->getAttributes()['password']);
        $this->assertTrue(Hash::check('Wyqi1234', $user->getAttributes()['password']));
    }
}
