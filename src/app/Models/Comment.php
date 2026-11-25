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

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Models\People\User;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Comment
 */
class Comment extends AbstractModel
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected static string $prefix = 'kommentar';

    protected static string $prefixPlural = 'kommentare';

    protected static string $path = '';

    public static array $exceptRoutes = [
        'web' => ['index', 'create', 'show', 'edit', 'update', 'destroy', 'store'],
        'api' => ['index', 'create', 'show', 'edit', 'update', 'destroy', 'store'],
    ];

    protected static $routes = [
        'web' => [
            'store' => [['POST'], '###'],
            'destroy' => [['DELETE'], '###/{modelId}'],
        ],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'body',
        'private',
        'commentable_id',
        'commentable_type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'body' => EncryptedAttribute::class,
    ];

    protected $with = ['user'];

    /**
     * Get all of the owning commentable models.
     *
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return string
     */
    public function getLabelAttribute(): string
    {
        return (string) ($this->body ?? '');
    }
}
