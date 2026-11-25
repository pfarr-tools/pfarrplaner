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

namespace App\Liturgy\PronounSets;

class PronounSets
{
    /**
     * Return all pronoun sets found on disk.
     *
     * @return array<int, AbstractPronounSet> Pronoun set instances.
     */
    public static function all(): array
    {
        $items = [];
        foreach (glob(app_path('Liturgy/PronounSets/*PronounSet.php')) ?: [] as $file) {
            $short = pathinfo($file, PATHINFO_FILENAME);
            if (substr($short, 0, 8) != 'Abstract') {
                $class = '\\App\\Liturgy\\PronounSets\\'.$short;
                /** @var AbstractPronounSet $item */
                $item = new $class;
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Return pronoun sets as a label-to-key map.
     *
     * @return array<string, string> Label to key mapping.
     */
    public static function items(): array
    {
        $items = [];
        /** @var AbstractPronounSet $item */
        foreach (self::all() as $item) {
            $items[$item->getLabel()] = $item->getKey();
        }

        return $items;
    }

    /**
     * Return a single pronoun set by its key.
     *
     * @param  string|null  $key  Pronoun set key; empty values use the default set.
     * @return AbstractPronounSet|null Pronoun set or null if it does not exist.
     */
    public static function get($key): ?AbstractPronounSet
    {
        if (empty($key)) {
            $key = 'er';
        } // fallback pronoun set
        $class = '\\App\\Liturgy\\PronounSets\\'.ucfirst($key).'PronounSet';
        if (! class_exists($class)) {
            return null;
        }

        /** @var AbstractPronounSet $pronounSet */
        $pronounSet = new $class;

        return $pronounSet;
    }

    /**
     * Return pronoun sets as arrays with key and label.
     *
     * @return array<int, array{key: string, label: string}> Pronoun set data.
     */
    public static function toArray(): array
    {
        $pronounSets = [];
        /** @var AbstractPronounSet $pronounSet */
        foreach (self::all() as $pronounSet) {
            $pronounSets[] = ['key' => $pronounSet->getKey(), 'label' => $pronounSet->getLabel()];
        }

        return $pronounSets;
    }
}
