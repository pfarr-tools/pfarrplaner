<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

use App\Absence;
use App\Mail\Absence\AbsenceApproved;
use App\Mail\Absence\AbsenceChecked;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AbsenceController extends \App\Http\Controllers\Controller
{


    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Force checked flag on absence to on
     * @param Absence $absence Absence
     * @return JsonResponse
     */
    public function setChecked(Absence $absence)
    {
        $absence->update([
                             'admin_id' => Auth::user()->id,
                             'checked_at' => Carbon::now(),
                             'workflow_status' => Absence::STATUS_CHECKED,
                         ]);
        $absence->refresh();
        Mail::to($absence->user->vacationApprovers)->send(new AbsenceChecked($absence));
        return response()->json($absence);
    }

    /**
     * Force approved flag on absence to on
     * @param Absence $absence Absence
     * @return JsonResponse
     */
    public function setApproved(Absence $absence)
    {
        $absence->update([
                             'approver_id' => Auth::user()->id,
                             'approved_at' => Carbon::now(),
                             'workflow_status' => Absence::STATUS_APPROVED,
                         ]);
        $absence->refresh();
        Mail::to(
            collect([$absence->user])
                ->merge($absence->user->vacationAdmins)
                ->merge($absence->user->vacationApprovers)
                ->reject(function ($user) {
                    // filter out users without email address
                    return empty($user->email);
                })
        )
            ->send(new AbsenceApproved($absence));
        return response()->json($absence);
    }

}
