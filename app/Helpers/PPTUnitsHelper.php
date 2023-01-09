<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Shared\Drawing;

class PPTUnitsHelper
{
    const UNIT_EMU = 'emu';
    const UNIT_CENTIMETER = 'cm';
    const UNIT_INCH = 'in';
    const UNIT_MILLIMETER = 'mm';
    const UNIT_PIXEL = 'px';
    const UNIT_POINT = 'pt';


    /**
     * Convert specified value from one to another units
     *
     * @param mixed  $value     value at fromUnit units
     * @param string $fromUnit units of value (from units)
     * @param string $toUnit   result units (to units)
     *
     * @return mixed value value at toUnit units
     */
    public static function convert(
        $value,
        $fromUnit = self::UNIT_MILLIMETER,
        $toUnit = self::UNIT_EMU
    ) {
        if ($fromUnit === $toUnit) {
            return $value;
        }

        // Convert from $fromUnit to EMU
        switch ($fromUnit) {
            case self::UNIT_MILLIMETER:
                $value *= 36000;
                break;
            case self::UNIT_CENTIMETER:
                $value *= 360000;
                break;
            case self::UNIT_INCH:
                $value *= 914400;
                break;
            case self::UNIT_PIXEL:
                $value = Drawing::pixelsToEmu($value);
                break;
            case self::UNIT_POINT:
                $value *= 12700;
                break;
            case self::UNIT_EMU:
            default:
                // no changes
        }

        // Convert from EMU to $toUnit
        switch ($toUnit) {
            case self::UNIT_MILLIMETER:
                $value /= 36000;
                break;
            case self::UNIT_CENTIMETER:
                $value /= 360000;
                break;
            case self::UNIT_INCH:
                $value /= 914400;
                break;
            case self::UNIT_PIXEL:
                $value = Drawing::emuToPixels($value);
                break;
            case self::UNIT_POINT:
                $value /= 12700;
                break;
            case self::UNIT_EMU:
            default:
                // no changes
        }
        return $value;
    }
}
