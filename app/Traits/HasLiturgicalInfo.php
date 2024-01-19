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

namespace App\Traits;

use App\Services\LiturgyService;
use App\Models\LiturgyInfo;

trait HasLiturgicalInfo
{

    /**
     * Add liturgy attribute
     * @return array
     */
    public function getLiturgicalInfoAttribute(): array
    {
        if ($this->liturgy_info_id) return LiturgyInfo::find($this->liturgy_info_id)->toArray() ?? [];
        $info = LiturgyService::getLiturgyInfoByDate($this->alt_liturgy_date ?: $this->date)->first();
        if ($info) return $info->toArray();
        return [];
    }

    public function getLiturgicalInfoDateAttribute()
    {
        return $this->liturgical_info['date'] ?? $this->date;
    }

    public function getIsAlternatePropriumAttribute(): bool
    {
        if (!$this->liturgy_info_id) return false;
        if ($this->liturgical_info['date'] != $this->date->format('d.m.Y')) return true;
        $firstRecord = LiturgyService::getLiturgyInfoByDate($this->alt_liturgy_date ?: $this->date)->first();
        if (!$firstRecord) return false;
        if ($this->liturgy_info_id != ($firstRecord['id'] ?? -1)) return true;
        return false;
    }

}
