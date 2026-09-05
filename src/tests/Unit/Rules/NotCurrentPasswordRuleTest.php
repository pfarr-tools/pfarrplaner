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

namespace Tests\Unit\Rules;

use App\Models\People\User;
use App\Rules\NotCurrentPassword;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class NotCurrentPasswordRuleTest extends TestCase
{
    private function runRule(mixed $value): bool
    {
        $failed = false;
        $fail = function () use (&$failed) {
            $failed = true;
            return new class { public function translate(): string { return ''; } };
        };
        (new NotCurrentPassword())->validate('password', $value, $fail);
        return !$failed;
    }

    public function testPassesForNewPassword()
    {
        // Plain text — User model mutator hashes it, so Hash::check('oldpassword', ...) works
        $user = User::factory()->create(['password' => 'oldpassword']);
        Auth::login($user);

        $this->assertTrue($this->runRule('newpassword'));
    }

    public function testFailsForCurrentPassword()
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        Auth::login($user);

        $this->assertFalse($this->runRule('oldpassword'));
    }
}
