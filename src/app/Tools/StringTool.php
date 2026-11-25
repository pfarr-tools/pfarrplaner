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
 * Date: 11.10.2019
 * Time: 19:03
 */

namespace App\Tools;

use Carbon\Carbon;

/**
 * Class StringTool
 */
class StringTool
{
    /**
     * Formatiert eine Uhrzeit im Format H:i als Text.
     *
     * @param  string  $s  Uhrzeit im Format H:i
     * @param  bool  $clockText  " Uhr" anhängen
     * @param  string  $separator  Trennzeichen zwischen Stunden und Minuten
     * @return string
     */
    public static function timeString($s, $clockText = true, $separator = ':')
    {
        return Carbon::createFromTimeString($s)->isoFormat('HH'.$separator.'mm').($clockText ? ' Uhr' : '');
    }

    /**
     * @param  Carbon  $from  Start.
     * @param  Carbon  $to  End.
     */
    public static function durationText(Carbon $from, Carbon $to): string
    {
        if ($from == $to) {
            return $from->format('d.m.Y');
        } elseif ($from->year == $to->year) {
            return $from->format('d.m.').' - '.$to->format('d.m.Y');
        } else {
            return $from->format('d.m.Y').' - '.$to->format('d.m.Y');
        }
    }

    /**
     * Wählt abhängig von der Anzahl die Singular-, Plural- oder Null-Form.
     *
     * @param  int  $count  Anzahl, die die Wortform bestimmt
     * @param  string  $singular  Singularform
     * @param  string  $plural  Pluralform
     * @param  string  $zeroString  Form für den Wert 0
     * @return string
     */
    public static function pluralString($count, $singular, $plural, $zeroString = '')
    {
        if ($count == 0) {
            return $zeroString ?: $plural;
        }

        return ($count == 1) ? $singular : $plural;
    }

    /**
     * Kürzt einen Text am Wortende und kennzeichnet das Kürzen mit "...".
     *
     * @param  string  $s  Zu kürzender Text
     * @param  int  $maxLength  Maximale Textlänge
     * @return string
     */
    public static function trimToLen($s, $maxLength)
    {
        if (strlen($s) > $maxLength) {
            $offset = ($maxLength - 3) - strlen($s);
            $s = substr($s, 0, strrpos($s, ' ', $offset) ?: 0).'...';
        }

        return $s;
    }

    /**
     * Formatiert einen Zeitwert als Uhrzeittext.
     *
     * @param  mixed  $time  Unix-Zeitstempel, numerischer Zeitwert oder parsare Zeitangabe
     * @param  bool  $uhr  " Uhr" anhängen
     * @param  string  $separator  Trennzeichen zwischen Stunden und Minuten
     * @param  bool  $skipMinutes  Minuten bei vollen Stunden ausblenden
     * @param  bool  $nbsp  Geschütztes Leerzeichen vor "Uhr" verwenden
     * @param  bool  $leadingZero  Stunde mit führender Null ausgeben
     * @return string
     */
    public static function timeText(
        $time,
        $uhr = true,
        $separator = ':',
        $skipMinutes = false,
        $nbsp = false,
        $leadingZero = false
    ) {
        if (! is_numeric($time)) {
            $time = strtotime(is_string($time) ? $time : '');
        }
        $format = ($leadingZero ? '%H' : '%k').$separator.'%M';
        if ($skipMinutes) {
            if ((int) strftime('%M', (int) $time) == 0) {
                $format = '%H';
            }
        }

        return trim(strftime($format, (int) $time).($uhr ? ($nbsp ? '&nbsp;' : ' ').'Uhr' : ''));
    }

    /**
     * Rückt die Zeilen eines Textes entsprechend ihrer HTML-Tiefe ein.
     *
     * @param  string  $text  Zu formatierender Text
     * @return string
     */
    public static function indent($text)
    {
        $indent = 0;
        $lines = explode("\n", $text);
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (substr($line, 0, 2) == '</') {
                $indent--;
            } elseif (substr($line, 0, 1) == '<') {
                $indent++;
            }
            $lines[$index] = str_pad($line, 4 * $indent, ' ', STR_PAD_LEFT);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  string  $s  Input text.
     */
    public static function sanitizeXMLString(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
