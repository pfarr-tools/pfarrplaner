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

namespace App\Mail;

use App\Models\People\User;
use App\Models\Service;
use App\Services\ServiceChangeFormatter;

/**
 * Class ServiceUpdated
 */
class ServiceUpdated extends AbstractServiceMailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Service $service, User $originatingUser, array $data)
    {
        parent::__construct($user, $service, $originatingUser, $data);
        $this->changed = $service->changed;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        /** @var array<string, array{original: mixed, changed: mixed}> $changes */
        $changes = $this->changed;
        $participants = [];
        if (isset($changes['participants'])) {
            foreach (['original', 'changed'] as $type) {
                $participantsOfType = $changes['participants'][$type] ?? [];
                foreach (is_array($participantsOfType) ? $participantsOfType : [] as $participant) {
                    if (! is_array($participant)) {
                        continue;
                    }
                    $pivot = $participant['pivot'] ?? null;
                    $category = is_array($pivot) ? ($pivot['category'] ?? null) : null;
                    switch ($category) {
                        case 'P':
                            $category = config('labels.pastor');
                            break;
                        case 'O':
                            $category = config('labels.organist');
                            break;
                        case 'M':
                            $category = config('labels.sacristan');
                            break;
                        case 'A':
                            $category = 'Andere Beteiligte';
                            break;
                    }
                    if (! is_string($category)) {
                        continue;
                    }
                    $participants[$category][$type][] = $participant;
                }
            }
        }
        $tmpParticipants = $participants;
        foreach ($tmpParticipants as $category => $p) {
            if (print_r($p['original'] ?? [], true) == print_r($p['changed'] ?? [], true)) {
                unset($participants[$category]);
            }
        }

        $displayChanges = ServiceChangeFormatter::format($this->service, $changes);

        $ics = view(
            'ical/ical',
            ['services' => [$this->service], 'token' => null, 'action' => null, 'key' => null]
        )->render();
        $icsTitle = 'GD '.(string) $this->service->date?->format('Ymd').' '.$this->service->timeText(
            false
        ).' '.$this->service->locationText().'.ics';

        return $this->subject(
            'Änderungen am einem Gottesdienst vom '.$this->service->dateText().', '.$this->service->timeText(
            ).' ('.$this->service->locationText().')'
        )
            ->view('mail.notifications.service-update')->with(
                [
                    'service' => $this->service,
                    'displayChanges' => $displayChanges,
                    'participants' => $participants,
                    'user' => $this->user,
                    'originatingUser' => $this->originatingUser,
                ]
            )->attachData(
                $ics,
                $icsTitle,
                [
                    'mime' => 'text/calendar',
                ]
            );
    }
}
