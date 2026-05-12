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

use App\Rules\Zip;
use Tests\TestCase;

class ZipRuleTest extends TestCase
{
    private function runRule(mixed $value): bool
    {
        $failed = false;
        $fail = function () use (&$failed) {
            $failed = true;
            return new class { public function translate(): string { return ''; } };
        };
        (new Zip())->validate('zip', $value, $fail);
        return !$failed;
    }

    public function testValidZipCodes()
    {
        $this->assertTrue($this->runRule('72336'));
        $this->assertTrue($this->runRule('01234'));
        $this->assertTrue($this->runRule('99999'));
    }

    public function testInvalidZipCodes()
    {
        $this->assertFalse($this->runRule('1234'));
        $this->assertFalse($this->runRule('123456'));
        $this->assertFalse($this->runRule('00000'));
        $this->assertFalse($this->runRule('abcde'));
        $this->assertFalse($this->runRule(''));
    }
}
