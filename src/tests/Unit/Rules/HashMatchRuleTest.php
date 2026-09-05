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

use App\Rules\HashMatch;
use App\Rules\NotHashMatch;
use Closure;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HashMatchRuleTest extends TestCase
{
    private string $plain = 'secret123';
    private string $hash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hash = Hash::make($this->plain);
    }

    private function makeFail(bool &$failed): Closure
    {
        return function () use (&$failed) {
            $failed = true;
            return new class { public function translate(): string { return ''; } };
        };
    }

    private function runHashMatch(mixed $value): bool
    {
        $failed = false;
        (new HashMatch($this->hash))->validate('field', $value, $this->makeFail($failed));
        return !$failed;
    }

    private function runNotHashMatch(mixed $value): bool
    {
        $failed = false;
        (new NotHashMatch($this->hash))->validate('field', $value, $this->makeFail($failed));
        return !$failed;
    }

    public function testHashMatchPassesForMatchingValue()
    {
        $this->assertTrue($this->runHashMatch($this->plain));
    }

    public function testHashMatchFailsForWrongValue()
    {
        $this->assertFalse($this->runHashMatch('wrong'));
    }

    public function testNotHashMatchPassesForDifferentValue()
    {
        $this->assertTrue($this->runNotHashMatch('different'));
    }

    public function testNotHashMatchFailsForMatchingValue()
    {
        $this->assertFalse($this->runNotHashMatch($this->plain));
    }
}
