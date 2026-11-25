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

namespace App\Liturgy\Replacement;

use App\Models\Service;

class Replacement
{
    /**
     * Replace all known markers in the given text.
     *
     * @param  string  $text  Text containing liturgy replacement markers.
     * @param  Service  $service  Service used to determine the replacement values.
     * @return string Text with all known markers replaced.
     */
    public static function replaceAll(string $text, Service $service): string
    {
        preg_match_all('/\[(\w*)\]/', $text, $replacers);
        if (! isset($replacers[1])) {
            return $text;
        }
        foreach ($replacers[1] as $replacer) {
            $replacerClass = 'App\\Liturgy\\Replacement\\'.ucfirst($replacer).'Replacer';
            if (class_exists($replacerClass) && is_subclass_of($replacerClass, AbstractReplacer::class)) {
                /** @var AbstractReplacer $replacement */
                $replacement = new $replacerClass($service);
                $text = $replacement->replace($text);
            }
        }

        return $text;
    }

    /**
     * Instantiate all available replacements.
     *
     * @return array<string, AbstractReplacer> Replacements indexed by their marker key.
     */
    public static function factory(): array
    {
        $classes = [];
        foreach (glob(app_path('Liturgy/Replacement/*Replacer.php')) ?: [] as $file) {
            if (substr(basename($file), 0, 8) != 'Abstract') {
                $className = 'App\\Liturgy\\Replacement\\'.str_replace('.php', '', basename($file));
                /** @var AbstractReplacer $object */
                $object = new $className(new Service);
                $classes[$object->getKey()] = $object;
            }
        }

        return $classes;
    }

    /**
     * List the descriptions of all available replacements.
     *
     * @return array<string, string> Descriptions indexed by their marker key.
     */
    public static function getList(): array
    {
        $records = [];
        /**
         * @var string $tag
         * @var AbstractReplacer $replacer
         */
        foreach (self::factory() as $tag => $replacer) {
            $records[$tag] = $replacer->getDescription();
        }

        return $records;
    }
}
