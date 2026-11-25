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

namespace App\Models;

use App\Contracts\AdConfig\CreatesAdConfigs;
use App\Contracts\AdConfig\DeletesAdConfigs;
use App\DAV\DAVCalendarItem;
use App\DAV\HasDAVCalendarItems;
use App\Helpers\StoragePath;
use App\Helpers\YoutubeHelper;
use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Models\Ads\AdConfig;
use App\Models\Calendar\Day;
use App\Models\Calendar\Occurence;
use App\Models\Liturgy\Block;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Seating\Booking;
use App\Observers\EventRecurrenceObserver;
use App\Seating\AbstractSeatFinder;
use App\Seating\MaximumBasedSeatFinder;
use App\Seating\RowBasedSeatFinder;
use App\Services\LiturgyService;
use App\Services\MinistryService;
use App\Services\NameService;
use App\Tools\StringTool;
use App\Traits\HasAttachmentsTrait;
use App\Traits\HasAttributeLabels;
use App\Traits\HasCommentsTrait;
use App\Traits\HasLiturgicalInfo;
use App\Traits\IncludesPermissionAttributes;
use App\Traits\TracksChangesTrait;
use App\Traits\TracksDeletedByTrait;
use Carbon\Carbon;
use Database\Factories\ServiceFactory;
use Exception;
use Google_Service_YouTube_LiveBroadcastSnippet;
use Google_Service_YouTube_VideoSnippet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class Service
 *
 * @property-read Carbon $datetime Copy of the service date exposed by the legacy accessor.
 * @property-read Carbon $dateTime Legacy camel-case alias, distinct from the dateTime() formatting method.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User&object{pivot: Pivot&object{category: string}}> $participants Participants with their service-specific category.
 *
 * @method string baptismsText(bool $includeCount = false, bool $includeText = false) Format attached baptisms.
 * @method string dateText(string $format = 'DD.MM.YYYY') Format the service date.
 * @method Carbon dateTime() Return the service date in Europe/Berlin.
 * @method string descriptionText(list<string> $exclude = [], bool $excludeBaptismIfNoCandidates = false) Format the description.
 * @method string locationText() Format the service location.
 * @method string timeText(bool $uhr = true, string $separator = ':', bool $skipMinutes = false, bool $nbsp = false, bool $leadingZero = false) Format the service time.
 * @method string titleText(bool $short = true, bool $skipRites = false) Format the service title.
 * @method bool isTemplate() Determine whether this service is the template.
 */
class Service extends Model implements HasDAVCalendarItems
{
    use HasAttachmentsTrait;
    use HasAttributeLabels;
    use HasCommentsTrait;

    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    use HasLiturgicalInfo;
    use IncludesPermissionAttributes;
    use SoftDeletes;
    use TracksChangesTrait;
    use TracksDeletedByTrait;

    /**
     * @var array<string, mixed>
     */
    public $liturgy = [];

    /**
     * @var list<string>
     */
    protected $with = [
        'participants',
        'day',
        'location',
        'city',
        'pastors',
        'organists',
        'sacristans',
        'otherParticipants',
        'participantsWithMinistry',
        'funerals',
        'baptisms',
        'weddings',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'need_predicant' => 'boolean',
        'cc' => 'boolean',
        'baptism' => 'boolean',
        'eucharist' => 'boolean',
        'date' => 'datetime',
        'registration_online_start' => 'datetime',
        'registration_online_end' => 'datetime',
        'alt_liturgy_date' => 'datetime',
        'end' => 'datetime',
        'offering_denominations' => 'array',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'day_id',
        'location_id',
        'time',
        'description',
        'city_id',
        'special_location',
        'need_predicant',
        'baptism',
        'eucharist',
        'offerings_counter1',
        'offerings_counter2',
        'offering_goal',
        'offering_description',
        'offering_type',
        'others',
        'cc',
        'cc_alt_time',
        'cc_location',
        'cc_lesson',
        'cc_staff',
        'internal_remarks',
        'offering_amount',
        'offering_denominations',
        'title',
        'youtube_url',
        'cc_streaming_url',
        'offerings_url',
        'meeting_url',
        'recording_url',
        'songsheet',
        'external_url',
        'sermon_title',
        'sermon_reference',
        'sermon_image',
        'sermon_description',
        'konfiapp_event_type',
        'konfiapp_event_qr',
        'hidden',
        'needs_reservations',
        'exclude_sections',
        'registration_active',
        'exclude_places',
        'registration_phone',
        'registration_online_start',
        'registration_online_end',
        'registration_max',
        'reserved_places',
        'youtube_prefix_description',
        'youtube_postfix_description',
        'sermon_id',
        'announcements',
        'offering_text',
        'slug',
        'controlled_access',
        'alt_liturgy_date',
        'date',
        'wtc_category',
        'liturgy_info_id',
        'event_class',
        'end',
        'rrule',
        'is_allday',
        'alt_proprium',
        'ad_text',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'descriptionText',
        'locationText',
        'locationTextWithCity',
        'dateText',
        'timeText',
        'baptismsText',
        'liturgy',
        'ministriesByCategory',
        'isShowable',
        'isEditable',
        'isDeletable',
        'isMine',
        'titleText',
        'liveDashboardUrl',
        'datetime',
        'liturgicalInfoDate',
        'liturgicalInfo',
        'keyDate',
        'isAlternateProprium',
        'isTemplate',
    ];

    /**
     * @var string[]
     */
    protected $appendsToTracking = ['dateText', 'timeText', 'locationText'];

    /** @var string[] */
    protected $attributesToSkipTracking = ['date', 'time', 'location_id', 'special_location', 'slug'];

    /**
     * @var string[]
     */
    protected $relationsToTrack = ['participants'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'offering_type' => 'eO',
    ];

    /** @var AbstractSeatFinder */
    protected $seatFinder = null;

    /*
     * Trait accessors are overridden here so the nullable authenticated user and
     * the nullable service attributes are handled without losing type safety.
     */

    /**
     * Determines whether the authenticated user may show this model.
     *
     * @return bool True when the user is allowed to show the model.
     */
    public function getIsShowableAttribute(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('show', $this);
    }

    /**
     * Determines whether the authenticated user may edit this model.
     *
     * @return bool True when the user is allowed to update the model.
     */
    public function getIsEditableAttribute(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('update', $this);
    }

    /**
     * Determines whether the authenticated user may delete this model.
     *
     * @return bool True when the user is allowed to delete the model.
     */
    public function getIsDeletableAttribute(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('delete', $this);
    }

    /**
     * Return the comments visible to the authenticated user.
     *
     * @return mixed
     */
    public function commentsForCurrentUser()
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return $this->comments()->where('private', 0);
        }

        return $this->commentsForUser($user);
    }

    /**
     * Resolve the liturgical information for this service.
     *
     * @return array<string, mixed>
     */
    public function getLiturgicalInfoAttribute(): array
    {
        if ($this->cachedLiturgicalInfo !== null) {
            return $this->cachedLiturgicalInfo;
        }
        if (! $this->isAlternateProprium) {
            $liturgyInfo = LiturgyService::getLiturgyInfoByDate($this->serviceDate());
            /** @var array<string, mixed> $litInfo */
            $litInfo = is_array($liturgyInfo) ? ($liturgyInfo[0] ?? []) : [];
        } else {
            $litInfo = (LiturgyService::getLiturgyByAltPropriumCode((string) $this->alt_proprium) ?? []);
        }
        if ($litInfo) {
            $this->cachedLiturgicalInfo = $litInfo;
        }

        return $litInfo;
    }

    // ACCESSORS

    /**
     * @return string
     */
    public function getBaptismsTextAttribute()
    {
        return $this->formatBaptismsText(true);
    }

    /**
     * @param  string|null  $value
     * @return string|null
     */
    public function getKonfiappEventQrAttribute($value)
    {
        return $this->ensureKonfiAppQrCode($value);
    }

    /**
     * @param  string|null  $value
     * @return string|null
     */
    public function ensureKonfiAppQrCode($value = null)
    {
        /** @var string|null $value */
        $value = $value ?? $this->getRawOriginal('konfiapp_event_qr');
        if (is_string($value) && ($value !== '')) {
            return $value;
        }

        $eventType = $this->getRawOriginal('konfiapp_event_type');
        if (empty($eventType)) {
            return $value;
        }

        $city = $this->relationLoaded('city') ? $this->city : $this->city()->first();
        if ((! $city) || (! KonfiAppIntegration::isActive($city))) {
            return $value;
        }

        try {
            $service = KonfiAppIntegration::get($city)->addQRCodeToService($this);
            /** @var string|null $value */
            $value = $service->getRawOriginal('konfiapp_event_qr');
            if (is_string($value) && ($value !== '')) {
                $this->attributes['konfiapp_event_qr'] = $value;
            }
        } catch (Throwable $exception) {
            Log::warning('KonfiApp: QR-Code konnte bei Zugriff nicht erzeugt werden.', [
                'service' => $this->id,
                'eventType' => $eventType,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
        }

        return $value;
    }

    /**
     * @param  bool  $includeCount
     * @param  bool  $includeText
     * @return string
     */
    protected function formatBaptismsText($includeCount = false, $includeText = false)
    {
        $baptisms = [];
        foreach ($this->baptisms as $baptism) {
            /** @var string $name */
            $name = NameService::fromName(self::stringify($baptism->candidate_name))->format(NameService::FIRST_LAST);
            $baptisms[] = $name;
        }

        return
            ($includeText ? ($this->baptisms()->count() == 1 ? 'Taufe ' : 'Taufen ').'von ' : '')
            .($includeCount ? $this->baptisms()->count().' '.($this->baptisms()->count(
            ) == 1 ? 'Taufe ' : 'Taufen ') : '')
            .implode('; ', $baptisms);
    }

    /**
     * Return the baptisms associated with this service.
     *
     * @return HasMany<Baptism, $this>
     */
    public function baptisms()
    {
        return $this->hasMany(Baptism::class);
    }

    /**
     * @return string Rendered description.
     *
     * @throws Throwable
     */
    public function getBroadcastDescriptionAttribute()
    {
        return view(
            'services.youtube.snippet.description',
            ['service' => $this, 'liturgy' => LiturgyService::getLiturgyInfoByDate($this->serviceDate()->format('Y-m-d'))]
        )->render();
    }

    /**
     * @return mixed|string
     */
    public function getBroadcastTitleAttribute()
    {
        $liturgy = LiturgyService::getLiturgyInfoByDate($this->serviceDate()->format('Y-m-d'));

        return $this->title ?: (is_array($liturgy) && isset($liturgy['Bezeichnung']) ? self::stringify($liturgy['Bezeichnung'])
            .' ('.$this->serviceDate()->format('d.m.Y').')'
            : 'Gottesdienst mit '.$this->participantsText('P', true));
    }

    /**
     * @param  bool  $fullName
     * @param  bool  $withTitle
     * @param  string  $glue
     * @param  string  $category  Participant category.
     * @return string
     */
    public function participantsText($category, $fullName = false, $withTitle = true, $glue = ', ')
    {
        $participants = $this->participantsByCategory($category);
        $names = [];
        foreach ($participants as $participant) {
            $names[] = ($fullName ? $participant->fullName($withTitle) : $participant->lastName($withTitle));
        }

        return implode($glue, $names);
    }

    /**
     * @param  string  $category  Participant category.
     * @return Collection<int, User>
     */
    public function participantsByCategory($category)
    {
        switch ($category) {
            case 'P':
                return $this->pastors;
            case 'O':
                return $this->organists;
            case 'M':
                return $this->sacristans;
            case 'A':
                return $this->otherParticipants;
            default:
                /** @var Collection<int, User> $ministries */
                $ministries = $this->ministries()[$category] ?? collect([]);

                return $ministries;
        }
    }

    /**
     * Group custom-ministry participants by their pivot category.
     *
     * @return Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, User>>
     */
    public function ministries()
    {
        return $this->participantsWithMinistry->groupBy('pivot.category');
    }

    /**
     * Return participant credits for display and exports.
     *
     * @return string Formatted participant credits.
     */
    public function getCreditsAttribute()
    {
        $credits = [];
        if ($t = $this->participantsText('P', true, true)) {
            $credits['Liturgie'] = 'Liturgie: '.$t;
        }
        if ($t = $this->participantsText('O', true, true)) {
            $credits['Orgel'] = 'Orgel: '.$t;
        }
        foreach ($this->ministries() as $ministry => $people) {
            if ($t = $this->participantsText((string) $ministry, true, true)) {
                $credits[$ministry] = $ministry.': '.$t;
            }
        }
        if ($t = $this->participantsText('M', true, true)) {
            $credits[self::stringify(config('labels.sacristan'))] = 'Mesnerdienst: '.$t;
        }
        $separator = utf8_encode(' '.chr(183).' ');

        return implode($separator, $credits);
    }

    /**
     * @return mixed
     */
    public function getDateTextAttribute()
    {
        return $this->formatDateText();
    }

    /**
     * @param  string  $format
     * @return mixed
     */
    protected function formatDateText($format = 'DD.MM.YYYY')
    {
        return $this->serviceDate()->isoFormat($format);
    }

    //

    /**
     * Return the service date as a mutable datetime value.
     *
     * @return Carbon Service date.
     */
    public function getDatetimeAttribute()
    {
        return $this->serviceDate()->copy();
    }

    /**
     * Return the service date as a guaranteed Carbon instance.
     *
     * @return Carbon Service date.
     */
    protected function serviceDate(): Carbon
    {
        $date = $this->date;
        if (! $date instanceof Carbon) {
            throw new \LogicException('Der Gottesdienst hat kein Datum.');
        }

        return $date;
    }

    /**
     * @return string
     */
    public function getDescriptionTextAttribute()
    {
        return $this->formatDescriptionText();
    }

    /**
     * @param  list<string>  $exclude  Parts to omit.
     * @param  bool  $excludeBaptismIfNoCandidates  Whether baptism hints are omitted.
     * @return string
     */
    protected function formatDescriptionText($exclude = [], $excludeBaptismIfNoCandidates = false)
    {
        $desc = [];
        if ($this->needs_reservations) {
            $desc['needs_reservations'] = ($this->registration_online_end ? 'Anmeldung nötig bis '.$this->registration_online_end->setTimezone(
                'Europe/Berlin'
            )->format(
                'd.m.Y, H:i'
            ).' Uhr' : 'Anmeldung nötig');
        }
        if ($this->baptism) {
            if (! Str::contains(Str::lower($this->formatTitleText()), 'tauf')) {
                if ((! $excludeBaptismIfNoCandidates) || ($this->baptisms->count())) {
                    $desc['baptism'] = $this->baptisms->count() ? 'mit Taufen' : 'möglicher Taufgottesdienst';
                }
            }
        }
        if ($this->eucharist) {
            if (! Str::contains(Str::lower($this->formatTitleText()), 'abendmahl')) {
                $desc['eucharist'] = 'mit Abendmahl';
            }
        }
        if ($this->getAttribute('description') != '') {
            $desc['description'] = self::stringify($this->getAttribute('description'));
        }
        foreach ($exclude as $key) {
            if (isset($desc[$key])) {
                unset($desc[$key]);
            }
        }

        return implode('; ', $desc);
    }

    /**
     * @param  bool  $short
     * @param  bool  $skipRites
     * @return string
     */
    protected function formatTitleText($short = true, $skipRites = false)
    {
        $includeDefaultServiceTitle = true;
        $elements = [];
        if ($this->title != '') {
            $elements[] = $x = $this->title;
        }
        if (! $skipRites) {
            if ($this->weddingsText() != '') {
                $elements[] = $x = $this->weddingsText();
                $includeDefaultServiceTitle = false;
            }
            if ($this->funeralsText() != '') {
                $elements[] = $x = $this->funeralsText();
                $includeDefaultServiceTitle = false;
            }
            if ($this->formatBaptismsText() != '') {
                if (! Str::contains(Str::lower(implode(' / ', $elements)), 'tauf')) {
                    $elements[] = $x = ($this->baptisms->count() > 1 ? 'Taufen' : 'Taufe');
                }
            }
        }
        if ($includeDefaultServiceTitle && (count($elements) == 1) && ($x != '') && ($x != $this->title)) {
            $elements[0] = ($short ? 'GD' : 'Gottesdienst').' mit '.$elements[0];
        }

        return implode(' / ', $elements) ?: ($short ? 'GD' : 'Gottesdienst');
    }

    /**
     * @return string
     */
    public function weddingsText()
    {
        $weddings = [];
        /** @var Wedding $wedding */
        foreach ($this->weddings as $wedding) {
            /** @var string $spouse1 */
            $spouse1 = NameService::fromName(self::stringify($wedding->spouse1_name))->format(NameService::FIRST_LAST);
            /** @var string $spouse2 */
            $spouse2 = NameService::fromName(self::stringify($wedding->spouse2_name))->format(NameService::FIRST_LAST);
            $weddings[] = 'Trauung von '.$spouse1
                .($wedding->spouse1_birth_name ? ' ('.self::stringify($wedding->spouse1_birth_name).')' : '')
                .' und '.$spouse2
                .($wedding->spouse2_birth_name ? ' ('.self::stringify($wedding->spouse2_birth_name).')' : '');
        }

        return implode('; ', $weddings);
    }

    /**
     * Build the service-title text for attached funerals, preserving the urn-only wording.
     *
     * @return string Formatted funeral titles separated by semicolons.
     */
    public function funeralsText()
    {
        $funerals = [];
        foreach ($this->funerals as $funeral) {
            /** @var string $name */
            $name = NameService::fromName(self::stringify($funeral->buried_name))->format(NameService::FIRST_LAST);
            $funerals[] = $funeral->type === 'Urnenbeisetzung'
                ? 'Urnenbeisetzung von '.$name
                : 'Trauerfeier für '.$name;
        }

        return implode('; ', $funerals);
    }

    /**
     * Return the service duration in minutes.
     *
     * @return int Duration in minutes.
     */
    public function getDurationAttribute()
    {
        if (! $this->end) {
            return 60;
        }

        return abs((int) $this->end->diffInMinutes($this->date));
    }

    /**
     * Return the formatted number of available seats.
     *
     * @return string Available-seat description.
     */
    public function getFreeSeatsTextAttribute()
    {
        return $this->getSeatFinder()->freeSeatsText();
    }

    /**
     * Get an instance of SeatFinder for this service
     *
     * @return AbstractSeatFinder
     */
    public function getSeatFinder()
    {
        if ($this->seatFinder) {
            return $this->seatFinder;
        }
        if (is_object($this->location) && (count($this->location->seatingSections) > 0)) {
            return new RowBasedSeatFinder($this);
        }

        return new MaximumBasedSeatFinder($this);
    }

    /**
     * Return the image url for a specific image cut (empty if this cut doesn't exist)
     */
    public function getImageCutUrl(string $cut): string
    {
        if ($attachment = $this->attachments()->firstWhere('cut', Str::slug($cut))) {
            return route('image', Str::replace('attachments/', '', (string) $attachment->file));
        }

        return '';
    }

    /**
     * Return the image path for a specific image cut (empty if this cut doesn't exist)
     */
    public function getImageCutPath(string $cut): string
    {
        if ($attachment = $this->attachments()->firstWhere('cut', Str::slug($cut))) {
            return StoragePath::for($attachment->file);
        }

        return '';
    }

    /**
     * @return bool
     */
    public function getHasSeatsAttribute()
    {
        return $this->getSeatFinder()->hasSeats;
    }

    /**
     * Determine whether the authenticated user participates in this service.
     *
     * @return bool Whether the service belongs to the current user's participation.
     */
    public function getIsMineAttribute()
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return $this->participants->contains(fn ($participant) => $participant->id === $user->id);
    }

    /**
     * Return the service date in the key format used by calendar views.
     *
     * @return string Date key.
     */
    public function getKeyDateAttribute()
    {
        return $this->serviceDate()->format('Y-m-d');
    }

    /**
     * @return array<string, mixed>
     */
    public function getLiturgyAttribute()
    {
        return $this->liturgy;
    }

    /**
     * Return the live-dashboard URL for the service's city and stream.
     *
     * @return string Dashboard URL or an empty string.
     */
    public function getLiveDashboardUrlAttribute()
    {
        if ($this->city === null) {
            return '';
        }

        return $this->city->youtube_channel_url ? YoutubeHelper::getLiveDashboardUrl(
            $this->city,
            (string) $this->youtube_url
        ) : '';
    }

    /**
     * @return mixed|string
     */
    public function getLocationTextAttribute()
    {
        return $this->formatLocationText();
    }

    /**
     * @return mixed|string
     */
    protected function formatLocationText()
    {
        return $this->special_location ?: (is_object($this->location) ? $this->location->name : '');
    }

    /**
     * @return mixed|string
     */
    public function getLocationTextWithCityAttribute()
    {
        return $this->special_location ?: (is_object($this->location) ? $this->location->fullName : '');
    }

    /**
     * Return the maximum capacity of the service location.
     *
     * @return int Maximum number of seats.
     */
    public function getMaximumCapacityAttribute()
    {
        return $this->getSeatFinder()->maximumCapacity();
    }

    /**
     * Expose the participant groups used by the ministry planner.
     *
     * @return Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, User>>
     */
    public function getMinistriesByCategoryAttribute()
    {
        return $this->ministries();
    }

    /**
     * @return string
     */
    public function getOfferingsUrlAttribute()
    {
        return self::stringify($this->attributes['offerings_url'] ?: ($this->city ? $this->city->default_offering_url : ''));
    }

    /**
     * Return the loaded organist participant collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getOrganistsAttribute()
    {
        return $this->organists()->get();
    }

    /**
     * Return participants assigned to the organist category.
     *
     * @return BelongsToMany<User, $this>
     */
    public function organists()
    {
        return $this->belongsToMany(User::class)->wherePivot('category', 'O')->withTimestamps();
    }

    /**
     * Return the loaded additional-participant collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getOtherParticipantsAttribute()
    {
        return $this->otherParticipants()->get();
    }

    /**
     * Return participants assigned to the additional-participant category.
     *
     * @return BelongsToMany<User, $this>
     */
    public function otherParticipants()
    {
        return $this->belongsToMany(User::class)->wherePivot('category', 'A')->withTimestamps();
    }

    /**
     * Return the loaded pastor participant collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getPastorsAttribute()
    {
        return $this->pastors()->get();
    }

    /**
     * Return participants assigned to the pastor category.
     *
     * @return BelongsToMany<User, $this>
     */
    public function pastors()
    {
        return $this->belongsToMany(User::class)->wherePivot('category', 'P')->withTimestamps();
    }

    /**
     * Return sermon recipients, or an empty collection when no sermon item exists.
     *
     * @return array<array-key, mixed>|Collection<array-key, mixed>
     */
    public function getPreachersAttribute()
    {
        $sermonItem = null;
        foreach ($this->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($item->data_type == 'sermon') {
                    $sermonItem = $item;

                    continue;
                }
            }
            if ($sermonItem !== null) {
                continue;
            }
        }
        if ($sermonItem === null) {
            return collect();
        }

        return $sermonItem->recipients();
    }

    /**
     * @return int|mixed|void
     */
    public function getRemainingCapacityAttribute()
    {
        return $this->getSeatFinder()->remainingCapacity();
    }

    /**
     * Return the loaded sacristan participant collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getSacristansAttribute()
    {
        return $this->sacristans()->get();
    }

    /**
     * Return participants assigned to the sacristan category.
     *
     * @return BelongsToMany<User, $this>
     */
    public function sacristans()
    {
        return $this->belongsToMany(User::class)->wherePivot('category', 'M')->withTimestamps();
    }

    /**
     * Get seating for this service
     *
     * @return array<int|string, mixed>
     */
    public function getSeatingAttribute()
    {
        return $this->getSeatFinder()->getSeatingTable();
    }

    /**
     * @return string
     */
    public function getSongsheetUrlAttribute()
    {
        return route(
            'storage',
            [
                'path' => pathinfo((string) $this->songsheet, PATHINFO_FILENAME),
                'prettyName' => $this->serviceDate()->format('Ymd').'-Liedblatt.'.pathinfo(
                    (string) $this->songsheet,
                    PATHINFO_EXTENSION
                ),
            ]
        );
    }

    /**
     * @return false|string
     */
    public function getTimeAttribute()
    {
        return isset($this->attributes['time']) ? substr(self::stringify($this->attributes['time']), 0, 5) : '';
    }

    /**
     * @return string
     */
    public function getTimeTextAttribute()
    {
        return $this->formatTimeText();
    }

    /**
     * @param  bool  $uhr
     * @param  string  $separator
     * @param  bool  $skipMinutes
     * @param  bool  $nbsp
     * @param  bool  $leadingZero
     * @return string
     */
    protected function formatTimeText($uhr = true, $separator = ':', $skipMinutes = false, $nbsp = false, $leadingZero = false)
    {
        return $this->serviceDate()->copy()->setTimeZone('Europe/Berlin')
            ->format(($leadingZero ? 'H' : 'G').($skipMinutes ? '' : $separator.'i'))
            .($uhr ? ($nbsp ? '&nbsp;' : ' ').'Uhr' : '');
    }

    /**
     * Return the display title of the service.
     *
     * @return string Display title.
     */
    public function getTitleTextAttribute()
    {
        return $this->formatTitleText(false, false);
    }

    /**
     * @return string
     */
    public function getVideoTimeStringAttribute()
    {
        $thisTime = Carbon::createFromTimeString(
            $this->serviceDate()->format('Y-m-d').' '.$this->time,
            'Europe/Berlin'
        );

        return $thisTime->setTimezone('UTC')->format('Y-m-d\TH:i:s').'.000Z';
    }

    /**
     * Keep the legacy helper calls working after moving them behind accessors.
     *
     * @param  string  $method  Method name.
     * @param  array<int, mixed>  $parameters  Method arguments.
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $legacyMethods = [
            'baptismsText' => 'formatBaptismsText',
            'dateText' => 'formatDateText',
            'dateTime' => 'formatDateTime',
            'descriptionText' => 'formatDescriptionText',
            'locationText' => 'formatLocationText',
            'timeText' => 'formatTimeText',
            'titleText' => 'formatTitleText',
            'isTemplate' => 'isTemplateDate',
        ];
        if (isset($legacyMethods[$method])) {
            /** @var callable(mixed...): mixed $legacyCall */
            $legacyCall = [$this, $legacyMethods[$method]];

            return $legacyCall(...$parameters);
        }

        return parent::__call($method, $parameters);
    }

    // END ACCESSORS

    // MUTATORS
    /**
     * Normalize a stored service time to hour and minute precision.
     *
     * @param  string  $time  Submitted time.
     * @return void
     */
    public function setTimeAttribute($time)
    {
        $this->attributes['time'] = substr($time, 0, 5);
    }

    // SCOPES

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @param  Carbon|string  $date  Date to filter.
     * @return Builder<static>
     */
    public function scopeAtDate(Builder $query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @param  Carbon  $start  Start of the range.
     * @param  Carbon  $end  End of the range.
     * @return Builder<static>
     */
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end)
    {
        $start = $start->copy()->setTime(0, 0, 0);
        $end = $end->copy()->setTime(23, 59, 59);

        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeDateRange(Builder $query, Carbon $start, Carbon $end)
    {
        $start = $start->copy()->setTime(0, 0, 0);
        $end = $end->copy()->setTime(23, 59, 59);

        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Filter services which should not be publically displayed before a specific date
     * This includes notHidden() and checks for funerals which have not been announced yet
     *
     * @param  Builder<static>  $query  Query being filtered.
     * @param  Carbon|null  $date  Reference date.
     * @return void
     */
    public function scopeDisplayable(Builder $query, $date = null)
    {
        $date = $date ?? Carbon::now();
        $query->notHidden()->where(function ($query) use ($date) {
            $query->doesntHave('funerals')
                ->doesntHave('funerals', 'or', function ($query) use ($date) {
                    $query->whereNull('announcement')
                        ->orWhereDate('announcement', '>', $date);
                });
        });
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeEndingAt(Builder $query, Carbon $date)
    {
        return $query->whereDate('date', '<=', $date);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @param  list<string>  $ministries  Ministry categories.
     * @return Builder<static>
     */
    public function scopeHavingOpenMinistries(Builder $query, $ministries)
    {
        return $query->whereDoesntHave(
            'participants',
            function ($query) use ($ministries) {
                $query->whereIn('service_user.category', $ministries);
            }
        )
            ->orderBy('date', 'asc');
    }

    /**
     * Scope a query to include all event types by removing the ServicesOnlyScope.
     *
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeIncludeAllEventTypes(Builder $query)
    {
        return $query->withoutGlobalScope(ServicesOnlyScope::class);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeHidden(Builder $query)
    {
        return $query->where('hidden', 1);
    }

    /**
     * Scope a query to filter by given cities.
     *
     * If a city has is_org=true, child cities will be included
     *
     * @param  mixed  $cities  An array or collection of cities, each having an id property.
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeInCities(Builder $query, $cities)
    {
        if (is_string($cities)) {
            $cities = [$cities];
        }
        $cityIds = collect();
        /** @var iterable<int, mixed> $cities */
        $cityItems = collect($cities);
        foreach ($cityItems as $item) {
            if (is_array($item)) {
                $item = array_first($item);
            }
            /** @var City $city */
            $city = $item instanceof City ? $item : City::query()->findOrFail($item);
            $cityIds->push($city->id);
            if ($city->is_org) {
                $cityIds = $cityIds->merge($city->children->pluck('id'));
            }
        }
        $cityIds = $cityIds->unique()->values();

        return $query->where(function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
            $q->orWhereHas('relatedCities', function ($q2) use ($cityIds) {
                $q2->whereIn('cities.id', $cityIds);
            });
        });
    }

    /**
     * Scope a query to filter by given cities and, optionally, locations.
     *
     * @param  mixed  $cities  An array or collection of cities, each having an id property.
     * @param  mixed|null  $locations  An optional array or collection of locations, each having an id property.
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeInCitiesAndLocations(Builder $query, $cities, $locations = null)
    {
        $query = $this->scopeInCities($query, $cities);

        /** @var iterable<int, mixed> $locations */
        if ($locations === null || collect($locations)->filter()->isEmpty()) {
            return $query;
        }

        if (is_string($locations)) {
            $locations = [$locations];
        }
        /** @var iterable<int, mixed> $locations */
        $locationIds = collect($locations)->map(function ($item) {
            if (is_array($item)) {
                $item = array_first($item);
            }

            if (is_numeric($item)) {
                return $item;
            }

            /** @var object{id: int|string} $item */
            return $item->id;
        })->filter()->values();

        if ($locationIds->isEmpty()) {
            return $query;
        }

        return $query->whereIn('location_id', $locationIds);
    }

    /**
     * Scope a query to filter by a given city
     *
     * If the city has is_org=true, child cities will be included
     *
     * @param  City|int|string  $city  City or its identifier.  A city object or id
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeInCity(Builder $query, $city)
    {
        return $this->scopeInCities($query, [$city]);
    }

    /**
     * @param  int  $year  Year
     * @param  int  $month  Month
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeInMonth(Builder $query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    /**
     * Limit services to the month containing a date.
     *
     * @param  Builder<static>  $query  Service query.
     * @param  Carbon  $date  Reference date.
     * @return Builder<static> Filtered query.
     */
    public function scopeInMonthByDate(Builder $query, Carbon $date)
    {
        return $query->whereMonth('date', $date->month)->whereYear('date', $date->year);
    }

    /**
     * Get services not used as agenda
     *
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeIsNotAgenda(Builder $query)
    {
        return $query->whereDate('date', '!=', '1978-03-05');
    }

    /**
     * Get services used as template
     *
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeIsTemplate(Builder $query)
    {
        return $query->whereDate('date', '1978-03-05');
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeNotHidden(Builder $query)
    {
        return $query->where('hidden', 0);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @param  string  $direction  Sort direction.
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query, $direction = 'ASC')
    {
        return $query->orderBy('date', strtolower($direction) === 'desc' ? 'desc' : 'asc');
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeOrderedDesc(Builder $query)
    {
        return $query->ordered('DESC');
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeRegularForCity(Builder $query, City $city)
    {
        return $query->inCity($city->id)
            ->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings');
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeStartingFrom(Builder $query, Carbon $date)
    {
        return $query->whereDate('date', '>=', $date);
    }

    /**
     * Get services where a specific user is a participant
     *
     * @param  string  $category
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeUserParticipates(Builder $query, User $user, $category = null)
    {
        return $query->whereHas('participants', function ($query2) use ($user, $category) {
            $query2->where('user_id', $user->id);
            if ($category) {
                $query2->where('category', $category);
            }
        });
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeWritable(Builder $query)
    {
        return $query->whereIn('city_id', Auth::user()?->writableCities->pluck('id') ?? []);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeServicesOnly(Builder $query)
    {
        return $query->where('event_class', 'service');
    }

    // SETTERS
    // SETTERS
    // SETTERS
    // SETTERS
    // SETTERS
    // SETTERS
    // SETTERS
    // SETTERS
    /**
     * Mix a collection of services into an array of events
     *
     * @param  array<string, mixed>  $events  Event map to extend.
     * @param  iterable<Service>  $services  Services to add.
     * @return array<int|string, mixed>
     */
    public static function mix($events, $services, Carbon $start, Carbon $end)
    {
        /** @var Service $service */
        foreach ($services as $service) {
            $date = $service->date;
            if (! $date instanceof Carbon) {
                continue;
            }
            if (($date <= $end) && ($date >= $start)) {
                $key = $date->format('YmdHis');
                $target = $events[$key] ?? [];
                if (! is_array($target)) {
                    $target = [];
                }
                $target[] = $service;
                $events[$key] = $target;
            }
        }

        ksort($events);

        return $events;
    }

    /**
     * Return services with empty entries for specific ministries
     *
     * @param  list<string>  $ministries  Ministry categories.  array
     * @return array<int|string, mixed>
     */
    public static function withOpenMinistries($ministries)
    {
        $missing2 = [];

        foreach ($ministries as $ministry) {
            $missing2[$ministry] = Service::with(['location', 'day'])
                ->whereIn('city_id', Auth::user()?->writableCities->pluck('id') ?? [])
                ->havingOpenMinistries([$ministry])
                ->startingFrom(Carbon::now())
                ->get();
        }

        $missing = [];
        foreach ($missing2 as $ministry => $services) {
            foreach ($services as $service) {
                if (isset($missing[$service->id])) {
                    $missing[$service->id]['missing'][] = $ministry;
                } else {
                    $missing[$service->id] = [
                        'missing' => [MinistryService::title($ministry)],
                        'service' => $service,
                    ];
                }
            }
        }

        return $missing;
    }

    /**
     * Boot method
     * -> register creating/updating handlers to ensure slug is always up to date
     * -> register global scope
     */
    protected static function boot()
    {
        parent::boot();

        /*
         * For backwards compatibility, we're adding a ServicesOnlyScope to all queries, so all existing code will only
         * work on service events, not on other event_classes. Eventually, this scope won't be necessary any more, but
         * for the moment, it's the easiest way to slowly introduce new event_classes.
         */
        static::addGlobalScope(new ServicesOnlyScope);

        static::creating(function (Service $service) {
            $service->slug = $service->createSlug();
        });

        static::updating(function (Service $service) {
            $service->slug = $service->createSlug();
        });

        static::deleting(function (Service $service) {
            if ($service->isForceDeleting()) {
                $service->baptisms()->withTrashed()->get()->each->forceDelete();
                $service->funerals()->withTrashed()->get()->each->forceDelete();
                $service->weddings()->withTrashed()->get()->each->forceDelete();
                $service->AdConfigs()->withTrashed()->get()->each->forceDelete();
                $service->occurences()->delete();

                return;
            }

            $service->baptisms()->get()->each->delete();
            $service->funerals()->get()->each->delete();
            $service->weddings()->get()->each->delete();
            $service->AdConfigs()->get()->each->delete();
            $service->occurences()->delete();
        });

        static::restored(function (Service $service) {
            $service->baptisms()->withTrashed()->get()->each->restore();
            $service->funerals()->withTrashed()->get()->each->restore();
            $service->weddings()->withTrashed()->get()->each->restore();
            $service->AdConfigs()->withTrashed()->get()->each->restore();

            app(EventRecurrenceObserver::class)->updated($service);
        });
    }

    /**
     * Create the route slug for this service.
     *
     * @return string Service slug.
     */
    public function createSlug()
    {
        if ($this->isTemplateDate()) {
            return $this->id.'-'.Str::slug((string) $this->title);
        }

        return $this->serviceDate()->copy()->setTimeZone('Europe/Berlin')->format('Ymd-Hi').'-'.$this->id
            .($this->city ? '-'.Str::slug($this->city->name) : '');
    }

    /**
     * Resolve route model bindings across all event types.
     *
     * Routes such as the service editor are also used for non-service events,
     * so route model binding must explicitly opt out of the default scope.
     *
     * @param  mixed  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return mixed
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        /** @var Builder<static> $query */
        return $query
            ->withoutGlobalScope(ServicesOnlyScope::class)
            ->where($field, $value);
    }

    /**
     * Determine whether this service is the template service.
     *
     * @return bool Whether the service uses the template date.
     */
    protected function isTemplateDate(): bool
    {
        return $this->serviceDate()->format('Y-m-d') == '1978-03-05';
    }

    /**
     * Expose the template state as an accessor.
     *
     * @return bool Whether the service is a template.
     */
    public function getIsTemplateAttribute(): bool
    {
        return $this->isTemplateDate();
    }

    /**
     * @param  Request  $request  Request with participant data.
     * @return array<string, mixed>|void
     */
    public function associateParticipants($request, Service $service)
    {
        if (! $request->has('participants')) {
            return;
        }

        $participants = [];
        /** @var array<string, array<int, int|string>> $requestParticipants */
        $requestParticipants = $request->get('participants') ?: [];
        foreach ($requestParticipants as $category => $participantList) {
            /** @var array<int, int|string> $participantList */
            foreach ($participantList as $participant) {
                if ($participant) {
                    $participant = User::createIfNotExists($participant);
                    $participants[$category][$participant]['category'] = $category;
                }
            }
        }

        /** @var array<int, array{people?: array<int, int|string>, description: string}> $ministries */
        $ministries = $request->get('ministries') ?: [];
        foreach ($ministries as $ministry) {
            if (isset($ministry['people'])) {
                foreach ($ministry['people'] as $participant) {
                    if ($participant) {
                        $participant = User::createIfNotExists($participant);
                        $participants[$ministry['description']][$participant]['category'] = $ministry['description'];
                    }
                }
            }
        }

        return $this->syncParticipantsFromArray($participants);
    }

    /**
     * @param  array<string, mixed>  $participants  Participants by category.
     * @return array<string, mixed>
     */
    public function syncParticipantsFromArray($participants)
    {
        $this->participants()->sync([]);
        if (count($participants)) {
            foreach ($participants as $category => $participant) {
                if ($category) {
                    $this->participants()->attach($participant);
                }
            }
        }

        return $participants;
    }

    /**
     * @return void
     */
    public function setAdConfigFromRequest(Request $request)
    {
        if ($request->has('ad_configs')) {
            /** @var User $user */
            $user = $request->user();
            foreach ($this->AdConfigs as $adConfig) {
                /** @var DeletesAdConfigs $deleter */
                $deleter = app(AdConfig::getContractName('delete'));
                $deleter->delete($user, $adConfig);
            }
            /** @var array{ad_configs?: array<int|string, array{offset?: int|null, ad_text?: string}>} $data */
            $data = $request->validate([
                'ad_configs.*.offset' => 'nullable|int|min:0',
                'ad_configs.*.ad_text' => 'nullable|string',
            ]);
            $adConfigs = $data['ad_configs'] ?? [];
            foreach (($this->city?->getActiveAdChannels() ?? []) as $key => $channel) {
                if (isset($adConfigs[$key]) && (($adConfigs[$key]['offset'] ?? 0) > 0)) {
                    /** @var CreatesAdConfigs $creator */
                    $creator = app(AdConfig::getContractName('create'));
                    $creator->create($user, $this, [
                        'service_id' => $this->id,
                        'slug' => $key,
                        'offset' => $adConfigs[$key]['offset'],
                        'ad_text' => $adConfigs[$key]['ad_text'] ?? '',
                    ]);
                }
            }
        }
    }

    /**
     * Return all participants with their category on the pivot record.
     *
     * @return BelongsToMany<User, $this>
     */
    public function participants()
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot('category');
    }

    /**
     * @return string
     */
    /**
     * Return the location label prefixed with its preposition.
     *
     * @return string Formatted location label.
     */
    public function atText()
    {
        $locationText = $this->formatLocationText();

        return $this->location instanceof Location ? self::stringify($this->location->atText()) : '('.(is_scalar($locationText) ? (string) $locationText : '').')';
    }

    /**
     * Record participant changes for the given field.
     *
     * @param  string  $field  Audited field.
     * @param  Collection<int|string, Model>  $originalRecords  Original related records.
     * @param  array<int, int>  $newIds  New related record IDs.
     * @return void
     */
    public function audit($field, $originalRecords, $newIds)
    {
        $originalIds = $originalRecords->pluck('id')->toArray();
    }

    /**
     * Return seating bookings associated with this service.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return mixed|string
     */
    public function ccLocationText()
    {
        return $this->cc_location ?: $this->formatLocationText();
    }

    /**
     * @param  bool  $emptyIfNotSet
     * @param  bool  $uhr
     * @param  string  $separator
     * @param  bool  $skipMinutes
     * @param  bool  $nbsp
     * @param  bool  $leadingZero
     * @return string
     */
    public function ccTimeText(
        $emptyIfNotSet = false,
        $uhr = true,
        $separator = ':',
        $skipMinutes = false,
        $nbsp = false,
        $leadingZero = false
    ) {
        if ($this->ccTime($emptyIfNotSet) === null) {
            return '';
        }

        return StringTool::timeText($this->ccTime(false), $uhr, $separator, $skipMinutes, $nbsp, $leadingZero);
    }

    /**
     * @param  bool  $emptyIfNotSet
     * @return Carbon|null
     *
     * @throws Exception
     */
    public function ccTime($emptyIfNotSet = false)
    {
        if ($this->cc_alt_time == '00:00:00') {
            $this->cc_alt_time = null;
        }
        if ($this->cc_alt_time === null) {
            if ($emptyIfNotSet) {
                return null;
            }
            $t = $this->time;
        } else {
            $t = $this->cc_alt_time;
        }

        return new Carbon($this->serviceDate()->format('Y-m-d').' '.$t);
    }

    /**
     * Clear the predicant requirement when pastors are assigned.
     *
     * @return void
     */
    public function checkIfPredicantNeeded()
    {
        if (count($this->pastors)) {
            $this->need_predicant = false;
            $this->save();
        }
    }

    /**
     * Return the city responsible for this service.
     *
     * @return BelongsTo<City, $this>
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Return the liturgical calendar day for this service.
     *
     * @return BelongsTo<Day, $this>
     */
    public function day()
    {
        return $this->belongsTo(Day::class);
    }

    /**
     * Return advertising configurations for this service.
     *
     * @return HasMany<AdConfig, $this>
     */
    public function AdConfigs()
    {
        return $this->hasMany(AdConfig::class);
    }

    /**
     * Get total number of participants + bookings
     *
     * @return int
     */
    public function estimatePeoplePresent()
    {
        $number = $this->participants->count();
        foreach ($this->bookings as $booking) {
            $number += $booking->number;
        }

        return $number;
    }

    /**
     * @param  string  $s  Time format.
     * @return string
     */
    public function formatTime($s)
    {
        return (strpos($s, '%') !== false) ? $this->formatDateTime()->isoFormat($s) : $this->formatDateTime()->format($s);
    }

    /**
     * @return Carbon
     *
     * @deprecated
     */
    protected function formatDateTime()
    {
        return $this->serviceDate()->copy()->setTimeZone('Europe/Berlin');
    }

    /**
     * Return funerals associated with this service.
     *
     * @return HasMany<Funeral, $this>
     */
    public function funerals()
    {
        return $this->hasMany(Funeral::class);
    }

    /**
     * Build the YouTube live-broadcast metadata for this service.
     *
     * @return Google_Service_YouTube_LiveBroadcastSnippet Broadcast metadata.
     */
    public function getBroadcastSnippet(): Google_Service_YouTube_LiveBroadcastSnippet
    {
        $broadcastSnippet = new Google_Service_YouTube_LiveBroadcastSnippet;
        $broadcastSnippet->setTitle(self::stringify($this->broadcastTitle));
        $broadcastSnippet->setDescription($this->broadcastDescription);
        $broadcastSnippet->setScheduledStartTime($this->videoTimeString);

        return $broadcastSnippet;
    }

    /**
     * Get all calendar items related to this service for CalDAV
     *
     * @param  bool  $includeAlternate  Include alternate calendar items
     * @param  bool  $includeRiteAnniversaries  Include one-year anniversary for rites
     * @return DAVCalendarItem[]
     */
    public function getCalendarItems(bool $includeAlternate, bool $includeRiteAnniversaries): array
    {
        $items = [
            $this->getCalendarItem(''),
        ];
        foreach (['baptisms', 'funerals', 'weddings'] as $rites) {
            foreach ($this->$rites as $rite) {
                foreach ($rite->getCalendarItems($includeAlternate, $includeRiteAnniversaries) as $item) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * Get a single calendar item related to this service for CalDAV
     */
    public function getCalendarItem(string $itemType): DAVCalendarItem
    {
        return new DAVCalendarItem(
            $this,
            $this->titleTextWithParticipants(),
            $this->serviceDate(),
            $this->serviceDate()->copy()->addHour(),
            self::stringify($this->formatLocationText()),
            'Im Pfarrplaner ansehen: '.route(
                'service.edit',
                $this->slug
            )."\n\n".DAVCalendarItem::AUTO_WARNING,
            '',
            [],
            ['Gottesdienst']
        );
    }

    /**
     * Get title plus some participants (for calendar entries)
     *
     * @param  string[]  $categories
     * @return string
     */
    public function titleTextWithParticipants($categories = ['P', 'O', 'M'])
    {
        $participants = [];
        foreach ($categories as $category) {
            $participants[] = $category.': '.$this->participantsText($category);
        }

        return trim($this->formatTitleText().' '.implode(' ', $participants));
    }

    /**
     * @return array
     */
    /**
     * Return participants grouped for calendar synchronization.
     *
     * @return array<string, array<int, array<string, mixed>>> Participant data.
     */
    public function getSyncableParticipantsArray()
    {
        $data = [];
        foreach ($this->participants as $participant) {
            $data[$participant->pivot->category][$participant->id]['category'] = $participant->pivot->category;
        }

        return $data;
    }

    /**
     * Build the YouTube video metadata for this service.
     *
     * @return Google_Service_YouTube_VideoSnippet Video metadata.
     */
    public function getVideoSnippet(): Google_Service_YouTube_VideoSnippet
    {
        $videoSnippet = new Google_Service_YouTube_VideoSnippet;
        $videoSnippet->setTitle(self::stringify($this->broadcastTitle));
        $videoSnippet->setDescription($this->broadcastDescription);
        $videoSnippet->setCategoryId('24');

        return $videoSnippet;
    }

    /**
     * Check if service description contains a specific text (case-insensitive!)
     *
     * @param  string  $text  Search for this text
     * @return bool True if text is in description
     */
    public function hasDescription(string $text): bool
    {
        return strpos(strtolower($this->formatDescriptionText()), strtolower($text)) !== false;
    }

    /**
     * @return bool
     */
    public function hasNonStandardCCLocation()
    {
        if ($this->special_location) {
            return true;
        } else {
            return $this->cc_location != $this->location?->cc_default_location;
        }
    }

    /**
     * Return the liturgy blocks belonging to this service.
     *
     * @return HasMany<Block, $this>
     */
    public function liturgyBlocks()
    {
        return $this->hasMany(Block::class);
    }

    /**
     * Return the location assigned to this service.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @param  string  $ministry  Ministry category.
     * @return BelongsToMany<User, $this>
     */
    public function ministryParticipants($ministry)
    {
        return $this->belongsToMany(User::class)->wherePivot('category', $ministry)->withTimestamps();
    }

    /**
     * Return recurrence occurrences associated with this service.
     *
     * @return HasMany<Occurence, $this>
     */
    public function occurences()
    {
        return $this->hasMany(Occurence::class);
    }

    /**
     * @return string
     */
    public function offeringText()
    {
        return $this->offeringGoal().($this->offering_type ? ' ('.$this->offering_type.')' : '');
    }

    /**
     * Get the effective offering goal, including city defaults for services, funerals and weddings.
     */
    public function offeringGoal(): string
    {
        if (trim((string) $this->offering_goal) !== '') {
            return (string) $this->offering_goal;
        }

        return $this->defaultOfferingValues()['goal'];
    }

    /**
     * Get the effective offering description, including city defaults when the goal falls back to a city default.
     */
    public function offeringDescription(): string
    {
        if (trim((string) $this->offering_description) !== '') {
            return (string) $this->offering_description;
        }

        return $this->defaultOfferingValues()['description'];
    }

    /**
     * Resolve default offering goal and description from the city, respecting funeral and wedding overrides.
     *
     * @return string[]
     */
    protected function defaultOfferingValues(): array
    {
        if ($this->city === null) {
            return ['goal' => '', 'description' => ''];
        }

        if ((count($this->funerals) > 0) && ($this->city->default_funeral_offering_goal != '')) {
            return [
                'goal' => (string) $this->city->default_funeral_offering_goal,
                'description' => (string) $this->city->default_funeral_offering_description,
            ];
        }

        if ((count($this->weddings) > 0) && ($this->city->default_wedding_offering_goal != '')) {
            return [
                'goal' => (string) $this->city->default_wedding_offering_goal,
                'description' => (string) $this->city->default_wedding_offering_description,
            ];
        }

        if ($this->city->default_offering_goal != '') {
            return [
                'goal' => (string) $this->city->default_offering_goal,
                'description' => (string) $this->city->default_offering_description,
            ];
        }

        return ['goal' => '', 'description' => ''];
    }

    /**
     * Return a compact one-line description of the service.
     *
     * @param  bool  $title  Whether to include the title.
     * @return string Compact service description.
     */
    public function oneLiner($title = false)
    {
        return ($title ? $this->formatTitleText(false).', ' : '').$this->serviceDate()->format(
            'd.m.Y'
        ).', '.$this->formatTimeText().', '.self::stringify($this->formatLocationText());
    }

    /**
     * @return BelongsToMany
     */
    /**
     * Return participants whose category names a custom ministry.
     *
     * @return BelongsToMany<User, $this>
     */
    public function participantsWithMinistry()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('category')
            ->wherePivotIn('category', ['P', 'O', 'M', 'A'], 'and', true)
            ->withTimestamps();
    }

    /**
     * Return the sermon associated with this service.
     *
     * @return BelongsTo<Sermon, $this>
     */
    public function sermon()
    {
        return $this->belongsTo(Sermon::class);
    }

    /**
     * Return the service groups containing this service.
     *
     * @return BelongsToMany<ServiceGroup, $this>
     */
    public function serviceGroups()
    {
        return $this->belongsToMany(ServiceGroup::class);
    }

    /**
     * Fill empty offering fields from the city defaults.
     *
     * @return void
     */
    public function setDefaultOfferingValues()
    {
        if ($this->city === null) {
            return;
        }
        if ($this->offering_goal == '') {
            $defaults = $this->defaultOfferingValues();
            $this->offering_goal = $defaults['goal'];
            $this->offering_description = $defaults['description'];
        }
    }

    /**
     * @param  string  $category  Participant category.
     * @param  array<int, int>  $participantIds  User identifiers.
     * @return array<string, mixed>
     */
    public function syncParticipantsByCategory($category, $participantIds)
    {
        $participants = [];
        foreach ($participantIds as $participantId) {
            $participants[$participantId] = ['category' => $category];
        }
        switch ($category) {
            case 'P':
                return $this->pastors()->sync($participants);
            case 'O':
                return $this->organists()->sync($participants);
            case 'M':
                return $this->sacristans()->sync($participants);
            case 'A':
                return $this->otherParticipants()->sync($participants);
        }

        return [];
    }

    /**
     * Return the tags assigned to this service.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * @return string
     */
    public function titleAndDescriptionCombinedText()
    {
        $description = [];
        $x = $this->formatTitleText(false, false);
        if ($x != 'Gottesdienst') {
            $description[] = $x;
        }
        $x = $this->formatDescriptionText();
        if ($x != '') {
            $description[] = $x;
        }

        return implode('; ', $description);
    }

    /**
     * @return Carbon
     */
    /**
     * Return the stored service date without formatting.
     *
     * @return Carbon Stored service date.
     */
    public function trueDate()
    {
        return $this->serviceDate();
    }

    /**
     * Remove computed append fields from the model serialization.
     *
     * @return void
     */
    public function unsetAdditionalFields()
    {
        $this->appends = [];
    }

    /**
     * Synchronize related cities from an incoming request.
     *
     * @param  Request  $request  Request containing related city IDs.
     * @return void
     */
    public function updateRelatedCitiesFromRequest(Request $request)
    {
        $cityIds = $request->get('related_cities', []);
        if (! is_array($cityIds)) {
            return;
        }
        $userCityIds = Auth::user()?->cities->pluck('id') ?? collect();
        $existing = $this->relatedCities->pluck('id');
        $result = $existing->filter(function ($item) use ($cityIds, $userCityIds) {
            if (! $userCityIds->contains($item)) {
                return true;
            }
            if (in_array($item, $cityIds)) {
                return true;
            }
        });
        foreach ($cityIds as $cityId) {
            if (! $result->contains($cityId)) {
                $result->push($cityId);
            }
        }
        $this->relatedCities()->sync($result);
    }

    /**
     * Return additional cities associated with this service.
     *
     * @return BelongsToMany<City, $this>
     */
    public function relatedCities()
    {
        return $this->belongsToMany(City::class);
    }

    /**
     * Return users linked to this service through the user pivot.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Return weddings associated with this service.
     *
     * @return HasMany<Wedding, $this>
     */
    public function weddings()
    {
        return $this->hasMany(Wedding::class);
    }

    /**
     * Convert a mixed value to a string.
     *
     * @param  mixed  $value  Value to convert.
     * @return string String representation.
     */
    private static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if ($value instanceof \Stringable) {
            return $value->__toString();
        }

        return '';
    }
}
