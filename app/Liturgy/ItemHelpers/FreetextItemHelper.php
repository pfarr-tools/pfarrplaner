<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.de
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

namespace App\Liturgy\ItemHelpers;


use App\Baptism;
use App\Funeral;
use App\Liturgy\PronounSets\AbstractPronounSet;
use App\Liturgy\PronounSets\PronounSets;
use App\Service;
use App\Services\NameService;
use App\Wedding;
use Carbon\Carbon;

class FreetextItemHelper extends AbstractItemHelper
{

    public function getText($shorten = false) {
        if(!$this->item->data['description']) return '';
        $s = strtr($this->item->data['description'], [
            '</p>' => "\r\n",
            '<br>' => "\n",
            '<br/>' => "\n",
            '<br />' => '\n',
            '<p>' => '',
        ]);
        if ($shorten) {
            return str_contains($s, "\n") ? explode("\n", $s)[0] : substr($s, 0, $shorten).'...';
        }
        return $s;
    }

}
