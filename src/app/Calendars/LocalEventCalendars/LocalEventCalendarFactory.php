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

namespace App\Calendars\LocalEventCalendars;

use File;
use Illuminate\Support\Str;

class LocalEventCalendarFactory
{

    public static function all(): array {
        $classes = [];
        foreach (File::allFiles(app_path('Calendars/LocalEventCalendars')) as $file) {
            if (($file->getExtension() == 'php') && (!Str::contains($file->getPathname(), 'Abstract')) && (!Str::contains($file->getPathname(), 'Factory'))) {
                $classes[] = substr('App\\Calendars\\LocalEventCalendars\\' . Str::replace('/', '\\', $file->getRelativePathname()), 0, -4);
            }
        };
        return $classes;
    }

    public static function list(): array {
        $calendars = [];
        foreach (static::all() as $calendar) {
            $calendars = array_merge($calendars, $calendar::list());
        }
        return $calendars;
    }

    public static function get($key): AbstractLocalEventCalendar
    {
        if (!Str::contains($key, ':')) abort(404);
        $parts = explode(':', $key);
        $class = 'App\\Calendars\\LocalEventCalendars\\'.ucfirst($parts[0]).'LocalEventCalendar';
        if (!class_exists($class)) abort(404);
        return new $class($parts[1]);
    }


}
