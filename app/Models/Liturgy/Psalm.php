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

namespace App\Models\Liturgy;


use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Psalm extends AbstractModel
{
    use HasFactory;

    protected static string $prefix = 'psalm';
    protected static string $prefixPlural = 'psalms';
    public static array $exceptRoutes = [
        'web' => ['show'],
        'api' => ['show', 'destroy'],
    ];
    protected static $routes = [
        'api' => [
            'index' => [['GET', 'HEAD'], 'liturgy/#p#'],
            'store' => [['POST'], 'liturgy/#p#'],
            'update' => [['PATCH', 'PUT'], 'liturgy/#p#/{modelId}'],
        ],
    ];
    public static array $validationRules = [
        'title' => 'required|string',
        'intro' => 'nullable|string',
        'text' => 'nullable|string',
        'copyrights' => 'nullable|string',
        'songbook' => 'nullable|string',
        'songbook_abbreviation' => 'nullable|string',
        'reference' => 'nullable|string',
    ];

    protected $fillable = ['title', 'intro', 'text', 'copyrights', 'songbook', 'songbook_abbreviation', 'reference'];

    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Admin/Psalm/PsalmEditor',
            default => parent::getVuePath($page),
        };
    }

    public function getLabelAttribute(): string
    {
        return trim((($this->songbook_abbreviation ?: $this->songbook ?: '') . ' ' . ($this->reference ?: '') . ' ' . $this->title));
    }

    protected static function boot()
    {
        parent::boot();

        // Order by sortable ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('songbook_abbreviation', 'asc');
            $builder->orderBy('reference', 'asc');
            $builder->orderBy('title', 'asc');
        });
    }

}
