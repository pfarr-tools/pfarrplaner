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

namespace App\Listeners;

use App\Absence;
use App\Events\AbsenceApproved;
use App\Events\AbsenceUpdated;
use App\Events\OrderShipped;
use App\Mail\Absence\AbsenceChecked;
use App\Mail\Absence\AbsenceRequested;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendApprovalNotification
 * @package App\Listeners
 */
class SendAbsenceWorkflowNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param OrderShipped $event
     * @return void
     */
    public function handle(AbsenceUpdated $event)
    {
        Log::debug('SendAbsenceWorkflowNotification::handle()');
        // do nothing, if no workflow is defined
        if ((!$event->absence->user->vacationAdmins->count()) && !$event->absence->user->vacationApprovers->count()) {
            Log::debug('SendAbsenceWorkflowNotification abgebrochen, weil kein Workflow definiert ist.');
            return;
        }



        // check workflow status and send appropriate notifications
        $message = null;
        switch ($event->absence->workflow_status) {
            case Absence::STATUS_NEW:
                $recipients = $event->absence->user->vacationAdmins->reject(function ($user) {
                    // filter out users without email address
                    return empty($user->email);
                });
                $message = new AbsenceRequested($event->absence);
                break;
            case Absence::STATUS_CHECKED:
                $recipients = $event->absence->user->vacationApprovers->reject(function ($user) {
                    // filter out users without email address
                    return empty($user->email);
                });
                $message = new AbsenceChecked($event->absence);
                break;
            case Absence::STATUS_APPROVED:
                $recipients = collect([$event->absence->user])
                    ->merge($event->absence->user->vacationAdmins)
                    ->merge($event->absence->user->vacationApprovers)
                    ->reject(function ($user) {
                        // filter out users without email address
                        return empty($user->email);
                    });
                $message = new \App\Mail\Absence\AbsenceApproved($event->absence);
                break;
        }

        Log::debug('SendAbsenceWorkflowNotification hat '.$recipients->count().' Empfänger');


        //dd($recipients, $event->absence, $event->absence->user->vacationAdmins);

        if ($recipients->count() && (null !== $message)) {
            Log::debug('SendAbsenceWorkflowNotification: Sende Workflow-Update ('
                       .get_class($message)
                       .') an ' . $recipients->pluck('email')->unique()->join(', '));
            Mail::to($recipients)->send($message);
        }


    }
}
