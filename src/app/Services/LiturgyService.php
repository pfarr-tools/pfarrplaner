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
 *
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

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Storage;
use Throwable;

/**
 * Class Liturgy
 */
class LiturgyService
{
    /** @var self|null Instance */
    protected static $instance = null;

    /** @var array<int|string, mixed> Liturgical calendars keyed by year. */
    protected static $calendars = [];

    /** @var array<int|string, array<string, mixed>> Lectionary data keyed by year. */
    protected static $lectionaryYears = [];

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get a specific liturgical item by code
     *
     * @param  int|string  $year  Year
     * @param  string  $code  Code
     * @return array|mixed
     */
    public static function getLiturgyByCode($year, $code)
    {
        $year = static::getYear($year);
        /** @var array{Tage?: array<string, mixed>} $year */
        $days = $year['Tage'] ?? [];
        /** @var array<string, array<int, array<string, mixed>>> $days */
        foreach ($days as $date => $items) {
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
     *
     * @param  int|string  $year  Year
     * @param  string  $code  Code
     * @return Carbon|null
     */
    public static function getPropriumDateByCode($year, $code)
    {
        $proprium = static::getLiturgyByCode($year, $code);
        /** @var array{Datum?: string} $proprium */
        if (isset($proprium['Datum'])) {
            return Carbon::parse($proprium['Datum']);
        }

        return null;
    }

    /**
     * Get all the propria for a specific date
     *
     * @param  Carbon|string  $date  Date as a Carbon instance or formatted string
     * @return array|mixed
     */
    public static function getLiturgyInfoByDate($date)
    {
        if (is_object($date)) {
            $date = $date->format('Y-m-d');
        }

        $year = static::getYear(substr($date, 0, 4));
        /** @var array{Tage?: array<string, mixed>} $year */

        return ($year['Tage'] ?? [])[$date] ?? [];
    }

    /**
     * Get the liturgical calendar for a specific year
     *
     * @param  int|string  $year  Liturgical year
     * @return mixed
     */
    public static function getYear($year)
    {
        if (isset(static::$calendars[$year])) {
            return static::$calendars[$year];
        }
        if (! Storage::exists('liturgy/'.$year.'.json')) {
            Storage::put('liturgy/'.$year.'.json', static::fetchCalendarJson('https://kirchenjahr.pfarr.tools/api/jahr/'.$year));
        }

        return static::$calendars[$year] = json_decode(Storage::get('liturgy/'.$year.'.json') ?? '', true);
    }

    /**
     * Load the lectionary data for a year, using the application cache.
     *
     * @param  int|string  $year  Liturgical year.
     * @return array<string, mixed> Lectionary data.
     */
    public static function getLectionaryYear($year)
    {
        if (isset(static::$lectionaryYears[$year])) {
            return static::$lectionaryYears[$year];
        }
        if (! Storage::exists('liturgy/lesejahr-'.$year.'.json')) {
            Storage::put('liturgy/lesejahr-'.$year.'.json', static::fetchCalendarJson('https://kirchenjahr.pfarr.tools/api/lesejahr/'.$year));
        }

        /** @var array<string, mixed> $lectionaryYear */
        $lectionaryYear = json_decode(Storage::get('liturgy/lesejahr-'.$year.'.json') ?? '', true);

        return static::$lectionaryYears[$year] = $lectionaryYear;
    }

    /**
     * Resolve liturgy data from an alternative proprium code.
     *
     * @param  string  $code  Alternative proprium code.
     * @return array<string, mixed> Matching liturgy data.
     */
    public static function getLiturgyByAltPropriumCode($code)
    {
        [$subCode, $year] = explode('-', $code);
        if (! $year) {
            return [];
        }
        if (! $subCode) {
            return [];
        }
        if ($lectionaryYear = static::getLectionaryYear($year)) {
            /** @var array<string, mixed> $lectionaryData */
            $lectionaryData = $lectionaryYear[$subCode] ?? [];

            return $lectionaryData;
        }

        return [];
    }

    /**
     * Fetch a missing liturgical calendar from the Kirchenjahr API.
     *
     * @param  string  $url  API URL
     * @return string JSON payload
     */
    protected static function fetchCalendarJson(string $url): string
    {
        try {
            return Http::get($url)->throw()->body() ?: '{}';
        } catch (Throwable) {
            return '{}';
        }
    }
}
