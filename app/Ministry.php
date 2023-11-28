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

namespace App;


use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Self_;

/**
 * Class Ministry
 * @package App
 */
class Ministry
{
    /**
     * Get all available ministries
     *
     * @param bool $includeBasicMinistries Include P/O/M/A ?
     * @return array
     */
    public static function all(bool $includeBasicMinistries = false): array
    {
        $ministries = Participant::all()
            ->pluck('category')
            ->unique()
            ->reject(function ($item) {
                return in_array($item, ['P', 'O', 'M', 'A']) || is_numeric($item);
            })
            ->sort();

        $m = $includeBasicMinistries ? self::POMA() : [];

        foreach ($ministries as $ministry) {
            $m[$ministry] = $ministry;
        }

        return $m;
    }

    /**
     * @param $title
     * @return string
     */
    public static function title($title): string
    {
        if ($title == 'P') {
            return config('labels.pastor');
        }
        if ($title == 'O') {
            return config('labels.organist');
        }
        if ($title == 'M') {
            return config('labels.sacristan');
        }
        if ($title == 'A') {
            return 'Weitere Beteiligte';
        }
        return $title;
    }

    /**
     * @return array
     */
    public static function POMA(): array
    {
        return ['P' => config('labels.pastor'), 'O' => config('labels.organist'), 'M' => config('labels.sacristan'), 'A' => 'Weitere Beteiligte'];
    }

    /**
     * @return array
     */
    public static function selectList(): array
    {
        $ministries = [];
        foreach (Participant::all()->pluck('category')->unique() as $ministry) {
            $ministries[] = [
                'id' => $ministry,
                'name' => self::POMA()[$ministry] ?? $ministry,
            ];
        }
        return $ministries;
    }
}
