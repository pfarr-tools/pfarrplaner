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

namespace App\Liturgy\SongBeamer;

/**
 * Represents a configurable SongBeamer object file.
 */
class ObjectFile extends AbstractFile
{
    /** @var string Class name used in the object declaration. */
    protected $class = '';

    /** @var string Type name used in the object declaration. */
    protected $type = '';

    /**
     * Configuration blocks or raw text lines of the object.
     *
     * @var array<array-key, array<string, mixed>|string>
     */
    protected $items = [];

    /**
     * @param  string  $class  Class name used in the object declaration
     * @param  string  $type  Type name used in the object declaration
     * @param  array<array-key, array<string, mixed>|string>  $items  Configuration blocks or raw text lines
     */
    public function __construct($class, $type, $items)
    {
        $this->class = $class;
        $this->type = $type;
        $this->items = $items;
    }

    /**
     * Formats a single configuration value for the SongBeamer object syntax.
     *
     * @param  mixed  $value  Configuration value to format
     * @return string Formatted value
     */
    protected function valueToString($value)
    {
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            if (substr($value, 0, 2) == '@@') {
                return substr($value, 2);
            }

            return "'{$value}'";
        }
        if (is_array($value)) {
            return '[]';
        }

        return "''";
    }

    /**
     * Renders a single configuration block as object file text.
     *
     * @param  array<string, mixed>  $config  Configuration key-value pairs
     * @return string Rendered configuration block
     */
    public function itemToText($config)
    {
        $item = $this->line('item', 2);
        foreach ($config as $key => $value) {
            $item .= $this->line(ucfirst($key).' = '.$this->valueToString($value), 3);
        }
        $item .= $this->line('end', 2);

        return $item;
    }

    /**
     * Renders all items of the object as object file text.
     *
     * @param  array<array-key, array<string, mixed>|string>  $items  Configuration blocks or raw text lines
     * @return string Rendered items section
     */
    public function itemsToText($items)
    {
        $text = $this->line('items = <', 1);
        foreach ($items as $item) {
            $text .= is_string($item) ? $item : $this->itemToText($item);
        }
        $text .= $this->line('>', 1);

        return $text;
    }

    /**
     * Renders the complete object file.
     *
     * @return string Complete object file text
     */
    public function toText()
    {
        return $this->line('object '.$this->class.': '.$this->type)
            .$this->itemsToText($this->items)
            .$this->line('end');
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param  string  $class  Object class.
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param  string  $type  Object type.
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array<array-key, array<string, mixed>|string> Configuration blocks or raw text lines
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param  array<array-key, array<string, mixed>|string>  $items  Configuration blocks or raw text lines
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @param  array<string, mixed>|string  $item  Configuration block or raw text line
     */
    public function addItem($item): void
    {
        $this->items[] = $item;
    }
}
