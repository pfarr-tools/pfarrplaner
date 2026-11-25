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

namespace App\Liturgy\ItemHelpers;

use Illuminate\Support\Str;

class FreetextItemHelper extends AbstractItemHelper
{
    /**
     * Name des Datenfeldes, dessen Inhalt als Freitext ausgegeben wird.
     *
     * @var string
     */
    protected $field = 'description';

    /**
     * Gibt den Namen des verwendeten Datenfeldes zurück.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Setzt das Datenfeld, dessen Inhalt als Freitext ausgegeben wird.
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    /**
     * Gibt den Freitext zurück, optional auf eine maximale Länge gekürzt.
     *
     * @param  int|false  $shorten  Maximale Zeichenzahl oder false für den vollständigen Text
     */
    public function getText(int|false $shorten = false): string
    {
        if (! $this->item->data[$this->field]) {
            return '';
        }
        $s = strtr($this->item->data[$this->field], [
            '</p>' => "\r\n",
            '<br>' => "\n",
            '<br/>' => "\n",
            '<br />' => '\n',
            '<p>' => '',
            '&nbsp;' => ' ',
        ]);
        if ($shorten) {
            return Str::limit($s, $shorten, '...', true);
        }

        return $s;
    }
}
