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

use App\Models\Calendar\Day;
use App\Models\LiturgyInfo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Storage;

/**
 * Class Liturgy
 * @package App
 */
class LiturgyService
{

    /** @var Liturgy|null Instance */
    protected static $instance = null;

    protected static $calendars = [];
    protected static $lectionaryYears = [];

    /**
     * @return Liturgy|null
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a specific liturgical item by code
     * @param $year Year
     * @param $code Code
     * @return array|mixed
     */

    public static function getLiturgyByCode($year, $code) {
        $year = static::getYear($year);
        foreach ($year['Tage'] as $date => $items) {
            foreach ($items as $item) {
                if ($item['Code'] == $code) {
                    $item['Datum'] = $date;
                    return $item;
                }
            }
        }
        return [];
    }

    /**
     * Get the date for a proprium by code
     * @param $year Year
     * @param $code Code
     * @return Carbon|null
     */
    public static function getPropriumDateByCode($year, $code) {
        $proprium = static::getLiturgyByCode($year, $code);
        if (isset($proprium['Datum'])) return Carbon::parse($proprium['Datum']);
        return null;
    }

    /**
     * Get all the propria for a specific date
     * @param $date
     * @return array|mixed
     */
    public static function getLiturgyInfoByDate($date)
    {
        if (is_object($date)) $date = $date->format('Y-m-d');
        return (static::getYear(substr($date, 0, 4))['Tage'] ?? [])[$date] ?? [];
    }

    /**
     * Get the liturgical calendar for a specific year
     * @param $year
     * @return mixed
     */
    public static function getYear($year)
    {
        if (isset(static::$calendars[$year])) return static::$calendars[$year];
        if (!Storage::exists('liturgy/'.$year.'.json')) {
            Storage::put('liturgy/'.$year.'.json', file_get_contents('https://kirchenjahr.pfarr.tools/api/jahr/'.$year));
        }
        return static::$calendars[$year] = json_decode(Storage::get('liturgy/'.$year.'.json'), true);
    }

    public static function getLectionaryYear($year) {
        if (isset(static::$lectionaryYears[$year])) return static::$lectionaryYears[$year];
        if (!Storage::exists('liturgy/lesejahr-'.$year.'.json')) {
            Storage::put('liturgy/lesejahr-'.$year.'.json', file_get_contents('https://kirchenjahr.pfarr.tools/api/lesejahr/'.$year));
        }
        return static::$lectionaryYears[$year] = json_decode(Storage::get('liturgy/lesejahr-'.$year.'.json'), true);
    }

    public static function getLiturgyByAltPropriumCode($code)
    {
        list($subCode,$year) = explode('-',$code);
        if (!$year) return [];
        if (!$subCode) return [];
        if ($lectionaryYear = static::getLectionaryYear($year)) return $lectionaryYear[$subCode] ?? [];
        return [];
    }

}
