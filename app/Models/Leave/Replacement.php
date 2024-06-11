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

use App\Models\People\User;
use App\Tools\StringTool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Replacement
 * @package App
 */
class Replacement extends Model
{

    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['absence_id', 'from', 'to', 'pool_id'];
    /**
     * @var string[]
     */
    protected $casts = ['from' => 'datetime', 'to' => 'datetime'];

    protected $with = ['pool'];

    /**
     * @return BelongsTo
     */
    public function absence()
    {
        return $this->belongsTo(Absence::class);
    }

    /**
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return BelongsTo
     */
    public function pool()
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
        if (!$this->pool_id) {
            return join(' | ', $u) . ' (' . StringTool::durationText($this->from, $this->to) . ')';
        } else {
            $texts = [];
            if (count($this->users)) $texts = [join(' | ', $u) . ' (' . StringTool::durationText($this->from, $this->to) . ')'];

            $poolmasters = Poolmaster::with('user')
                ->where('pool_id', $this->pool_id)
                ->where('start', '<=', $this->to)
                ->where('end', '>=', $this->from)
                ->get();
            foreach ($poolmasters as $poolmaster) {
                $from = max(Carbon::parse($poolmaster->start.' 0:00:00'), $this->from);
                $to = min(Carbon::parse($poolmaster->end.' 23:59:59'), $this->to);

                $texts[] = $poolmaster->user->name.' [Poolmaster "'.$poolmaster->pool->name.'"]'
                    . ' (' . StringTool::durationText($from, $to) . ')';
            }

            return join(' | ', $texts);
        }
    }
}
