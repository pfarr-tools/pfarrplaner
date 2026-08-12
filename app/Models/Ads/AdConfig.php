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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the* GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Models\Ads;

use App\Models\AbstractModel;
use App\Models\Service;
use App\Traits\TracksDeletedByTrait;
use Database\Factories\AdConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdConfig extends AbstractModel
{
    /** @use HasFactory<\Database\Factories\AdConfigFactory> */
    use HasFactory;
    use TracksDeletedByTrait;
    use SoftDeletes;

    protected static string $prefix = 'adconfig';
    protected static string $prefixPlural = 'adconfigs';
    protected static string $path = '';
    public static array $exceptRoutes = [
        'web' => ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'],
        'api' => ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'],
    ];
    public static array $validationRules = [
        'service_id' => 'required|integer|exists:services,id',
        'slug' => 'required|string|max:255',
        'offset' => 'nullable|integer|min:0',
        'ad_text' => 'nullable|string',
    ];

    protected $fillable = ['service_id', 'slug', 'offset', 'ad_text'];

    protected static function newFactory(): AdConfigFactory
    {
        return AdConfigFactory::new();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
