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

namespace App\Liturgy\Replacement;

use App\Imports\EventCalendarImport;
use App\Imports\OPEventsImport;
use App\Models\Service;
use App\Tools\StringTool;
use Carbon\Carbon;

class TermineReplacer extends AbstractReplacer
{
    protected $description = 'Setzt automatisch die tagesaktuelle Terminvorschau ein.';

    protected function getReplacementText(): string
    {
        $service = $this->service;
        $serviceDate = $service->trueDate();
        $city = $service->city;
        if ($city === null) {
            return '';
        }
        $lastWeek = Carbon::parse($serviceDate->format('Y-m-d').' last Sunday');
        $nextWeek = Carbon::parse($serviceDate->format('Y-m-d').' next Sunday');

        $services = Service::with(['day', 'location'])
            ->displayable()
            ->whereDoesntHave('funerals')
            ->whereHas(
                'day',
                function ($query) use ($service, $serviceDate, $city, $nextWeek) {
                    $query->where('date', '>=', $serviceDate);
                    $query->where('date', '<=', $nextWeek);
                    $query->where('city_id', $city->id);
                    $query->where('id', '!=', $service->id);
                }
            )
            ->get();

        $events = [];

        $calendar = new EventCalendarImport($city->public_events_calendar_url);
        $events = $calendar->mix($events, $serviceDate, $nextWeek, true);

        $events = Service::mix($events, $services, $serviceDate, $nextWeek);

        $op = new OPEventsImport($city);
        $events = $op->mix($events, $serviceDate, $nextWeek);

        $text = '';

        $ctr = 0;
        $lastDay = $nextWeek->format('Ymd');
        foreach ($events as $eventsArray) {
            foreach ($eventsArray as $event) {
                $eventStart = is_array($event) ? $event['start'] : $event->trueDate();
                $dateFormat = $ctr ? '%A, %d. %B' : '%A, %d. %B %Y';
                $done = false;

                if (is_array($event)) {
                    if ($serviceDate->format('Ymd') == $eventStart->format('Ymd')) {
                        if ($event['allDay']) {
                            $text .= $this->renderParagraph($event['title']);
                            $done = true;
                        }
                    }
                }

                if (! $done) {
                    if ($lastDay != $eventStart->format('Ymd')) {
                        $text .= $this->renderParagraph();
                        if ($nextWeek->format('Ymd') == $eventStart->format('Ymd')) {
                            $text .= $this->renderParagraph('Vorschau');
                        }

                        $text .= $this->renderParagraph(
                            ($serviceDate->format('Ymd') == $eventStart->format('Ymd')) ?
                                'Heute' : (string) strftime($dateFormat, $eventStart->getTimestamp())
                        );
                    }

                    if (is_array($event)) {
                        $text .= $this->renderParagraph(
                            (isset($event['allDay']) && $event['allDay']) ? '' : strftime(
                                '%H.%M Uhr',
                                $eventStart->getTimestamp()
                            )."\t".trim(
                                StringTool::sanitizeXMLString(
                                    $event['title']
                                ).' ('.StringTool::sanitizeXMLString($event['place']).')'
                            )
                        );
                    } else {
                        $description = $event->descriptionText();
                        $description = $description ? ' mit '.$description : '';
                        // take care of ampersands
                        $description = (string) preg_replace('/&(?![A-Za-z0-9#]{1,7};)/', '&amp;', $description);
                        $text .= $this->renderParagraph(
                            $event->timeText(true, '.')."\t".trim(
                                ($event->title ?: 'Gottesdienst').$description.' ('.$event->locationText().')'
                            )
                        );

                        // add children's church
                        if ($event->cc) {
                            $childChurchStart = Carbon::createFromFormat(
                                'Y-m-d H:i',
                                $event->trueDate()->format('Y-m-d').' '.($event->cc_alt_time ?? $event->time)
                            );
                            if ($childChurchStart !== null) {
                                $text .= $this->renderParagraph(
                                    $childChurchStart->isoFormat('HH.mm \U\h\r')."\t".'Kinderkirche'.' ('.($event->cc_location ?? $event->locationText()).')'
                                );
                            }
                        }
                    }

                    if ((isset($event['allDay']) && $event['allDay'])) {
                        $text .= $this->renderParagraph();
                    }

                    $lastDay = $eventStart->format('Ymd');
                }

                $ctr++;
            }
        }

        return $text;
    }
}
