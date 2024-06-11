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

namespace App\Models\Leave;

use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pool extends AbstractModel
{
    use HasFactory;

    protected $fillable = ['id', 'name'];

    protected static string $prefix = 'pool';
    protected static string $prefixPlural = 'pools';
    public static array $exceptRoutes = ['web' => ['show'], 'api' => ['show']];
    public static array $validationRules = [
        'name' => 'required|max:255',
        'users.*' => 'nullable|int|exists:users,id',
        'cities.*' => 'nullable|int|exists:cities,id',
    ];

    public static $adminIcon = 'mdi mdi-pool';

    public static $relationsForIndex = ['cities', 'users'];
    public static $relationsForEditor = ['cities', 'users'];


    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class);
    }

    public function poolmasters() {
        return $this->belongsToMany(User::class, 'poolmasters', 'pool_id', 'user_id');
    }

}
