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

namespace App\Helpers;

use App\Models\Places\City;

/**
 * Class YoutubeHelper
 */
class YoutubeHelper
{
    /**
     * Ermittelt die URL zum Live-Dashboard eines YouTube-Videos.
     *
     * @param  string  $url  YouTube-URL des Gottesdienstes
     * @return string Dashboard-URL
     */
    public static function getLiveDashboardUrl(City $city, string $url)
    {
        return 'https://studio.youtube.com/video/'
            .self::getCode($url).'/livestreaming';
    }

    /**
     * Ermittelt die Kanal-ID aus einer YouTube-Kanal-URL.
     *
     * @param  string  $url  YouTube-Kanal-URL
     * @return mixed|string Kanal-ID
     */
    public static function getChannelId(string $url)
    {
        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
        }
        $tmp = explode('/', $url);
        $code = end($tmp);

        return $code;
    }

    /**
     * Ermittelt den Videocode aus einer YouTube-URL.
     *
     * @param  string  $url  YouTube-URL
     * @return string Videocode
     */
    public static function getCode(string $url): string
    {
        $code = '';
        if (strpos($url, 'youtu.be') !== false) {
            $tmp = explode('/', $url);
            $code = end($tmp);
        }

        return is_string($code) ? $code : '';
    }
}
