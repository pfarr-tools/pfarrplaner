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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Poolmaster extends AbstractModel
{

    protected $fillable = ['id',  'pool_id', 'user_id', 'start', 'end'];
    protected $casts = ['start' => 'date', 'end' => 'date'];


    protected static string $prefix = 'poolmaster';
    protected static string $prefixPlural = 'poolmaster';
    public static array $exceptRoutes = ['web' => ['show'], 'api' => ['show']];
    public static array $validationRules = [
        'pool_id' => 'required|integer|exists:pools,id',
        'user_id' => 'required|integer|exists:users,id',
        'start' => 'required|date',
        'end' => 'required|date',
    ];

    public function pool()
    {
        return $this->belongsTo(Pool::class, 'pool_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function currentReplacements(): HasMany
    {
        return $this->hasMany(Replacement::class, 'pool_id', 'pool_id')
            ->with('absence')
            ->where('from', '<=', Carbon::now())
            ->where('to', '>=', Carbon::now());
    }

    public static function getAdminModuleConfig()
    {
        return false;
    }

    public function getLabelAttribute(): string
    {
        return ($this->pool ? $this->pool->name.', ' : '').$this->start.'-'.$this->end;
    }

    /**
     * Get an empty model instance
     *
     * @override AbstractModel::getEmptyModel() because Poolmaster has no name field
     */
    public static function getEmptyModel(): AbstractModel
    {
        $model = new static();
        $data = array_merge(array_fill_keys($model->getFillable(), null), $model->fillDefaults());
        $model->fill($data);
        return $model;
    }


    /**
     * Mutator for start date, needed to deal with ISO date strings
     * @param $value
     * @return void
     */
    public function setStartAttribute($value): void
    {
        $this->attributes['start'] = $value ? Carbon::parse($value)->toDateString() : null;
    }

    /**
     * Mutator for end date, needed to deal with ISO date strings
     * @param $value
     * @return void
     */
    public function setEndAttribute($value): void
    {
        $this->attributes['end'] = $value ? Carbon::parse($value)->toDateString() : null;
    }

}
