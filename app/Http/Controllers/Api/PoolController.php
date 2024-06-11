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

namespace App\Http\Controllers\Api;

use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\Leave\Poolmaster;
use App\Models\People\User;
use App\Models\Service;
use Carbon\Carbon;

class PoolController extends AbstractApiCRUDController
{

    protected string $modelClass = Pool::class;

    public function poolmasters(Pool $pool, $date)
    {
        $start = Carbon::parse($date . '-01')->startOfDay();
        $end = $start->copy()->endOfMonth();
        $days = Absence::getDaysForPlanner($start->copy());

        /*
        $poolmasters = Poolmaster::where('start', '>=', $start)
            ->where('end', '<=', $end)
            ->get();

        */

        foreach ($days as $dayKey => $day) {
            $days[$dayKey]['show'] = true;
            $days[$dayKey]['absent'] = false;
            $days[$dayKey]['duration'] = 0;
            $days[$dayKey]['absence'] = null;
            $days[$dayKey]['busy'] = false;
            $days[$dayKey]['services'] = 0;
        }


        return response()->json($days);
    }

    public function mastered(User $user, $date)
    {
        $date = Carbon::parse($date);
        $mastered = Poolmaster::with(['pool', 'pool.users', 'pool.cities'])
            ->where('user_id', $user->id)
            ->where('start', '<=', $date->startOfDay())
            ->where('end', '>=', $date->copy()->subdays(1)->endOfDay())
            ->get();

        $startOfRelevantPeriod = $date->copy()->subDays(4)->startOfDay();
        $endOfRelevantPeriod = $date->copy()->addDays(4)->endOfDay();
        $period = [$startOfRelevantPeriod, $endOfRelevantPeriod];

        $users = [];
        foreach ($mastered as $poolmaster) {
            foreach ($poolmaster->pool->users as $user) {
                if (!isset($users[$user->id])) {
                    $users[$user->id] = [
                        'absent' => Absence::where('user_id', $user->id)
                                ->where('from', '<=', $date)
                                ->where('to', '>=', $date)
                                ->count() > 0,
                        'services' => Service::userParticipates($user)
                            ->between($startOfRelevantPeriod, $endOfRelevantPeriod)
                            ->ordered()
                            ->get(),
                        'absences' => Absence::where('user_id', $user->id)
                                ->where('from', '<=', $endOfRelevantPeriod)
                                ->where('to', '>=', $startOfRelevantPeriod)
                                ->get(),
                    ];
                }
            }
        }
        return response()->json(compact( 'users', 'period'));
    }

}
