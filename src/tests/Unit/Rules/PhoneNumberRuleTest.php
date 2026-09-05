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

use App\Rules\PhoneNumber;
use Tests\TestCase;

class PhoneNumberRuleTest extends TestCase
{
    private function runRule(mixed $value): bool
    {
        $failed = false;
        $fail = function () use (&$failed) {
            $failed = true;
            return new class { public function translate(): string { return ''; } };
        };
        (new PhoneNumber())->validate('phone', $value, $fail);
        return !$failed;
    }

    public function testValidPhoneNumbers()
    {
        $this->assertTrue($this->runRule('+49 7433 12345'));
        $this->assertTrue($this->runRule('07433/12345'));
        $this->assertTrue($this->runRule('0800 000 0000'));
        $this->assertTrue($this->runRule('(07433) 12345'));
    }

    public function testInvalidPhoneNumbers()
    {
        $this->assertFalse($this->runRule('abc'));
        $this->assertFalse($this->runRule('123'));
        $this->assertFalse($this->runRule(''));
    }
}
