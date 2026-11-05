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

use App\Services\ColorService;
use Tests\TestCase;

class ColorServiceUnitTest extends TestCase
{
    public function test_rainbow_array_returns_colors_for_each_key(): void
    {
        $keys = ['a', 'b', 'c'];
        $result = ColorService::rainbowArray($keys);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function test_rainbow_array_colors_are_valid_hex_strings(): void
    {
        $keys = ['x', 'y'];
        $result = ColorService::rainbowArray($keys);
        foreach ($result as $color) {
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $color);
        }
    }

    public function test_contrast_color_returns_white_for_dark_background(): void
    {
        $result = ColorService::contrastColor('#000000');
        $this->assertEquals('#ffffff', strtolower($result));
    }

    public function test_contrast_color_returns_black_for_light_background(): void
    {
        $result = ColorService::contrastColor('#ffffff');
        $this->assertEquals('#000000', strtolower($result));
    }
}
