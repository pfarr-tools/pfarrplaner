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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 27.06.2019
 * Time: 12:29
 */

namespace App\Models;

use App\Models\Leave\Absence;
use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Class Vacations
 */
class Vacations
{
    /**
     * Return the vacation replacements for a given day.
     *
     * @param  Carbon|string  $date  Day to check, either as Carbon instance or as a parseable string
     * @return array<string, string> Replacement labels keyed by user identifier
     */
    public static function getByDate(Carbon|string $date): array
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }
        $vacationers = [];
        $vacationData = self::getVacationDataFromCache();

        foreach ($vacationData as $key => $datum) {
            if (($date >= $datum['start']) && ($date <= $datum['end'])) {
                if (preg_match('/(?:U:|FB:) (\w*)/', $datum['title'], $tmp)) {
                    $vacationers[$tmp[1]] = $tmp[0];
                }
            }
        }

        return $vacationers;
    }

    /**
     * Load the vacation data from the cache or fetch it from the configured URL.
     *
     * @return mixed
     */
    protected static function getVacationDataFromCache()
    {
        if (! Cache::has('vacationData')) {
            $contents = file_get_contents((string) config('calendar.vacation_url'));
            $vacationData = json_decode($contents ?: '', true);
            if (! is_array($vacationData)) {
                $vacationData = [];
            }
            foreach ($vacationData as $key => $datum) {
                $vacationData[$key]['start'] = Carbon::createFromTimeString($datum['start']);
                $vacationData[$key]['end'] = Carbon::createFromTimeString($datum['end']);
            }
            Cache::put('vacationData', $vacationData, 10000);
        } else {
            $vacationData = Cache::get('vacationData');
        }

        return $vacationData;
    }

    /**
     * Return the absences and replacements of a user within a period.
     *
     * @param  Carbon  $start  First day of the period
     * @param  Carbon  $end  Last day of the period
     * @param  User|null  $user  User whose absences are returned
     * @return mixed
     */
    public static function getByPeriodAndUser(Carbon $start, Carbon $end, ?User $user)
    {
        if ($user === null) {
            return Absence::query()->whereRaw('1 = 0');
        }

        return Absence::with('replacements')->byUserAndPeriod($user, $start, $end);
    }

    /**
     * Find a user by the end of their name.
     *
     * @param  string  $lastName  Name suffix to search for
     * @return Builder|Model|object|null
     */
    protected static function findUserByLastName(string $lastName)
    {
        return User::with('cities')->where('name', 'like', '%'.$lastName)->first();
    }
}
