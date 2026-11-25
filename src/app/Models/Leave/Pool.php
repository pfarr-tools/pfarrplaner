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

namespace App\Models\Leave;

use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Places\City;
use Database\Factories\Leave\PoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Pool extends AbstractModel
{
    /** @use HasFactory<PoolFactory> */
    use HasFactory;

    protected $fillable = ['id', 'name', 'slug', 'contact', 'office', 'phone', 'email'];

    protected static string $prefix = 'pool';

    protected static string $prefixPlural = 'pools';

    public static array $exceptRoutes = ['web' => ['show'], 'api' => ['show']];

    public static array $validationRules = [
        'name' => 'required|max:255',
        'contact' => 'nullable|string',
        'office' => 'nullable|string',
        'phone' => 'nullable|string',
        'email' => 'nullable|email',
        'users.*' => 'nullable|int|exists:users,id',
        'cities.*' => 'nullable|int|exists:cities,id',
    ];

    public static $adminIcon = 'mdi mdi-pool';

    public static $adminGroup = 'Personen';

    public static $adminTitle = 'Vertretungs-Pools';

    public static $relationsForIndex = ['cities', 'users'];

    public static $relationsForEditor = ['cities', 'users'];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function cities()
    {
        return $this->belongsToMany(City::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function poolmasters()
    {
        return $this->belongsToMany(User::class, 'poolmasters', 'pool_id', 'user_id');
    }

    /**
     * Get an empty model instance with editor relations preloaded.
     */
    public static function getEmptyModel(): self
    {
        /** @var self $model */
        $model = parent::getEmptyModel();
        $model->setRelation('cities', collect());
        $model->setRelation('users', collect());

        return $model;
    }

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pool) {
            /** @var self $pool */
            $name = $pool->name;
            /** @var string $name */
            $pool->slug = Str::slug($name, '-', 'de');
        });

        static::updating(function ($pool) {
            /** @var self $pool */
            $name = $pool->name;
            /** @var string $name */
            $pool->slug = Str::slug($name, '-', 'de');
        });
    }
}
