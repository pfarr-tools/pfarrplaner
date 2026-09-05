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

namespace App\Models\Seating;

use App\Models\AbstractModel;
use App\Models\Location;
use App\Seating\RowBasedSeatingModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatingSection extends AbstractModel
{
    use HasFactory;

    protected static string $path = '';
    protected static string $prefix = 'bereich';
    protected static string $prefixPlural = 'bereiche';
    public static array $exceptRoutes = [
        'web' => ['index', 'show', 'create', 'edit', 'store', 'update', 'destroy'],
        'api' => ['show'],
    ];
    protected static $routes = [
        'web' => [
            'create' => [['GET', 'HEAD'], 'admin/ort/{location}/bereich'],
            'edit' => [['GET', 'HEAD'], 'admin/bereich/{modelId}'],
            'store' => [['POST'], 'admin/bereich'],
            'update' => [['PATCH', 'PUT'], 'admin/bereich/{modelId}'],
            'destroy' => [['DELETE'], 'admin/bereich/{modelId}'],
        ],
    ];
    public static array $validationRules = [
        'location_id' => 'required|int|exists:locations,id',
        'title' => 'required|string',
        'seating_model' => 'nullable|string',
        'priority' => 'nullable|int',
        'color' => 'nullable|string',
    ];

    protected $fillable = ['location_id', 'title', 'seating_model', 'priority', 'color'];

    protected $with = ['seatingRows'];
    protected $appends = ['modelClass'];

    public static function singularKey(): string
    {
        return 'seatingSection';
    }

    public static function pluralKey(): string
    {
        return 'seatingSections';
    }

    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Admin/Location/SeatingSectionEditor',
            default => parent::getVuePath($page),
        };
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('title', 'asc');
        });
    }

    public function getLabelAttribute(): string
    {
        return $this->title;
    }

    public function getModelClassAttribute(): string
    {
        return $this->attributes['seating_model'] ?? RowBasedSeatingModel::class;
    }

    public function fillDefaults(): array
    {
        return [
            'seating_model' => RowBasedSeatingModel::class,
            'priority' => 1,
            'color' => '',
        ];
    }

    /**
     * @return BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany
     */
    public function seatingRows()
    {
        return $this->hasMany(SeatingRow::class);
    }

    public function setSeatingModelAttribute($seatingModel)
    {
        if (!is_string($seatingModel)) {
            $seatingModel = get_class($seatingModel);
        }
        $this->attributes['seating_model'] = $seatingModel;
    }

}
