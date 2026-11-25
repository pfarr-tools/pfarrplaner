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

use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FileNameService
{
    /**
     * Erzeugt einen datei- und ordnerfreundlichen Namen aus Titel, Datum, Rufzeichen und Person.
     *
     * @param  string  $title  Titel des Dokuments
     * @param  string|null  $extension  Dateiendung mit oder ohne führenden Punkt
     * @param  mixed  $callSign  Kürzel, das dem Namen vorangestellt wird
     * @param  array<Carbon|string>|Carbon|string|null  $dates  Ein oder mehrere Datumswerte
     * @param  bool  $withTime  Uhrzeit im Datumsformat berücksichtigen
     * @param  User|string|null  $person  Person oder Name, der dem Namen vorangestellt wird
     * @param  string|null  $forceDateFormat  Erzwingt ein bestimmtes Datumsformat
     */
    public static function make($title, $extension = '', $callSign = '', $dates = null, $withTime = false, $person = null, $forceDateFormat = null): string
    {
        if ((! empty($extension)) && (substr($extension, 0, 1) != '.')) {
            $extension = '.'.$extension;
        }
        $date = null;
        if ($dates) {
            if (! is_array($dates)) {
                $dates = [$dates];
            }
            foreach ($dates as $index => $date) {
                if (! $date instanceof Carbon) {
                    $date = Carbon::parse($date);
                }
                $dateFormat = $forceDateFormat ?? ($withTime ? 'Ymd-Hi' : 'Ymd');
                $dates[$index] = $date->setTimezone('Europe/Berlin')->format($dateFormat);
            }
            $date = implode('-', $dates).' ';
        }

        if ($person) {
            $name = ($person instanceof User ? NameService::fromUser($person) : NameService::fromName($person))->format(NameService::LAST_COMMA_FIRST).' ';
        } else {
            $name = '';
        }

        $title = Str::replace(' /', ',', (string) $title);
        $title = Str::replace('/', ',', (string) $title);

        return ($callSign ? $callSign.($date ? '_' : '') : '')
            .($date ?? ' ').$name.$title.($extension ?: '');

    }
}
