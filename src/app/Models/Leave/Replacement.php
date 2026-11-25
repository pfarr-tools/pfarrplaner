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
use App\Tools\StringTool;
use App\Traits\TracksDeletedByTrait;
use Carbon\Carbon;
use Database\Factories\Leave\ReplacementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Class Replacement
 */
class Replacement extends AbstractModel
{
    /** @use HasFactory<ReplacementFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksDeletedByTrait;

    protected static string $prefix = 'replacement';

    protected static string $prefixPlural = 'replacements';

    protected static string $path = '';

    public static array $exceptRoutes = [
        'web' => ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'],
        'api' => ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'],
    ];

    public static array $validationRules = [
        'absence_id' => 'required|integer|exists:absences,id',
        'from' => 'required|date',
        'to' => 'required|date|after_or_equal:from',
        'pool_id' => 'nullable|integer|exists:pools,id',
    ];

    /**
     * Massiv zuweisbare Felder der Vertretung.
     *
     * @var list<string>
     */
    protected $fillable = ['absence_id', 'from', 'to', 'pool_id'];

    /**
     * Umwandlung der Datumsfelder in Carbon-Instanzen.
     *
     * @var array<string, string>
     */
    protected $casts = ['from' => 'datetime', 'to' => 'datetime'];

    protected $with = ['pool'];

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Replacement $replacement) {
            if ($replacement->isForceDeleting()) {
                $replacement->users()->sync([]);
            }
        });
    }

    /**
     * @return BelongsTo<Absence, $this>
     */
    public function absence(): BelongsTo
    {
        return $this->belongsTo(Absence::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return BelongsTo<Pool, $this>
     */
    public function pool(): BelongsTo
    {
        return $this->belongsTo(Pool::class);
    }

    /**
     * @return string
     */
    public function toText()
    {
        $u = [];
        /** @var User $user */
        foreach ($this->users as $user) {
            $u[] = $user->lastName();
        }
        if (! $this->pool_id) {
            return implode(' | ', $u).' ('.StringTool::durationText($this->from, $this->to).')';
        } else {
            $texts = [];
            if (count($this->users)) {
                $texts = [implode(' | ', $u).' ('.StringTool::durationText($this->from, $this->to).')'];
            }
            $pool = $this->pool;
            if ($pool !== null && $pool->contact) {
                $texts[] = $pool->contact.($pool->office ? ', '.$pool->office : '').' [Kontakt für Pool "'.$pool->name.'"]';
            } else {
                $poolmasters = $this->relevantPoolmasters();
                foreach ($poolmasters as $poolmaster) {
                    $from = max(Carbon::parse($poolmaster->start)->startOfDay(), $this->from);
                    $to = min(Carbon::parse($poolmaster->end)->endOfDay(), $this->to);
                    $poolmasterPool = $poolmaster->pool;
                    if ($poolmasterPool === null) {
                        continue;
                    }

                    /** @var User $user */
                    $user = $poolmaster->getAttribute('user');
                    $texts[] = $user->fullName().' [Poolmaster:in "'.$poolmasterPool->name.'"]'
                        .' ('.StringTool::durationText($from, $to).')';
                }
            }

            return implode(' | ', $texts);
        }
    }

    /**
     * Gibt die Vertretungen je Person aufbereitet und nach Zeitraum sortiert zurück.
     *
     * @return array<string, array{from: Carbon, to: Carbon, period: string, user: User, poolmaster: mixed}>
     */
    public function toUserArray()
    {
        $users = [];
        foreach ($this->users as $user) {
            $users[$this->from->format('Ymd').$this->to->format('Ymd').$user->last_name.$user->id] = [
                'from' => $this->from,
                'to' => $this->to,
                'period' => StringTool::durationText($this->from, $this->to),
                'user' => $user,
                'poolmaster' => null,
            ];
        }

        $pool = $this->pool;
        if ($pool !== null && $pool->contact) {
            $users[$this->from->format('Ymd').$this->to->format('Ymd').$pool->name] = [
                'from' => $this->from,
                'to' => $this->to,
                'period' => StringTool::durationText($this->from, $this->to),
                'user' => new User([
                    'name' => $pool->contact,
                    'office' => $pool->office,
                    'phone' => $pool->phone,
                    'email' => $pool->email,
                ]),
                'poolmaster' => $pool->name,
            ];
        } else {
            $poolmasters = $this->relevantPoolmasters();
            foreach ($poolmasters as $poolmaster) {
                $from = max(Carbon::parse($poolmaster->start)->startOfDay(), $this->from);
                $to = min(Carbon::parse($poolmaster->end)->endOfDay(), $this->to);
                $poolmasterPool = $poolmaster->pool;
                if ($poolmasterPool === null) {
                    continue;
                }

                /** @var User $user */
                $user = $poolmaster->getAttribute('user');
                $users[$from->format('Ymd').$to->format(
                    'Ymd'
                ).$user->last_name.$user->id] = [
                    'from' => $from,
                    'to' => $to,
                    'period' => StringTool::durationText($from, $to),
                    'user' => $user,
                    'poolmaster' => $poolmasterPool->name,
                ];
            }
        }

        ksort($users);

        return $users;
    }

    /**
     * @param  mixed  $value
     */
    public function setFromAttribute($value): void
    {
        $this->attributes['from'] = $this->normalizeDateAttribute($value, false);
    }

    /**
     * @param  mixed  $value
     */
    public function setToAttribute($value): void
    {
        $this->attributes['to'] = $this->normalizeDateAttribute($value, true);
    }

    /**
     * Ermittelt die Poolmaster, deren Zeitraum diese Vertretung schneidet.
     *
     * Die eager geladene Beziehung 'user' ist Poolmaster::user() und verweist auf die
     * zugeordnete Person, 'pool' auf den Pool des Poolmasters. Beide Beziehungen werden
     * in toText() und toUserArray() weiterverwendet.
     *
     * @return Collection<int, Poolmaster>
     */
    protected function relevantPoolmasters()
    {
        $absentUserId = null;
        if ($this->relationLoaded('absence')) {
            $absence = $this->getRelation('absence');
            $absentUserId = $absence instanceof Absence ? $absence->user_id : null;
        } elseif ($this->absence_id) {
            $absentUserId = Absence::query()
                ->whereKey($this->absence_id)
                ->value('user_id');
        }

        return Poolmaster::with('user', 'pool')
            ->where('pool_id', $this->pool_id)
            ->where('start', '<=', $this->to)
            ->where('end', '>=', $this->from)
            ->when($absentUserId, function ($query) use ($absentUserId) {
                $query->where('user_id', '!=', $absentUserId);
            })
            ->get();
    }

    /**
     * @param  mixed  $value
     */
    protected function normalizeDateAttribute($value, bool $endOfDay = false): ?string
    {
        if (! $value) {
            return null;
        }
        if (! is_string($value) && ! is_int($value) && ! is_float($value) && ! $value instanceof \DateTimeInterface) {
            return null;
        }

        return Carbon::parse($value)->setTimezone('Europe/Berlin')->toDateString();
    }
}
