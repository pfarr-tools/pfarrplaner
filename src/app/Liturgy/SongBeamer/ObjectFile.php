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

namespace App\Liturgy\SongBeamer;

class ObjectFile extends AbstractFile
{

    protected $class = '';
    protected $type = '';
    protected $items = [];

    public function __construct($class, $type, $items)
    {
        $this->class = $class;
        $this->type = $type;
        $this->items = $items;
    }

    protected function valueToString($value) {
        if (is_numeric($value)) return (string)$value;
        if (is_string($value)) {
            if (substr($value, 0, 2) == '@@') return substr($value, 2);
            return "'{$value}'";
        };
        if (is_array($value)) return '[]';
        return "''";
    }

    public function itemToText($config)
    {
        $item = $this->line('item', 2);
        foreach ($config as $key => $value) {
            $item .= $this->line(ucfirst($key) . ' = ' . $this->valueToString($value), 3);
        }
        $item .= $this->line('end', 2);
        return $item;
    }

    public function itemsToText($items)
    {
        $text = $this->line('items = <', 1);
        foreach ($items as $item) {
            $text .= is_string($item) ? $item : $this->itemToText($item);
        }
        $text .= $this->line('>', 1);
        return $text;
    }

    public function toText()
    {
        return $this->line('object ' . $this->class . ': ' . $this->type)
            . $this->itemsToText($this->items)
            . $this->line('end');
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function addItem($item): void
    {
        $this->items[] = $item;
    }


}
