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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Songbook extends AbstractModel
{
    use HasFactory;

    protected static string $prefix = 'liederbuch';
    protected static string $prefixPlural = 'liederbuecher';
    public static array $exceptRoutes = [
        'web' => ['show'],
        'api' => ['show', 'update', 'destroy'],
    ];
    public static array $validationRules = [
        'name' => 'required|string',
        'code' => 'required|string',
        'isbn' => 'nullable|string',
        'description' => 'nullable|string',
        'image' => 'nullable|string',
    ];
    public static $adminTitle = 'Liederbücher';
    public static $adminIcon = 'mdi mdi-book-music-outline';
    public static $adminGroup = 'Liturgie';

    protected $fillable = ['name', 'code', 'description', 'isbn', 'image'];

    /**
     * @param string $page
     * @return string
     */
    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Admin/Songbook/SongbookEditor',
            default => parent::getVuePath($page),
        };
    }

    /**
     * @return BelongsToMany
     */
    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class)->withPivot(['id', 'reference']);
    }

    /**
     * @return string
     */
    public function getImageField()
    {
        return 'image';
    }
}
