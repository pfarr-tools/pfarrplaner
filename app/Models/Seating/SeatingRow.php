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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatingRow extends AbstractModel
{
    use HasFactory;

    protected static string $path = '';
    protected static string $prefix = 'reihe';
    protected static string $prefixPlural = 'reihen';
    public static array $exceptRoutes = [
        'web' => ['index', 'show', 'create', 'edit', 'store', 'update', 'destroy'],
        'api' => ['show'],
    ];
    protected static $routes = [
        'web' => [
            'create' => [['GET', 'HEAD'], 'admin/ort/{location}/reihe'],
            'edit' => [['GET', 'HEAD'], 'admin/reihe/{modelId}'],
            'store' => [['POST'], 'admin/reihe'],
            'update' => [['PATCH', 'PUT'], 'admin/reihe/{modelId}'],
            'destroy' => [['DELETE'], 'admin/reihe/{modelId}'],
        ],
    ];
    public static array $validationRules = [
        'seating_section_id' => 'required|int|exists:seating_sections,id',
        'title' => 'required|regex:/[0-9]+/i',
        'divides_into' => 'nullable|int',
        'seats' => 'nullable|int',
        'spacing' => 'nullable|int',
        'split' => 'nullable|string|regex:/^((\\d+)(,\\s*\\d+)+)$/i',
        'color' => 'nullable|string',
    ];

    protected $fillable = ['seating_section_id', 'title', 'seats', 'divides_into', 'spacing', 'split', 'color'];

    public $bookings = [];

    public static function singularKey(): string
    {
        return 'seatingRow';
    }

    public static function pluralKey(): string
    {
        return 'seatingRows';
    }

    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Admin/Location/SeatingRowEditor',
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

    public function fillDefaults(): array
    {
        return [
            'seats' => 1,
            'divides_into' => 1,
            'spacing' => 0,
            'split' => '',
            'color' => '',
        ];
    }


    /**
     * @return BelongsTo
     */
    public function seatingSection()
    {
        return $this->belongsTo(SeatingSection::class);
    }

    /**
     * Get CSS style for background color
     * @return mixed|string
     */
    public function getCSS()
    {
        $color = $this->color ?: $this->seatingSection->color ?: '';
        if ($color != '') {
            $color = 'background-color: ' . $color;
        }
        return $color;
    }

}
