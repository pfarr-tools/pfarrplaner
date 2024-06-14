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

namespace App\Attachments;

use Illuminate\Support\Str;

class AttachmentFactory
{
    public static function getList($type) {
        if (!in_array($type, ['baptism', 'funeral', 'wedding'])) return [];
        return collect(glob(app_path('Attachments/' . ucfirst($type) . '/*.php')))
        ->reject(function ($file) { return Str::startsWith(basename($file), 'Abstract'); })
            ->map(function ($file) {
                return (static::getClassFromFile($file))::getInfo();
        });

    }

    public static function get($type, $key, $id): ?AbstractAttachment
    {
        $item = static::getList($type)->first(function ($item) use ($key) {
            return $item['key'] == $key;
        });
        if (!$item) return null;
        return ($item['class'])::fromId($id);
    }

    protected static function getClassFromFile($file)
    {
        return 'App\\'.Str::replace(['/', '.php'], ['\\', ''], Str::after($file, 'app/'));
    }
}
