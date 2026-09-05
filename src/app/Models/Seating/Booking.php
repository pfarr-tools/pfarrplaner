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

use App\Casts\EncryptedAttribute;
use App\Models\AbstractModel;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends AbstractModel
{
    use HasFactory;

    protected static string $path = '';
    protected static string $prefix = 'booking';
    protected static string $prefixPlural = 'bookings';
    public static array $exceptRoutes = [
        'web' => ['index', 'create', 'show', 'store', 'edit', 'update', 'destroy'],
        'api' => ['index', 'show', 'store', 'update', 'destroy'],
    ];
    protected static $routes = [
        'web' => [
            'store' => [['POST'], 'anmeldung'],
            'edit' => [['GET', 'HEAD'], 'anmeldung/{modelId}'],
            'update' => [['PATCH', 'PUT'], 'anmeldung/{modelId}'],
            'destroy' => [['DELETE'], 'anmeldung/{modelId}'],
        ],
        'api' => [
            'destroy' => [['DELETE'], 'booking/{modelId}'],
        ],
    ];
    public static array $validationRules = [
        'service_id' => 'required|int|exists:services,id',
        'code' => 'nullable|string',
        'name' => 'required|string',
        'first_name' => 'nullable|string',
        'contact' => 'required|string',
        'number' => ['required', 'int', 'min:1'],
        'fixed_seat' => ['nullable', 'string'],
        'override_seats' => 'nullable|int',
        'override_split' => 'nullable|string',
        'email' => 'nullable|email',
    ];

    protected $guarded = [];
    protected $fillable = [
        'service_id',
        'code',
        'name',
        'first_name',
        'contact',
        'number',
        'fixed_seat',
        'override_seats',
        'override_split',
        'email',
    ];

    protected $casts = [
        'name' => EncryptedAttribute::class,
        'first_name' => EncryptedAttribute::class,
        'contact' => EncryptedAttribute::class,
        'email' => EncryptedAttribute::class,
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot() {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('number', 'desc');
        });
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function getLabelAttribute(): string
    {
        return trim(($this->name ?? '') . (($this->first_name ?? '') ? ', ' . $this->first_name : ''));
    }

    public function fillDefaults(): array
    {
        return [
            'name' => '',
            'first_name' => '',
            'contact' => '',
            'email' => '',
            'number' => 1,
            'fixed_seat' => '',
            'override_seats' => '',
            'override_split' => '',
        ];
    }

    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Service/Registrations/BookingEditor',
            default => parent::getVuePath($page),
        };
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public static function createCode(): string
    {
        return str_pad(dechex(rand(0x100000, 0xFFFFFF)), 6, 0, STR_PAD_LEFT);
    }

}
