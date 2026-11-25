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

namespace App\Listeners;

use App\Events\AbsenceApproved;
use App\Models\Leave\Absence;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendApprovalNotification
 */
class SendApprovalNotification
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
     * Das Legacy-Event liefert die Abwesenheit als dynamische Eigenschaft.
     *
     * @return void
     */
    public function handle(AbsenceApproved $event)
    {
        /** @var AbsenceApproved&object{absence: Absence} $event */
        $owner = $event->absence->user;
        if ($owner === null) {
            return;
        }
        $users = clone $owner->approvers;
        $users->push($owner);

        if (config('app.dev')) {
            foreach ($users as $user) {
                $user->email = 'chris@toph.de';
            }
        }

        Log::debug('AbsenceApproved listener: Sende AbsenceApproved an '.$users->pluck('email')->unique->join(', '));
        Mail::to($users)->queue(new \App\Mail\Absence\AbsenceApproved($event->absence));
    }
}
