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

namespace App\Observers;

use App\Models\Calendar\Occurence;
use App\Models\Service;
use Carbon\Carbon;
use Recurr\Exception\InvalidRRule;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;

class EventRecurrenceObserver
{
    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        //
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        /*
         * Here, the logic for rrule updates (check $service->getOriginal('rrule')) must be located:
         * - Check if rrule is updated
         * - Delete all occurrences with the old rrule
         * - Calculate new occurences up to sensible limit
         * - At a minimum, set date as first occurence
         */

        /*
        if (($service->getOriginal('rrule') == $service->rrule)
            && ($service->getOriginal('date' == $service->date) && ($service->getOriginal('end') == $service->end))
        ) {
            return;
        }
        */

//        if ($service->getOriginal('rrule') != $service->rrule) {
            Occurence::where('service_id', $service->id)->delete();
//        }

        // create default occurence
        Occurence::create([
            'service_id' => $service->id,
            'start' => $service->date,
            'end' => $service->date->copy()->addMinutes($service->duration),
                          ]);

//        if (($service->rrule) && ($service->getOriginal('rrule') != $service->rrule)) {
        if ($service->rrule) {
            try {
                $rrule = new Rule($service->rrule);
            } catch (InvalidRRule $exception) {
                return;
            }
            $rrule->setTimezone('Europe/Berlin');
            $rrule->setStartDate($service->date->copy()->setTimezone('Europe/Berlin'));
            $rrule->setEndDate($service->date->copy()->setTimezone('Europe/Berlin')->addMinutes($service->duration));

            // sanity check: no more than 5 years, no more than 5000 occurrences
            if ((!$rrule->getCount()) && (!$rrule->getUntil())) {
                $rrule->setUntil(Carbon::now()->startOfYear()->addYears(5));
            }

            $transformer = new ArrayTransformer((new ArrayTransformerConfig())->setVirtualLimit(5000));
            $recurrences = $transformer->transform($rrule);

            foreach ($recurrences as $recurrence) {
                if (!($recurrence->getStart() == $service->date)) {
                    Occurence::create([
                                          'service_id' => $service->id,
                                          'start' => Carbon::instance($recurrence->getStart())->setTimezone('UTC'),
                                          'end' => Carbon::instance($recurrence->getEnd())->setTimezone('UTC'),
                                      ]);
                }
            }
        }
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        //
    }

    /**
     * Handle the Service "restored" event.
     */
    public function restored(Service $service): void
    {
        //
    }

    /**
     * Handle the Service "force deleted" event.
     */
    public function forceDeleted(Service $service): void
    {
        //
    }
}
