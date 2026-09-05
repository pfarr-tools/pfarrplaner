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

namespace App\Models\Rites;

use App\Calendars\AbstractCalendarItem;
use App\Calendars\SyncEngines\AbstractSyncEngine;
use App\Casts\EncryptedAttribute;
use App\DAV\DAVCalendarItem;
use App\DAV\HasDAVCalendarItems;
use App\Models\AbstractModel;
use App\Models\Service;
use App\Traits\HasAttachmentsTrait;
use App\Traits\HasCommentsTrait;
use App\Traits\TracksDeletedByTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

/**
 * Class Funeral
 * @package App
 */
class Funeral extends AbstractModel implements HasDAVCalendarItems
{
    use HasCommentsTrait, HasAttachmentsTrait, HasFactory, TracksDeletedByTrait, SoftDeletes;

    protected static string $prefix = 'funeral';
    protected static string $prefixPlural = 'funerals';
    protected static string $path = '';
    public static array $exceptRoutes = [
        'web' => ['index', 'show', 'store', 'create', 'edit', 'update', 'destroy'],
        'api' => ['index', 'show', 'store', 'update', 'destroy'],
    ];
    protected static $routes = [
        'web' => [
            'create' => [['GET', 'HEAD'], '#p#/create'],
            'edit' => [['GET', 'HEAD'], '#p#/{modelId}'],
            'update' => [['PATCH', 'PUT'], '#p#/{modelId}'],
            'destroy' => [['DELETE'], '#p#/{modelId}'],
        ],
    ];
    public static array $validationRules = [
        'service_id' => 'required|int|exists:services,id',
        'buried_name' => 'required|string',
        'buried_address' => 'nullable|string',
        'buried_zip' => 'nullable|zip',
        'buried_city' => 'nullable|string',
        'pronoun_set' => 'nullable|string',
        'text' => 'nullable|string',
        'type' => 'nullable|string',
        'relative_name' => 'nullable|string',
        'relative_address' => 'nullable|string',
        'relative_zip' => 'nullable|zip',
        'relative_city' => 'nullable|string',
        'relative_contact_data' => 'nullable|string',
        'wake_location' => 'nullable|string',
        'wake' => 'nullable|date_format:d.m.Y',
        'dob' => 'nullable|date_format:d.m.Y',
        'dod' => 'nullable|date_format:d.m.Y',
        'announcement' => 'nullable|date_format:d.m.Y',
        'appointment' => 'nullable|date_format:d.m.Y H:i',
        'spouse' => 'nullable|string',
        'parents' => 'nullable|string',
        'children' => 'nullable|string',
        'further_family' => 'nullable|string',
        'baptism' => 'nullable|string',
        'confirmation' => 'nullable|string',
        'undertaker' => 'nullable|string',
        'eulogies' => 'nullable|string',
        'notes' => 'nullable|string',
        'announcements' => 'nullable|string',
        'childhood' => 'nullable|string',
        'profession' => 'nullable|string',
        'family' => 'nullable|string',
        'further_life' => 'nullable|string',
        'faith' => 'nullable|string',
        'events' => 'nullable|string',
        'character' => 'nullable|string',
        'death' => 'nullable|string',
        'life' => 'nullable|string',
        'attending' => 'nullable|string',
        'quotes' => 'nullable|string',
        'spoken_name' => 'nullable|string',
        'professional_life' => 'nullable|string',
        'birth_place' => 'nullable|string',
        'death_place' => 'nullable|string',
        'processed' => 'nullable|integer|between:0,1',
        'needs_dimissorial' => 'nullable|integer|between:0,1',
        'dimissorial_issuer' => 'nullable|string',
        'dimissorial_requested' => 'nullable|date_format:d.m.Y',
        'dimissorial_received' => 'nullable|date_format:d.m.Y',
        'birth_name' => 'nullable|string',
        'appointment_address' => 'nullable|string',
        'baptism_date' => 'nullable|date_format:d.m.Y',
        'confirmation_date' => 'nullable|date_format:d.m.Y',
        'confirmation_text' => 'nullable|string',
        'wedding_date' => 'nullable|date_format:d.m.Y',
        'wedding_text' => 'nullable|string',
        'dod_spouse' => 'nullable|date_format:d.m.Y',
    ];
    public static $relationsForEditor = ['attachments', 'service.pastors', 'service.sermon'];

    /**
     * @var string[]
     */
    protected $fillable = [
        'service_id',
        'buried_name',
        'buried_address',
        'buried_zip',
        'buried_city',
        'pronoun_set',
        'text',
        'announcement',
        'type',
        'wake',
        'wake_location',
        'relative_name',
        'relative_address',
        'relative_zip',
        'relative_city',
        'relative_contact_data',
        'appointment',
        'dob',
        'dod',
        'spouse',
        'parents',
        'children',
        'further_family',
        'baptism',
        'confirmation',
        'undertaker',
        'eulogies',
        'notes',
        'announcements',
        'childhood',
        'profession',
        'family',
        'further_life',
        'faith',
        'events',
        'character',
        'death',
        'life',
        'attending',
        'quotes',
        'spoken_name',
        'professional_life',
        'birth_place',
        'death_place',
        'processed',
        'needs_dimissorial',
        'dimissorial_issuer',
        'dimissorial_requested',
        'dimissorial_received',
        'birth_name',
        'appointment_address',
        'baptism_date',
        'confirmation_date',
        'confirmation_text',
        'wedding_date',
        'wedding_text',
        'dod_spouse',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'buried_name' => EncryptedAttribute::class,
        'buried_address' => EncryptedAttribute::class,
        'buried_zip' => EncryptedAttribute::class,
        'buried_city' => EncryptedAttribute::class,
        'relative_name' => EncryptedAttribute::class,
        'relative_address' => EncryptedAttribute::class,
        'relative_zip' => EncryptedAttribute::class,
        'relative_city' => EncryptedAttribute::class,
        'relative_contact_data' => EncryptedAttribute::class,
        'spouse' => EncryptedAttribute::class,
        'parents' => EncryptedAttribute::class,
        'children' => EncryptedAttribute::class,
        'further_family' => EncryptedAttribute::class,
        'baptism' => EncryptedAttribute::class,
        'confirmation' => EncryptedAttribute::class,
        'eulogies' => EncryptedAttribute::class,
        'notes' => EncryptedAttribute::class,
        'announcements' => EncryptedAttribute::class,
        'childhood' => EncryptedAttribute::class,
        'profession' => EncryptedAttribute::class,
        'family' => EncryptedAttribute::class,
        'further_life' => EncryptedAttribute::class,
        'faith' => EncryptedAttribute::class,
        'events' => EncryptedAttribute::class,
        'character' => EncryptedAttribute::class,
        'death' => EncryptedAttribute::class,
        'life' => EncryptedAttribute::class,
        'attending' => EncryptedAttribute::class,
        'quotes' => EncryptedAttribute::class,
        'spoken_name' => EncryptedAttribute::class,
        'professional_life' => EncryptedAttribute::class,
        'birth_place' => EncryptedAttribute::class,
        'death_place' => EncryptedAttribute::class,
        'birth_name' => EncryptedAttribute::class,
        'announcement' => 'datetime',
        'wake' => 'datetime',
        'appointment' => 'datetime',
        'dob' => 'datetime',
        'dod' => 'datetime',
        'dimissorial_requested' => 'datetime',
        'dimissorial_received' => 'datetime',
        'baptism_date' => 'datetime',
        'confirmation_date' => 'datetime',
        'wedding_date' => 'datetime',
        'dod_spouse' => 'datetime',
    ];

    protected $appends = ['age', 'dimissorialUrl'];
    protected $with = ['attachments'];

    /**
     * @param string $page
     * @return string
     */
    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Rites/FuneralEditor',
            default => parent::getVuePath($page),
        };
    }

    /**
     * @return array
     */
    public function fillDefaults(): array
    {
        return [
            'buried_name' => '',
            'buried_address' => '',
            'buried_zip' => '',
            'buried_city' => '',
            'pronoun_set' => '',
            'text' => '',
            'type' => 'Erdbestattung',
            'relative_name' => '',
            'relative_address' => '',
            'relative_zip' => '',
            'relative_city' => '',
            'relative_contact_data' => '',
            'wake_location' => '',
            'spouse' => '',
            'parents' => '',
            'children' => '',
            'further_family' => '',
            'baptism' => '',
            'confirmation' => '',
            'undertaker' => '',
            'eulogies' => '',
            'notes' => '',
            'announcements' => '',
            'childhood' => '',
            'profession' => '',
            'family' => '',
            'further_life' => '',
            'faith' => '',
            'events' => '',
            'character' => '',
            'death' => '',
            'life' => '',
            'attending' => '',
            'quotes' => '',
            'spoken_name' => '',
            'professional_life' => '',
            'birth_place' => '',
            'death_place' => '',
            'processed' => 0,
            'needs_dimissorial' => 0,
            'dimissorial_issuer' => '',
            'birth_name' => '',
            'appointment_address' => '',
            'confirmation_text' => '',
            'wedding_text' => '',
        ];
    }

    /**
     * @return string
     */
    public function getLabelAttribute(): string
    {
        return $this->buried_name ?: '';
    }

    /**
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return string
     */
    public function age()
    {
        if (($this->dob) && ($this->dod)) {
            return abs((int)$this->dod->diffInYears($this->dob));
        }
        return '';
    }

    /**
     * @param $date
     */
    public function setDobAttribute($date)
    {
        $this->attributes['dob'] = $this->normalizeDateAttribute($date);
    }

    /**
     * @param $date
     */
    public function setDodAttribute($date)
    {
        $this->attributes['dod'] = $this->normalizeDateAttribute($date);
    }

    /**
     * @param $date
     */
    public function setAnnouncementAttribute($date)
    {
        $this->attributes['announcement'] = $this->normalizeDateAttribute($date);
    }

    /**
     * @param $date
     */
    public function setWakeAttribute($date)
    {
        $this->attributes['wake'] = $this->normalizeDateAttribute($date);
    }

    /**
     * @param $date
     */
    public function setAppointmentAttribute($date)
    {
        $this->attributes['appointment'] = $this->normalizeDateTimeAttribute($date);
    }

    /**
     * @param mixed $date
     * @return Carbon|string|null
     */
    protected function normalizeDateAttribute($date)
    {
        if (($date === null) || ($date === '')) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date;
        }

        return Carbon::createFromFormat('d.m.Y', $date);
    }

    /**
     * @param mixed $date
     * @return Carbon|string|null
     */
    protected function normalizeDateTimeAttribute($date)
    {
        if (($date === null) || ($date === '')) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date;
        }

        return Carbon::createFromFormat('d.m.Y H:i', $date);
    }

    public function getAgeAttribute()
    {
        return $this->age();
    }

    /**
     * Generate a record for sync'ing to external calendars
     * @return array[]|null
     */
    public function getAdditionalEvents($config = [])
    {
        $records = [];
        if ($this->appointment) {
            $records['funeral_prep_' . $this->id] = [
                'startDate' => $this->appointment->copy(),
                'endDate' => $this->appointment->copy()->addHour(1),
                'title' => 'Trauergespräch ' . $this->buried_name,
                'description' =>
                    '<p>' . $this->type . ' am ' . $this->service->date->format(
                        'd.m.Y'
                    ) . ' um ' . $this->service->timeText() . ' (' . $this->service->locationText() . ')</p>'
                    . '<p><a href="' . route(
                        'funerals.edit',
                        $this->id
                    ) . '">Bestattung im Pfarrplaner öffnen</a></p>'
                    . '<p>Kontakt: ' . nl2br($this->relative_contact_data) . '</p>'
                    . AbstractSyncEngine::AUTO_WARNING,
                'location' => $this->appointment_address,
                'categories' => ['Pfarrplaner', 'Trauergespräch', 'Amtskalender: Seelsorge/Diakonie'],
            ];
        }

        if ($config['include_rite_anniversaries']) {
            $records['funeral_anniversary_' . $this->id] = [
                'startDate' => $this->service->date->copy()->addYear(1),
                'endDate' => $this->service->date->copy()->addYear(1),
                'title' => '1. Jahrestag der Beerdigung von ' . $this->buried_name,
                'description' =>
                    '<p>' . $this->type . ' am ' . $this->service->date->format(
                        'd.m.Y'
                    ) . ' um ' . $this->service->timeText() . ' (' . $this->service->locationText() . ')</p>'
                    . '<p><a href="' . route('funerals.edit', $this->id) . '">Bestattung im Pfarrplaner öffnen</a></p>'
                    . '<p>Kontakt: ' . nl2br($this->relative_contact_data) . '</p>'
                    . AbstractSyncEngine::AUTO_WARNING,
                'location' => $this->appointment_address,
                'categories' => ['Pfarrplaner', 'Jahrestag Beerdigung'],
                'isAllDayEvent' => true,
                'freeBusy' => AbstractCalendarItem::STATUS_FREE,
            ];
        }

        return $records;
    }

    /**
     * Get the signed url for an online dimissorial
     * @return string
     */
    public function getDimissorialUrlAttribute()
    {
        return URL::signedRoute('dimissorial.show', ['type' => 'beerdigung', 'id' => $this->id]);
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
        switch ($itemType) {
            case 'prep':
                return new DAVCalendarItem(
                    $this,
                    'Trauergespräch ' . $this->buried_name,
                    $this->appointment->copy(),
                    $this->appointment->copy()->addHour(1),
                    $this->appointment_address ?? '',
                    $this->type . ' am ' . $this->service->date->format(
                        'd.m.Y'
                    ) . ' um ' . $this->service->timeText() . ' (' . $this->service->locationText() . ")\n"
                    . 'Bestattung im Pfarrplaner öffnen: '
                    . route('funerals.edit', $this->id)
                    . "\nKontakt: " . $this->relative_contact_data . "\n\n" . DAVCalendarItem::AUTO_WARNING,
                    'prep',
                    ['busyStatus' => 'BUSY'],
                    ['Trauergespräch', 'Amtskalender: Seelsorge/Diakonie']
                );
                break;
            case 'anniv':
                return new DAVCalendarItem(
                    $this,
                    '1. Jahrestag der Beerdigung von ' . $this->buried_name,
                    $this->service->date->copy()->addYear(1),
                    $this->service->date->copy()->addYear(1)->addDay(1)->startOfDay(),
                    $this->relative_address ?? '',
                    $this->type . ' am ' . $this->service->date->format(
                        'd.m.Y'
                    ) . ' um ' . $this->service->timeText() . ' (' . $this->service->locationText()
                    . ")\nBestattung im Pfarrplaner öffnen:  "
                    . route('funerals.edit', $this->id)
                    . "\nKontakt: " . $this->relative_contact_data . "\n\n" . DAVCalendarItem::AUTO_WARNING,
                    'anniv',
                    ['allDay' => true, 'busy' => false, 'busyStatus' => 'FREE'],
                    ['Jahrestag Beerdigung']
                );
                break;
        }
    }


}
