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

namespace App\Http\Resources\Calendar;

use App\Models\Leave\Absence;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $date = Carbon::parse($this->resource);
        $liturgy = LiturgyService::getLiturgyInfoByDate($date);

        // use raw query to avoid any relationships
        $services = DB::select('SELECT id, slug, date, city_id FROM services WHERE date(`date`) = ? ORDER BY date', [$this->resource]);

        $absences = Absence::setEagerLoads([])->with('user', function ($query) {
            $query->setEagerLoads([])->with([]);
        })->byPeriod($date, $date->copy()->endOfDay())
            ->visibleForUser(Auth::user())
            ->showInCalendar()
            ->get();

        return [
            'date' => $this->resource,
            'liturgy' => isset($liturgy[0]) ? [
                'title' => $liturgy[0]['title'],
                'litColor' => $liturgy[0]['litColor'],
            ] : [],
            'absences' => CalendarAbsenceResource::collection($absences),
            'services' => new CalendarServiceIdCollectionResource($services),
        ];
    }
}
