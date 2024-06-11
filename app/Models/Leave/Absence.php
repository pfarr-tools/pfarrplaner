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

namespace App\Models\Leave;

use App\Calendars\AbstractCalendarItem;
use App\DAV\DAVCalendarItem;
use App\DAV\HasDAVCalendarItems;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\People\User;
use App\Services\CalendarService;
use App\Services\NameService;
use App\Tools\StringTool;
use App\Traits\HasAttachmentsTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Class Absence
 * @package App
 */
class Absence extends Model implements HasDAVCalendarItems
{

    use HasFactory;

    public const STATUS_NEW = 0;
    public const STATUS_CHECKED = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_SELF_ADMINISTERED = 10;
    public const STATUS_SELF_ADMINISTERED_AND_APPROVED = 11;

    use HasAttachmentsTrait;


    /**
     * @var string[]
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
     * @var string[]
     */
    protected $casts = [
        'from' => 'datetime',
        'to' => 'datetime',
        'checked_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'durationText',
        'replacementText'
    ];

    protected $with = [
        'user',
        'replacements',
        'attachments'
    ];

// ACCESSORS
    public function getDurationTextAttribute()
    {
        return $this->durationText();
    }

    /**
     * @return string
     */
    public function durationText()
    {
        return StringTool::durationText($this->from, $this->to);
    }

    public function getReplacementTextAttribute()
    {
        return $this->replacementText();
    }

    /**
     * @param string $prefix
     * @return string
     */
    public function replacementText($prefix = '')
    {
        $prefix = $prefix ? $prefix . ' ' : '';
        $r = [];
        /** @var Replacement $replacement */
        foreach ($this->replacements as $replacement) {
            $r[] = $replacement->toText();
        }
        return $prefix . join('; ', $r);
    }
// END ACCESSORS

// SCOPES
    /**
     * @param Builder $query
     * @param Carbon $start
     * @param Carbon $end
     * @return Builder
     */
    public function scopeByPeriod(Builder $query, Carbon $start, Carbon $end)
    {
        return $query->where('from', '<=', $end)
            ->where('to', '>=', $start);
    }

    /**
     * @param Builder $query
     * @param User $user
     * @param Carbon $start
     * @param Carbon $end
     * @return mixed
     */
    public function scopeByUserAndPeriod(Builder $query, User $user, Carbon $start, Carbon $end)
    {
        return $query->where('user_id', $user->id)
            ->where('from', '<=', $end)
            ->where('to', '>=', $start);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeShowInCalendar(Builder $query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('show_vacations_with_services', 1);
        });
    }

    public function scopeUserIsReplacement(Builder $query, User $user)
    {
        return $query->whereHas('replacements', function ($q) use ($user) {
            $q->whereHas('users', function ($q2) use ($user) {
                $q2->where('users.id', $user->id);
            });
        });
    }


    /**
     * @param $query
     * @param $user
     * @return mixed
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
     * Get all absences keyed by days
     * @param Collection|\Illuminate\Database\Eloquent\Collection $absences
     * @param Collection|\Illuminate\Database\Eloquent\Collection $days
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
     * @param Carbon $start Start of displayed month
     * @return array Day data
     */
    public static function getDaysForPlanner(Carbon $start)
    {
        $end = $start->copy()->addMonth(1)->subSecond(1);
        $holidays = CalendarService::getHolidays($start, $end);
        $days = [];
        while ($start <= $end) {
            $day = ['day' => $start->day, 'holiday' => false, 'date' => $start->copy()];
            foreach ($holidays as $holiday) {
                $day['holiday'] = $day['holiday'] || (($start >= $holiday['start']) && ($start <= $holiday['end']));
            }
            $days[$start->day] = $day;
            $start->addDay(1);
        }
        return $days;
    }

    public function approvedBy()
    {
        return $this->hasOne(User::class, 'id', 'approver_id');
    }

    public function checkedBy()
    {
        return $this->hasOne(User::class, 'id', 'admin_id');
    }

    public function containsDate($date)
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        return ($date >= $this->from) && ($date <= $this->to);
    }

    /**
     * @return bool|null
     * @throws Exception
     */
    public function delete()
    {
        foreach ($this->replacements as $replacement) {
            $replacement->delete();
        }
        return parent::delete();
    }

    /**
     * @return string
     */
    public function fullDescription()
    {
        $t = $this->replacementText();
        if ($t) {
            $t = ' [V: ' . $t . ']';
        }
        return $this->user->fullName() . ' (' . $this->reason . ')' . $t;
    }

    /**
     * @return Collection
     */
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
        return (new Collection(array_unique($ids)));
    }

    /**
     * @return HasMany
     */
    public function replacements()
    {
        return $this->hasMany(Replacement::class);
    }

    /**
     * @param $data
     */
    public function setupReplacements($data)
    {
        $this->load('replacements');
        if (count($this->replacements)) {
            foreach ($this->replacements as $replacement) {
                $replacement->delete();
            }
        }
        foreach ($data as $replacementData) {
            $replacement = new Replacement(
                [
                    'absence_id' => $this->id,
                    'from' => max(Carbon::createFromFormat('d.m.Y', $replacementData['from']), $this->from),
                    'to' => min(Carbon::createFromFormat('d.m.Y', $replacementData['to']), $this->to),
                    'pool_id' => $replacementData['pool_id'] ?? null,
                ]
            );
            $replacement->save();
            if (isset($replacementData['users'])) {
                foreach ($replacementData['users'] as $id => $userData) {
                    $replacementData['users'][$id] = $userData['id'] ?? $userData;
                }
                $replacement->users()->sync($replacementData['users']);
            }
            $replacementIds[] = $replacement->id;
        }
    }

    /**
     * Calculate, how many days of this absence fall into a certain period
     * This is used for the colspans in calendar view
     * @param Carbon $start Start date
     * @param Carbon $end End date
     */
    public function showableDays($start, $end)
    {
        $myFrom = max($start, $this->from);
        $myTo = min($end, $this->to);
        return $myTo->diffInDays($myFrom) + 1;
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCalendarEvent(User $user)
    {
        $replacing = $this->user_id != $user->id;
        $categories = ['Pfarrplaner', ($replacing ? 'Vertretung' : 'Urlaub')];
        $status = AbstractCalendarItem::STATUS_FREE;

        if (!$replacing) {
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
            $title = 'Vertretung für ' . NameService::fromUser($this->user)->format(
                    NameService::FIRST_LAST
                ) . ' (' . $this->reason . ')';
        }

        $title .= $this->replacementText(' V: ');


        return [
            'absence_' . $this->id => [
                'startDate' => $this->from,
                'endDate' => $this->to,
                'title' => $title,
                'description' => '<p><b>Vertretungsregelung:</b><br /> ' . $this->replacementText() . '</p>',
                'categories' => $categories,
                'location' => '',
                'isAllDayEvent' => true,
                'freeBusy' => $status,
            ]
        ];
    }


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


    public function getCalendarItems(bool $includeAlternate, bool $includeRiteAnniversaries): array
    {
        return [
            $this->getCalendarItem(''),
        ];
    }

    public function getCalendarItem(string $itemType): DAVCalendarItem
    {
        $replacing = $this->user_id != Auth::user()->id;
        $categories = [($replacing ? 'Vertretung' : 'Abwesenheit')];
        $status = 'FREE';
        $busy = false;

        if (!$replacing) {
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
            $title = 'Vertretung für ' . NameService::fromUser($this->user)->format(
                    NameService::FIRST_LAST
                ) . ' (' . $this->reason . ')';
        }

        $title .= $this->replacementText(' V: ');

        return new DAVCalendarItem(
            $this,
            $title,
            $this->from,
            $this->to->addDay(1)->startOfDay(),
            '',
            'Vertretungsregelung: ' . $this->replacementText(),
            '',
            ['busy' => $busy, 'busyStatus' => $status, 'allDay' => true],
            $categories,
        );
    }

    protected function periodText(Carbon $from, Carbon $to)
    {
        if ($from->format('Ymd') == $to->format('Ymd')) {
            return ' am ' . $from->format('%d. %B');
        }
        $format = ($from->month == $to->month) ? '%d.' : '%d. %B';
        if ($from->year != $to->year) {
            $format = '%d. %B %Y';
        }
        return ' vom ' . $from->formatLocalized($format) . ' bis '
            . $to->formatLocalized($format == '%d.' ? '%d. %B' : $format);
    }

    public function getDescriptiveTextAttribute()
    {
        $line = $this->user->formatName(NameService::TITLE_FIRST_LAST) . ' ist'
            .$this->periodText($this->from, $this->to). ' abwesend.';
        $replacements = collect();
        $replaceCtr = 0;
        foreach ($this->replacements as $replacement) {
            $replaceCtr += count($replacement->users);
            $replacements->push(
                (($replacement->from != $this->from) || ($replacement->to != $this->to) ?
                    $this->periodText($replacement->from, $replacement->to) : '')
                     . ' ' . $replacement->users->map(
                    function (User $item) {
                        return trim($item->formatName(NameService::TITLE_FIRST_LAST)
                                    .($item->phone ? ' (Telefon '.$item->phone.')' : ''));
                    }
                )->join(', ', ' und ')
            );
        }
        if ($replaceCtr) {
            $line .= ' Die Vertretung ' . ($replaceCtr > 1 ? 'übernehmen' : 'übernimmt') . ' '
                .trim($replacements->join('; ',' und ')) . '.';
        }
        return $line;
    }
}
