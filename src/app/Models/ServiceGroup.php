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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class ServiceGroup
 * @package App
 */
class ServiceGroup extends AbstractModel
{
    use HasFactory;

    protected static string $prefix = 'servicegroup';
    protected static string $prefixPlural = 'servicegroups';
    protected static string $path = '';
    public static array $exceptRoutes = [
        'web' => ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'],
        'api' => ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'],
    ];
    public static array $validationRules = [
        'name' => 'required|string|max:255',
    ];

    /**
     * @var string[]
     */
    protected $fillable = ['name'];

    /**
     * @param $list
     * @return array
     */
    public static function createIfMissing($list)
    {
        $result = [];
        foreach ($list as $element) {
            if (is_numeric($element)) {
                $result[] = $element;
            } else {
                $sg = static::firstOrCreate(['name' => $element]);
                $result[] = $sg->id;
            }
        }
        return $result;
    }

    /**
     * @return BelongsToMany
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }
}
