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

namespace App\Models;

use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DiaryEntry extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'service_id', 'event_id', 'date', 'title', 'category'];
    protected $casts = ['date' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return mixed|null
     */
    public function getDateAttribute()
    {
        if ($this->attributes['date']) {
            return $this->attributes['date'];
        }
        if ($this->service_id) {
            return $this->service->date;
        }
        return null;
    }

    /**
     * Create a new DiaryEntry from an existing service
     *
     * @param string $category Target category
     * @param Service $service Service record
     * @return DiaryEntry New DiaryEntry record
     */
    public static function createFromService(Service $service,  $category)
    {
        return self::create([
                                'date' => $service->date->copy()->setTimeZone('Europe/Berlin'),
                                'title' => $service->titleText(false),
                                'user_id' => Auth::user()->id,
                                'service_id' => $service->id,
                                'category' => $category,
                            ]);
    }

    /**
     * Create a new DiaryEntry from a calendar event
     *
     * @param string $category Target category
     * @param array $eventData Event data
     * @return DiaryEntry New DiaryEntry record
     */
    public static function createFromEvent($eventData,  $category)
    {
        if ($eventData['TimeZone'] == 'UTC') {
            $tzCode = 'UTC';
        } else {
            preg_match('/[\+\-]\d\d\:\d\d/', $eventData['TimeZone'], $matches);
            $tzCode = $matches[0];
        }
        return self::create([
                                'date' => Carbon::parse($eventData['Start'], $tzCode)->setTimezone('Europe/Berlin'),
                                'title' => $eventData['Subject'],
                                'user_id' => Auth::user()->id,
                                'service_id' => null,
                                'event_id' => $eventData['UID'],
                                'category' => $category,
                            ]);
    }

}
