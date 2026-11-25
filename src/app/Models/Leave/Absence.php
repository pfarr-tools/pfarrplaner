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

namespace App\Models\Leave;

use App\Calendars\AbstractCalendarItem;
use App\DAV\DAVCalendarItem;
use App\DAV\HasDAVCalendarItems;
use App\Models\Attachment;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\People\User;
use App\Services\CalendarService;
use App\Services\NameService;
use App\Tools\StringTool;
use App\Traits\HasAttachmentsTrait;
use App\Traits\TracksDeletedByTrait;
use Carbon\Carbon;
use Database\Factories\Leave\AbsenceFactory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Class Absence
 *
 * @property bool $replacing Whether the current user is a replacement.
 * @property bool $canEdit Whether the current user may edit the absence.
 * @property bool $poolmaster Whether the absence represents a poolmaster duty.
 * @property int|null $poolmaster_id Related poolmaster identifier.
 * @property-read string $durationText Human readable duration.
 * @property-read string $replacementText Human readable replacement summary.
 * @property-read Collection<int, Attachment> $attachments Attached files.
 *
 * @method string durationText() Human readable duration.
 * @method string replacementText(string $prefix = '') Human readable replacement summary.
 */
class Absence extends Model implements HasDAVCalendarItems
{
    /** @use HasFactory<AbsenceFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksDeletedByTrait;

    public const STATUS_NEW = 0;

    public const STATUS_CHECKED = 1;

    public const STATUS_APPROVED = 2;

    public const STATUS_SELF_ADMINISTERED = 10;

    public const STATUS_SELF_ADMINISTERED_AND_APPROVED = 11;

    use HasAttachmentsTrait;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from',
        'to',
        'user_id',
        'reason',
        'replacement_notes',
        'workflow_status',
        'admin_notes',
        'approver_notes',
        'admin_id',
        'approver_id',
        'checked_at',
        'approved_at',
        'sick_days',
        'internal_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'from' => 'datetime',
        'to' => 'datetime',
        'checked_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'durationText',
        'replacementText',
    ];

    protected $with = [
        'user',
        'replacements',
        'attachments',
    ];

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Absence $absence) {
            if ($absence->isForceDeleting()) {
                $absence->replacements()->withTrashed()->get()->each->forceDelete();

                return;
            }

            $absence->replacements()->get()->each->delete();
        });

        static::restored(function (Absence $absence) {
            $absence->replacements()->withTrashed()->get()->each->restore();
        });
    }

    // ACCESSORS
    public function getDurationTextAttribute(): string
    {
        return StringTool::durationText($this->from, $this->to);
    }

    public function getReplacementTextAttribute(): string
    {
        return $this->formatReplacementText();
    }

    /**
     * Build the human readable replacement summary.
     *
     * @param  string  $prefix  Optional prefix.
     */
    protected function formatReplacementText(string $prefix = ''): string
    {
        $prefix = $prefix ? $prefix.' ' : '';
        $r = [];
        /** @var Replacement $replacement */
        foreach ($this->replacements as $replacement) {
            $r[] = $replacement->toText();
        }

        return $prefix.implode('; ', $r);
    }

    /**
     * Keep the legacy durationText() and replacementText() helper calls working.
     *
     * @param  string  $method  Method name.
     * @param  array<int, mixed>  $parameters  Method arguments.
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method === 'durationText') {
            return $this->getDurationTextAttribute();
        }

        if ($method === 'replacementText') {
            return $this->formatReplacementText($parameters[0] ?? '');
        }

        return parent::__call($method, $parameters);
    }
    // END ACCESSORS

    // MUTATORS
    /**
     * @param  mixed  $value  Date value.
     */
    public function setFromAttribute($value): void
    {
        $this->attributes['from'] = $this->normalizeDateAttribute($value);
    }

    /**
     * @param  mixed  $value  Date value.
     */
    public function setToAttribute($value): void
    {
        $this->attributes['to'] = $this->normalizeDateAttribute($value);
    }
    // END MUTATORS

    // SCOPES
    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeByPeriod(Builder $query, Carbon $start, Carbon $end)
    {
        return $query->where('from', '<=', $end)
            ->where('to', '>=', $start);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return mixed
     */
    public function scopeByUserAndPeriod(Builder $query, User $user, Carbon $start, Carbon $end)
    {
        return $query->where('user_id', $user->id)
            ->where('from', '<=', $end)
            ->where('to', '>=', $start);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeShowInCalendar(Builder $query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('show_vacations_with_services', 1);
        });
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeUserIsReplacement(Builder $query, User $user)
    {
        return $query->whereHas('replacements', function ($q) use ($user) {
            $q->whereHas('users', function ($q2) use ($user) {
                $q2->where('users.id', $user->id);
            });
        });
    }

    /**
     * @return mixed
     */
    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @param  User  $user  User requesting visibility.
     * @return Builder<static>
     */
    public function scopeVisibleForUser($query, $user)
    {
        $userId = $user->id;
        $users = $user->getViewableAbsenceUsers();

        return $query->whereIn('user_id', $users->pluck('id'))
            ->orWhere(
                function ($query2) use ($userId) {
                    $query2->whereHas(
                        'replacements',
                        function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        }
                    );
                }
            );
    }
    // END SCOPES

    // SETTERS
    // SETTERS
    // SETTERS
    /**
     * Get all absences keyed by days.
     *
     * @param  Collection<int, Absence>|\Illuminate\Database\Eloquent\Collection<int, Absence>  $absences
     * @param  Collection<array-key, int|string>|\Illuminate\Database\Eloquent\Collection<array-key, int|string>  $days
     * @return array<int|string, Collection<int, Absence>>
     */
    public static function getByDays($absences, $days)
    {
        $dayAbsences = [];
        foreach ($days as $day) {
            $dayAbsences[$day] = collect();
            foreach ($absences as $absence) {
                if ($absence->containsDate($day)) {
                    $dayAbsences[$day]->push($absence);
                }
            }
        }

        return $dayAbsences;
    }

    /**
     * Get an array of displayable dates for the AbsencePlanner
     *
     * @param  Carbon  $start  Start of displayed month
     * @return array Day data
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDaysForPlanner(Carbon $start)
    {
        $end = $start->copy()->addMonth()->subSecond();
        $holidays = CalendarService::getHolidays($start, $end);
        $days = [];
        while ($start <= $end) {
            $day = ['day' => $start->day, 'holiday' => false, 'date' => $start->copy()];
            foreach ($holidays as $holiday) {
                $day['holiday'] = $day['holiday'] || (($start >= $holiday['start']) && ($start <= $holiday['end']));
            }
            $days[$start->day] = $day;
            $start->addDay();
        }

        return $days;
    }

    /**
     * @return HasOne<User, $this>
     */
    public function approvedBy()
    {
        return $this->hasOne(User::class, 'id', 'approver_id');
    }

    /**
     * @return HasOne<User, $this>
     */
    public function checkedBy()
    {
        return $this->hasOne(User::class, 'id', 'admin_id');
    }

    /**
     * @param  mixed  $date  Date to check.
     * @return bool
     */
    public function containsDate($date)
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return ($date >= $this->from) && ($date <= $this->to);
    }

    /**
     * @return bool|null
     *
     * @throws Exception
     */
    public function delete()
    {
        return parent::delete();
    }

    /**
     * @return string
     */
    public function fullDescription()
    {
        $t = $this->formatReplacementText();
        if ($t) {
            $t = ' [V: '.$t.']';
        }

        return $this->requiredUser()->fullName().' ('.$this->reason.')'.$t;
    }

    /** @return Collection<int, int<0, max>> */
    public function getReplacingUserIds(): Collection
    {
        $ids = [];
        foreach ($this->replacements as $replacement) {
            foreach ($replacement->users as $user) {
                if (is_object($user)) {
                    $ids[] = $user->id;
                }
            }
        }

        return new Collection(array_unique($ids));
    }

    /**
     * @return HasMany<Replacement, $this>
     */
    public function replacements()
    {
        return $this->hasMany(Replacement::class);
    }

    /**
     * @param  array<int|string, mixed>  $data  Replacement data.
     * @return void
     */
    public function setupReplacements(User $user, $data)
    {
        $this->load('replacements');
        if (count($this->replacements)) {
            foreach ($this->replacements as $replacement) {
                app(Replacement::getContractName('delete'))->delete($user, $replacement);
            }
        }
        foreach ($data as $replacementData) {
            $replacement = app(Replacement::getContractName('create'))->create($user, $this, [
                'absence_id' => $this->id,
                'from' => max($this->normalizePlannerDateValue($replacementData['from']), $this->from),
                'to' => min($this->normalizePlannerDateValue($replacementData['to'], true), $this->to),
                'pool_id' => $replacementData['pool_id'] ?? null,
                'users' => $replacementData['users'] ?? [],
            ]);
            $replacementIds[] = $replacement->id;
        }
    }

    /**
     * Normalize ISO or legacy d.m.Y input to a UTC calendar date.
     */
    protected function normalizePlannerDateValue(mixed $value, bool $endOfDay = false): Carbon
    {
        if (is_string($value) && preg_match('/^\d{2}\.\d{2}\.\d{4}$/', trim($value))) {
            $date = Carbon::createFromFormat('d.m.Y', trim($value), 'Europe/Berlin');
            if ($date === null) {
                throw new \InvalidArgumentException('Invalid absence date.');
            }

            return $date
                ->setTime($endOfDay ? 23 : 0, $endOfDay ? 59 : 0, $endOfDay ? 59 : 0)
                ->setTimezone('UTC');
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            $date = Carbon::createFromFormat('Y-m-d', trim($value), 'Europe/Berlin');
            if ($date === null) {
                throw new \InvalidArgumentException('Invalid absence date.');
            }

            return $date
                ->setTime($endOfDay ? 23 : 0, $endOfDay ? 59 : 0, $endOfDay ? 59 : 0)
                ->setTimezone('UTC');
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', trim($value))) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', trim($value), 'Europe/Berlin');
            if ($date === null) {
                throw new \InvalidArgumentException('Invalid absence date.');
            }

            return $date
                ->setTimezone('UTC');
        }

        if (is_string($value) && preg_match('/T.*(?:Z|[+\-]\d{2}:\d{2})$/', trim($value))) {
            return Carbon::parse($value)->setTimezone('UTC');
        }

        $date = Carbon::parse($value)->setTimezone('Europe/Berlin');

        return $date
            ->copy()
            ->setTime($endOfDay ? 23 : 0, $endOfDay ? 59 : 0, $endOfDay ? 59 : 0)
            ->setTimezone('UTC');
    }

    /**
     * Calculate, how many days of this absence fall into a certain period
     * This is used for the colspans in calendar view
     *
     * @param  Carbon  $start  Start date.
     * @param  Carbon  $end  End date.
     * @return int Number of showable days.
     */
    public function showableDays($start, $end)
    {
        $myFrom = max($start, $this->from);
        $myTo = min($end, $this->to);

        return abs((int) $myTo->diffInDays($myFrom)) + 1;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalendarEvent(User $user)
    {
        $replacing = $this->user_id != $user->id;
        $categories = ['Pfarrplaner', ($replacing ? 'Vertretung' : 'Urlaub')];
        $status = AbstractCalendarItem::STATUS_FREE;

        if (! $replacing) {
            $statusTexts = [
                self::STATUS_NEW => ['Noch nicht genehmigt'],
                self::STATUS_CHECKED => ['Überprüft', 'Noch nicht genehmigt'],
                self::STATUS_APPROVED => ['Überprüft', 'Genehmigt'],
                self::STATUS_SELF_ADMINISTERED => [],
                self::STATUS_SELF_ADMINISTERED_AND_APPROVED => ['Genehmigt'],
            ];
            if (isset($statusTexts[$this->workflow_status])) {
                $categories = array_merge($categories, $statusTexts[$this->workflow_status]);
            }

            $title = $this->reason;
            $status = $status = AbstractCalendarItem::STATUS_OUT_OF_OFFICE;
        } else {
            $title = 'Vertretung für '.NameService::fromUser($this->requiredUser())->format(
                NameService::FIRST_LAST
            ).' ('.$this->reason.')';
        }

        $title .= $this->formatReplacementText(' V: ');

        return [
            'absence_'.$this->id => [
                'startDate' => $this->from,
                'endDate' => $this->to,
                'title' => $title,
                'description' => '<p><b>Vertretungsregelung:</b><br /> '.$this->formatReplacementText().'</p>',
                'categories' => $categories,
                'location' => '',
                'isAllDayEvent' => true,
                'freeBusy' => $status,
            ],
        ];
    }

    /**
     * @param  mixed  $value  Date value.
     */
    protected function normalizeDateAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->setTimezone('Europe/Berlin')->toDateString();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, CalendarConnection>
     */
    public function getConcernedCalendars()
    {
        // 1. user's own calendars
        $calendars = CalendarConnection::where('user_id', $this->user_id)->where('include_vacations', '>', 0)->get();

        // 2. replacements' calendars
        foreach ($this->replacements as $replacement) {
            foreach ($replacement->users as $user) {
                $moreCalendars = CalendarConnection::where('user_id', $user->id)->where(
                    'include_vacations',
                    '>',
                    0
                )->get();
                if ($moreCalendars) {
                    $calendars = $calendars->merge($moreCalendars);
                }
            }
        }

        return $calendars;
    }

    /**
     * @param  bool  $includeAlternate  Whether alternate items are included.\n     * @param  bool  $includeRiteAnniversaries  Whether rite anniversaries are included.\n     * @return array<int, DAVCalendarItem>
     */
    public function getCalendarItems(bool $includeAlternate, bool $includeRiteAnniversaries): array
    {
        return [
            $this->getCalendarItem(''),
        ];
    }

    /**
     * @param  string  $itemType  Calendar item type.\n     * @return DAVCalendarItem
     */
    public function getCalendarItem(string $itemType): DAVCalendarItem
    {
        $replacing = $this->user_id != (Auth::id() ?? 0);
        $categories = [($replacing ? 'Vertretung' : 'Abwesenheit')];
        $status = 'FREE';
        $busy = false;

        if (! $replacing) {
            $statusTexts = [
                self::STATUS_NEW => ['Noch nicht genehmigt'],
                self::STATUS_CHECKED => ['Überprüft', 'Noch nicht genehmigt'],
                self::STATUS_APPROVED => ['Überprüft', 'Genehmigt'],
                self::STATUS_SELF_ADMINISTERED => [],
                self::STATUS_SELF_ADMINISTERED_AND_APPROVED => ['Genehmigt'],
            ];
            if (isset($statusTexts[$this->workflow_status])) {
                $categories = array_merge($categories, $statusTexts[$this->workflow_status]);
            }

            $title = $this->reason;
            $busy = true;
            $status = 'OOF';
        } else {
            $title = 'Vertretung für '.NameService::fromUser($this->requiredUser())->format(
                NameService::FIRST_LAST
            ).' ('.$this->reason.')';
        }

        $title .= $this->formatReplacementText(' V: ');

        return new DAVCalendarItem(
            $this,
            $title,
            $this->from,
            $this->to->addDay()->startOfDay(),
            '',
            'Vertretungsregelung: '.$this->formatReplacementText(),
            '',
            ['busy' => $busy, 'busyStatus' => $status, 'allDay' => true],
            $categories,
        );
    }

    /**
     * @return string
     */
    protected function periodText(Carbon $from, Carbon $to)
    {
        if ($from->format('Ymd') == $to->format('Ymd')) {
            return ' am '.$from->format('%d. %B');
        }
        $format = ($from->month == $to->month) ? 'DD.' : 'DD. MMMM';
        if ($from->year != $to->year) {
            $format = 'DD. MMMM YYYY';
        }

        return ' vom '.$from->isoFormat($format).' bis '
            .$to->isoFormat($format == 'DD.' ? 'DD. MMMM' : $format);
    }

    /**
     * @return string
     */
    public function getDescriptiveTextAttribute()
    {
        $line = $this->requiredUser()->formatName(NameService::TITLE_FIRST_LAST).' ist'
            .$this->periodText($this->from, $this->to).' abwesend.';
        $replacements = collect();
        $replaceCtr = 0;
        foreach ($this->replacements as $replacement) {
            $replaceCtr += count($replacement->users);
            $replacements->push(
                (($replacement->from != $this->from) || ($replacement->to != $this->to) ?
                    $this->periodText($replacement->from, $replacement->to) : '')
                     .' '.$replacement->users->map(
                         function (User $item) {
                             return trim($item->formatName(NameService::TITLE_FIRST_LAST)
                                         .($item->phone ? ' (Telefon '.$item->phone.')' : ''));
                         }
                     )->join(', ', ' und ')
            );
        }
        if ($replaceCtr) {
            $line .= ' Die Vertretung '.($replaceCtr > 1 ? 'übernehmen' : 'übernimmt').' '
                .trim($replacements->join('; ', ' und ')).'.';
        }

        return $line;
    }

    /** Return the owner required by absence calendar and description output. */
    protected function requiredUser(): User
    {
        return $this->user ?? throw new \LogicException('Absence has no associated user.');
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getFullReplacementArrayAttribute()
    {
        $records = [];
        foreach ($this->replacements as $replacement) {
            $users = $replacement->toUserArray();
            if (! $users) {
            }
            $records = array_merge($users, $records);
        }
        if (! count($records)) {
            $records = [
                $this->from->format('Ymd').$this->to->format('Ymd') => [
                    'from' => $this->from,
                    'to' => $this->to,
                    'period' => StringTool::durationText($this->from, $this->to),
                    'poolmaster' => false,
                    'user' => null,
                ],
            ];
        }

        return $records;
    }
}
