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

use App\Services\ColorService;
use Tests\TestCase;

class ColorServiceUnitTest extends TestCase
{
    public function testRainbowArrayReturnsColorsForEachKey(): void
    {
        $keys = ['a', 'b', 'c'];
        $result = ColorService::rainbowArray($keys);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function testRainbowArrayColorsAreValidHexStrings(): void
    {
        $keys = ['x', 'y'];
        $result = ColorService::rainbowArray($keys);
        foreach ($result as $color) {
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $color);
        }
    }

    public function testContrastColorReturnsWhiteForDarkBackground(): void
    {
        $result = ColorService::contrastColor('#000000');
        $this->assertEquals('#ffffff', strtolower($result));
    }

    public function testContrastColorReturnsBlackForLightBackground(): void
    {
        $result = ColorService::contrastColor('#ffffff');
        $this->assertEquals('#000000', strtolower($result));
    }
}
