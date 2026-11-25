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

namespace App\Calendars\LocalEventCalendars;

use File;
use Illuminate\Support\Str;

/** Factory zum Auffinden und Erzeugen lokaler Veranstaltungskalender. */
class LocalEventCalendarFactory
{
    /**
     * Ermittelt die Klassen aller lokalen Veranstaltungskalender.
     *
     * Dateien mit „Abstract“ oder „Factory“ im Pfad werden ausgeschlossen,
     * damit nur konkrete Kalenderklassen zurückgegeben werden.
     *
     * @return array<int, class-string<AbstractLocalEventCalendar>> Vollqualifizierte Klassennamen der Kalender.
     */
    public static function all(): array
    {
        $classes = [];
        foreach (File::allFiles(app_path('Calendars/LocalEventCalendars')) as $file) {
            if (($file->getExtension() == 'php') && (! Str::contains($file->getPathname(), 'Abstract')) && (! Str::contains($file->getPathname(), 'Factory'))) {
                /** @var class-string<AbstractLocalEventCalendar> $class */
                $class = substr('App\\Calendars\\LocalEventCalendars\\'.Str::replace('/', '\\', $file->getRelativePathname()), 0, -4);
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * Sammelt die Kalendereinträge aller lokalen Veranstaltungskalender.
     *
     * @return array<int, array{id: string, name: string, group: string}> Kalendereinträge aller Kalenderklassen.
     */
    public static function list(): array
    {
        $calendars = [];
        foreach (static::all() as $calendar) {
            $calendars = array_merge($calendars, $calendar::list());
        }

        return $calendars;
    }

    /**
     * Erzeugt den lokalen Veranstaltungskalender zu einem Kalenderschlüssel.
     *
     * Bei einem Schlüssel ohne Doppelpunkt oder einer unbekannten Kalenderklasse
     * wird ein 404-Fehler ausgelöst.
     *
     * @param  string  $key  Kalenderschlüssel in der Form „typ:kennung“.
     * @return AbstractLocalEventCalendar Kalender zum angegebenen Schlüssel.
     */
    public static function get(string $key): AbstractLocalEventCalendar
    {
        if (! Str::contains($key, ':')) {
            abort(404);
        }
        $parts = explode(':', $key);
        $class = 'App\\Calendars\\LocalEventCalendars\\'.ucfirst($parts[0]).'LocalEventCalendar';
        if (! class_exists($class)) {
            abort(404);
        }

        /** @var AbstractLocalEventCalendar $calendar */
        $calendar = new $class($parts[1]);

        return $calendar;
    }
}
