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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

/**
 * Class Wedding
 * @package App
 */
class Wedding extends AbstractModel implements HasDAVCalendarItems
{
    use HasCommentsTrait, HasAttachmentsTrait, HasFactory, TracksDeletedByTrait, SoftDeletes;

    protected static string $prefix = 'wedding';
    protected static string $prefixPlural = 'weddings';
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
        'service' => 'nullable|exists:services,id',
        'service_id' => 'nullable|exists:services,id',
        'spouse1_name' => 'required|string',
        'spouse1_birth_name' => 'nullable|string',
        'pronoun_set1' => 'nullable|string',
        'spouse1_phone' => 'nullable|phone_number',
        'spouse1_email' => 'nullable|email',
        'spouse2_name' => 'required|string',
        'spouse2_birth_name' => 'nullable|string',
        'spouse2_phone' => 'nullable|phone_number',
        'spouse2_email' => 'nullable|email',
        'pronoun_set2' => 'nullable|string',
        'text' => 'nullable|string',
        'registered' => 'nullable|bool',
        'signed' => 'nullable|bool',
        'docs_ready' => 'nullable|bool',
        'docs_where' => 'nullable|string',
        'appointment' => 'nullable|date_format:d.m.Y H:i',
        'spouse1_dob' => 'nullable|date_format:d.m.Y',
        'spouse1_address' => 'nullable|string',
        'spouse1_zip' => 'nullable|string',
        'spouse1_city' => 'nullable|string',
        'spouse1_needs_dimissorial' => 'nullable|int',
        'spouse1_dimissorial_issuer' => 'nullable|string',
        'spouse1_dimissorial_requested' => 'nullable|date_format:d.m.Y',
        'spouse1_dimissorial_received' => 'nullable|date_format:d.m.Y',
        'spouse2_dob' => 'nullable|date_format:d.m.Y',
        'spouse2_address' => 'nullable|string',
        'spouse2_zip' => 'nullable|string',
        'spouse2_city' => 'nullable|string',
        'spouse2_needs_dimissorial' => 'nullable|int',
        'spouse2_dimissorial_issuer' => 'nullable|string',
        'spouse2_dimissorial_requested' => 'nullable|date_format:d.m.Y',
        'spouse2_dimissorial_received' => 'nullable|date_format:d.m.Y',
        'needs_permission' => 'nullable|int',
        'permission_requested' => 'nullable|date_format:d.m.Y',
        'permission_received' => 'nullable|date_format:d.m.Y',
        'music' => 'nullable|string',
        'gift' => 'nullable|string',
        'flowers' => 'nullable|string',
        'docs_format' => 'nullable|int',
        'notes' => 'nullable|string',
        'processed' => 'nullable|integer|between:0,1',
    ];
    public static $relationsForEditor = ['attachments', 'service.pastors'];

    /**
     * @var string[]
     */
    protected $fillable = [
        'service_id',
        'spouse1_name',
        'spouse1_birth_name',
        'spouse1_email',
        'spouse1_phone',
        'pronoun_set1',
        'spouse2_name',
        'spouse2_birth_name',
        'spouse2_email',
        'spouse2_phone',
        'pronoun_set2',
        'appointment',
        'text',
        'registered',
        'registration_document',
        'signed',
        'docs_ready',
        'docs_where',
        'spouse1_dob',
        'spouse1_address',
        'spouse1_zip',
        'spouse1_city',
        'spouse1_needs_dimissorial',
        'spouse1_dimissorial_issuer',
        'spouse1_dimissorial_requested',
        'spouse1_dimissorial_received',
        'spouse2_dob',
        'spouse2_address',
        'spouse2_zip',
        'spouse2_city',
        'spouse2_needs_dimissorial',
        'spouse2_dimissorial_issuer',
        'spouse2_dimissorial_requested',
        'spouse2_dimissorial_received',
        'needs_permission',
        'permission_requested',
        'permission_received',
        'notes',
        'music',
        'gift',
        'flowers',
        'docs_format',
        'processed',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'spouse1_name' => EncryptedAttribute::class,
        'spouse1_birth_name' => EncryptedAttribute::class,
        'spouse1_email' => EncryptedAttribute::class,
        'spouse1_phone' => EncryptedAttribute::class,
        'spouse2_name' => EncryptedAttribute::class,
        'spouse2_birth_name' => EncryptedAttribute::class,
        'spouse2_email' => EncryptedAttribute::class,
        'spouse2_phone' => EncryptedAttribute::class,
        'spouse1_address' => EncryptedAttribute::class,
        'spouse1_zip' => EncryptedAttribute::class,
        'spouse1_city' => EncryptedAttribute::class,
        'spouse2_address' => EncryptedAttribute::class,
        'spouse2_zip' => EncryptedAttribute::class,
        'spouse2_city' => EncryptedAttribute::class,
        'notes' => EncryptedAttribute::class,
        'appointment' => 'datetime',
        'spouse1_dob' => 'datetime',
        'spouse2_dob' => 'datetime',
        'spouse1_dimissorial_requested' => 'datetime',
        'spouse1_dimissorial_received' => 'datetime',
        'spouse2_dimissorial_requested' => 'datetime',
        'spouse2_dimissorial_received' => 'datetime',
        'permission_requested' => 'datetime',
        'permission_received' => 'datetime',
    ];

    protected $with = ['attachments'];
    protected $appends = ['spouse1DimissorialUrl', 'spouse2DimissorialUrl'];

    /**
     * @param string $page
     * @return string
     */
    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Rites/WeddingEditor',
            default => parent::getVuePath($page),
        };
    }

    /**
     * @return array
     */
    public function fillDefaults(): array
    {
        return [
            'spouse1_name' => '',
            'spouse1_birth_name' => '',
            'spouse1_email' => '',
            'spouse1_phone' => '',
            'spouse2_name' => '',
            'spouse2_birth_name' => '',
            'spouse2_email' => '',
            'spouse2_phone' => '',
            'text' => '',
            'registered' => 0,
            'registration_document' => '',
            'signed' => 0,
            'docs_ready' => 0,
            'docs_where' => '',
            'notes' => '',
            'music' => '',
            'gift' => '',
            'flowers' => '',
            'docs_format' => 0,
            'needs_permission' => 0,
            'processed' => 0,
        ];
    }

    /**
     * @return string
     */
    public function getLabelAttribute(): string
    {
        return trim($this->spouse1_name . ' / ' . $this->spouse2_name, ' /');
    }

    /**
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Generate a record for sync'ing to external calendars
     * @return array[]|null
     */
    public function getAdditionalEvents($config = [])
    {
        if (!$this->appointment) {
            return null;
        }
        $records = [];

        $description = '<p>Trauung am ' . $this->service->date->format('d.m.Y') . ' um ' . $this->service->timeText(
            ) . ' (' . $this->service->locationText() . ')</p>'
            . '<p><a href="' . route('weddings.edit', $this->id) . '">Trauung im Pfarrplaner öffnen</a></p>'
            . '<p>Kontakt:<br />'
            . trim(' - ' . $this->spouse1_name . ': ' . $this->spouse1_phone . ' ' . $this->spouse1_email) . '<br />'
            . trim(' - ' . $this->spouse2_name . ': ' . $this->spouse2_phone . ' ' . $this->spouse2_email)
            . '</p>'
            . AbstractSyncEngine::AUTO_WARNING;


        if ($this->appointment) {
            $records['wedding_prep_' . $this->id] = [
                'startDate' => $this->appointment->copy(),
                'endDate' => $this->appointment->copy()->addHour(1),
                'title' => 'Traugespräch ' . $this->spouse1_name . ' / ' . $this->spouse2_name,
                'description' => $description,
                'location' => '',
                'categories' => ['Pfarrplaner', 'Traugespräch', 'Amtskalender: Amtshandlungen'],
            ];
        }
        if ($config['include_rite_anniversaries'] ?? false) {
            $records['wedding_anniversary_' . $this->id] = [
                'startDate' => $this->service->date->copy()->addYear(1),
                'endDate' => $this->service->date->copy()->addYear(1),
                'title' => '1. Jahrestag der Trauung von ' . $this->spouse1_name . ' / ' . $this->spouse2_name,
                'description' => $description,
                'location' => '',
                'categories' => ['Pfarrplaner', 'Traugespräch', 'Amtskalender: Amtshandlungen'],
                'isAllDayEvent' => true,
                'freeBusy' => AbstractCalendarItem::STATUS_FREE,
            ];
        }


        return $records;
    }

    public function getSpouse1DimissorialUrlAttribute()
    {
        return URL::signedRoute('dimissorial.show', ['type' => 'trauung', 'id' => $this->id, 'spouse' => 1]);
    }

    public function getSpouse2DimissorialUrlAttribute()
    {
        return URL::signedRoute('dimissorial.show', ['type' => 'trauung', 'id' => $this->id, 'spouse' => 2]);
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
        $description = 'Trauung am ' . $this->service->date->format('d.m.Y') . ' um ' . $this->service->timeText(
            ) . ' (' . $this->service->locationText() . ")\n\n"
            . 'Trauung im Pfarrplaner öffnen: '
            . route('weddings.edit', $this->id)
            . "\n\nKontakt:\n"
            . trim(' - ' . $this->spouse1_name . ': ' . $this->spouse1_phone . ' ' . $this->spouse1_email) . "\n"
            . trim(' - ' . $this->spouse2_name . ': ' . $this->spouse2_phone . ' ' . $this->spouse2_email)
            . "\n\n" . DAVCalendarItem::AUTO_WARNING;

        switch ($itemType) {
            case 'prep':
                return new DAVCalendarItem(
                    $this,
                    'Traugespräch ' . $this->spouse1_name . ' / ' . $this->spouse2_name,
                    $this->appointment->copy(),
                    $this->appointment->copy()->addHour(1),
                    $this->appointment_address ?? '',
                    $description,
                    'prep',
                    ['busyStatus' => 'BUSY'],
                    ['Traugespräch', 'Amtskalender: Amtshandlungen']
                );
                break;
            case 'anniv':
                return new DAVCalendarItem(
                    $this,
                    '1. Jahrestag der Trauung von ' . $this->spouse1_name . ' / ' . $this->spouse2_name,
                    $this->service->date->copy()->addYear(1),
                    $this->service->date->copy()->addYear(1)->addDay(1)->startOfDay(),
                    '',
                    $description,
                    'anniv',
                    ['allDay' => true, 'busy' => false, 'busyStatus' => 'FREE'],
                    ['Jahrestag Trauung']
                );
                break;
        }
    }


}
