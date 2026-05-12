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

use App\Mail\Absence\AbsenceApproved;
use App\Mail\Absence\AbsenceChecked;
use App\Models\Leave\Absence;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: '/absence/{absence}/set-checked',
        operationId: 'setAbsenceChecked',
        summary: 'Mark an absence as checked',
        tags: ['Absence'],
        security: [['apiToken' => []]],
        parameters: [new OA\Parameter(name: 'absence', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated absence'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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

    /**
     * Delete absence without comment
     * @param Absence $absence
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Absence $absence)
    {
        $absence->delete();
        return response()->json();
    }

}
