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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Services;

class ImageService
{

    /**
     * Sample average RGB color in a region of a JPEG image
     * @param string $jpgPath
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * @param int $step
     * @return int[]
     */
    public static function sampleAverageRgb(string $jpgPath, int $x, int $y, int $w, int $h, int $step = 6): array
    {
        $im = imagecreatefromjpeg($jpgPath);
        if (!$im) {
            throw new RuntimeException("Cannot load jpg: $jpgPath");
        }

        $imgW = imagesx($im);
        $imgH = imagesy($im);

        // Clamp region to image bounds
        $x = max(0, min($x, $imgW - 1));
        $y = max(0, min($y, $imgH - 1));
        $w = max(1, min($w, $imgW - $x));
        $h = max(1, min($h, $imgH - $y));

        $rSum = $gSum = $bSum = 0;
        $count = 0;

        for ($py = $y; $py < $y + $h; $py += $step) {
            for ($px = $x; $px < $x + $w; $px += $step) {
                $rgb = imagecolorat($im, $px, $py);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $rSum += $r;
                $gSum += $g;
                $bSum += $b;
                $count++;
            }
        }

        imagedestroy($im);

        return [
            'r' => (int)round($rSum / max(1, $count)),
            'g' => (int)round($gSum / max(1, $count)),
            'b' => (int)round($bSum / max(1, $count)),
        ];
    }


    /**
     * Get the relative luminance of a color
     * @param int $r
     * @param int $g
     * @param int $b
     * @return float
     */
    protected static function relativeLuminance(int $r, int $g, int $b): float
    {
        $toLinear = function (int $c): float {
            $s = $c / 255;
            return ($s <= 0.04045) ? ($s / 12.92) : pow(($s + 0.055) / 1.055, 2.4);
        };

        $R = $toLinear($r);
        $G = $toLinear($g);
        $B = $toLinear($b);

        return 0.2126 * $R + 0.7152 * $G + 0.0722 * $B;
    }


    /***
     * Get a clamped value between 0 and 255
     * @param int $v
     * @return int
     */
    protected static function clamp(int $v): int
    {
        return max(0, min(255, $v));
    }

    /**
     * Get an ARGB color string for a tinted overlay on a background color
     * @param array $avgRgb
     * @param float $opacity
     * @return string
     */

    public static function tintedOverlayArgb(array $avgRgb, float $opacity = 0.75): string
    {
        $lum = static::relativeLuminance($avgRgb['r'], $avgRgb['g'], $avgRgb['b']);

        // If background is bright, make overlay darker; if background is dark, make overlay lighter
        $factor = ($lum > 0.5) ? 0.35 : 1.65;

        $r = static::clamp((int)round($avgRgb['r'] * $factor));
        $g = static::clamp((int)round($avgRgb['g'] * $factor));
        $b = static::clamp((int)round($avgRgb['b'] * $factor));

        $a = (int)round(max(0, min(1, $opacity)) * 255);
        return sprintf('%02X%02X%02X%02X', $a, $r, $g, $b); // AARRGGBB
    }


    /**
     * Decide whether black or white text has better contrast
     * against a semi-transparent ARGB overlay.
     *
     * @param string $argb AARRGGBB
     * @param array  $bgRgb ['r'=>0..255,'g'=>0..255,'b'=>0..255] average background
     * @return string 'black' or 'white'
     */
    public static function bestTextColorForOverlay(string $argb, array $bgRgb): string
    {
        // Parse ARGB
        $a = hexdec(substr($argb, 0, 2)) / 255;
        $r = hexdec(substr($argb, 2, 2));
        $g = hexdec(substr($argb, 4, 2));
        $b = hexdec(substr($argb, 6, 2));

        // Alpha blend overlay onto background
        $R = (int) round($r * $a + $bgRgb['r'] * (1 - $a));
        $G = (int) round($g * $a + $bgRgb['g'] * (1 - $a));
        $B = (int) round($b * $a + $bgRgb['b'] * (1 - $a));

        // Relative luminance (WCAG)
        $lum = static::relativeLuminance($R, $G, $B);

        // Threshold: >0.5 = bright background → black text
        return $lum > 0.5 ? 'black' : 'white';
    }

    /**
     * @param string $argb
     * @param float $threshold
     * @return string
     */
    public static function blackOrWhiteForArgb(string $argb, float $threshold = 0.82): string
    {
        $r = hexdec(substr($argb, 2, 2));
        $g = hexdec(substr($argb, 4, 2));
        $b = hexdec(substr($argb, 6, 2));

        $lum = static::relativeLuminance($r, $g, $b);
        return ($lum >= 0.82 ? 'FF000000' : 'FFFFFFFF');
    }

}
