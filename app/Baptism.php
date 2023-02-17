<?php
/**
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.de
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

namespace App;

use App\Calendars\SyncEngines\AbstractSyncEngine;
use App\Casts\EncryptedAttribute;
use App\DAV\DAVCalendarItem;
use App\DAV\HasDAVCalendarItems;
use App\Traits\HasAttachmentsTrait;
use App\Traits\HasCommentsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * Class Baptism
 * @package App
 */
class Baptism extends Model implements HasDAVCalendarItems
{
    use HasCommentsTrait;
    use HasAttachmentsTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'service_id',
        'candidate_name',
        'candidate_address',
        'candidate_zip',
        'candidate_city',
        'candidate_email',
        'candidate_phone',
        'pronoun_set',
        'first_contact_with',
        'first_contact_on',
        'registered',
        'signed',
        'appointment',
        'docs_ready',
        'docs_where',
        'city_id',
        'text',
        'notes',
        'processed',
        'needs_dimissorial',
        'dimissorial_issuer',
        'dimissorial_requested',
        'dimissorial_received',
        'dob',
        'birth_place'
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'first_contact_on',
        'appointment',
        'dimissorial_requested',
        'dimissorial_received',
    ];

    /** @var array */
    protected $casts = [
        'candidate_name' => EncryptedAttribute::class,
        'candidate_address' => EncryptedAttribute::class,
        'candidate_city' => EncryptedAttribute::class,
        'candidate_email' => EncryptedAttribute::class,
        'candidate_phone' => EncryptedAttribute::class,
        'text' => EncryptedAttribute::class,
        'birth_place' => EncryptedAttribute::class,
    ];

    protected $with = ['attachments'];

    protected $appends = ['hasRegistrationForm', 'dimissorialUrl'];

    /**
     * @return BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return bool
     */
    public function getHasRegistrationFormAttribute()
    {
        $found = false;
        foreach ($this->attachments as $attachment) {
            $found = $found || ($attachment->title == 'Anmeldeformular');
        }
        return $found;
    }

    /**
     * Generate a record for sync'ing to external calendars
     * @return array[]|null
     */
    public function getAdditionalEvents($config = [])
    {
        if (!$this->appointment) return null;

        $key = 'baptism_prep_'.$this->id;

        $contacts = [];
        if ($this->candidate_phone) $contacts[] = $this->candidate_phone;
        if ($this->candidate_email) $contacts[] = $this->candidate_email;

        $record = [
            'startDate' => $this->appointment->copy(),
            'endDate' => $this->appointment->copy()->addHour(1),
            'title' => 'Taufgespräch '.$this->candidate_name,
            'description' =>
                '<p>Taufe am '.$this->service->date->format('d.m.Y').' um '.$this->service->timeText().' ('.$this->service->locationText().')</p>'
                .'<p><a href="'.route('baptisms.edit', $this->id).'">Taufe im Pfarrplaner öffnen</a></p>'
                .(count($contacts) ? '<p>Kontakt: '.join(', ', $contacts).'</p>' : '')
                .AbstractSyncEngine::AUTO_WARNING,
            'location' => $this->candidate_address.', '.$this->candidate_zip.' '.$this->candidate_city,
            'categories' => ['Pfarrplaner','Taufgespräch','Amtskalender: Amtshandlungen'],
            'allDay' => true,
        ];
        return [$key => $record];
    }

    /**
     * Get the signed url for an online dimissorial
     * @return string
     */
    public function getDimissorialUrlAttribute()
    {
        return URL::signedRoute('dimissorial.show', ['type' => 'taufe', 'id' => $this->id]);
    }

    /**
     * Get all calendar items related to this service for CalDAV
     * @param bool $includeAlternate Include alternate calendar items
     * @param bool $includeRiteAnniversaries Include one-year anniversary for rites
     * @return DAVCalendarItem[]
     */
    public function getCalendarItems(bool $includeAlternate, bool $includeRiteAnniversaries): array
    {
        $items = [];
        if ($includeAlternate && ($this->appointment != null)) {
            $items[] = $this->getCalendarItem('prep');
        }
        if ($includeRiteAnniversaries) {
            $items[] = $this->getCalendarItem('anniv');
        }
        return $items;
    }


    /**
     * Get a single calendar item related to this service for CalDAV
     * @param string $itemType
     * @return DAVCalendarItem
     */
    public function getCalendarItem(string $itemType): DAVCalendarItem
    {

        $description = 'Taufe am '.$this->service->date->format('d.m.Y').' um '.$this->service->timeText().' ('.$this->service->locationText().")\n\n"
            .'Taufe im Pfarrplaner öffnen: '
            .route('baptisms.edit', $this->id)
            ."\n\nKontakt:\n"
            .trim(' - '.$this->candidate_name.': '.$this->candidate_phone.' '.$this->candidate_email)."\n"
            ."\n\n".DAVCalendarItem::AUTO_WARNING;

        switch ($itemType) {
            case 'prep':
                return new DAVCalendarItem(
                    $this,
                    'Taufgespräch '.$this->candidate_name,
                    $this->appointment->copy(),
                    $this->appointment->copy()->addHour(1),
                    $this->appointment_address ?? '',
                    $description,
                    'prep',
                    ['busyStatus' => 'BUSY'],
                    ['Taufgespräch','Amtskalender: Amtshandlungen']
                );
                break;
            case 'anniv':
                return new DAVCalendarItem(
                    $this,
                    '1. Jahrestag der Taufe von ' . $this->candidate_name,
                    $this->service->date->copy()->addYear(1),
                    $this->service->date->copy()->addYear(1)->addDay(1)->startOfDay(),
                    $this->candidate_address,
                    $description,
                    'anniv',
                    ['allDay' => true, 'busy' => false, 'busyStatus' => 'FREE'],
                    ['Jahrestag Taufe']
                );
                break;
        }
    }

}
