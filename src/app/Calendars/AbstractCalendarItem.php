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

namespace App\Calendars;

use Carbon\Carbon;

/**
 * @method void update(array<string, mixed> $record)
 * @method void delete()
 */
class AbstractCalendarItem
{
    public const STATUS_FREE = 0;

    public const STATUS_TENTATIVE = 1;

    public const STATUS_BUSY = 2;

    public const STATUS_OUT_OF_OFFICE = 3;

    public const STATUS_WORKING_ELSEWHERE = 4;

    public const NO_DATA = 5;

    /** @var mixed ID */
    protected $ID;

    /** @var Carbon Start date */
    protected $startDate = null;

    /** @var Carbon End date */
    protected $endDate = null;

    /** @var string Title */
    protected $title = '';

    /** @var string Description */
    protected $description = '';

    /** @var string Location */
    protected $location = '';

    /** @var string[] Categories */
    protected $categories = [];

    /** @var int LegacyFreeBusyStatus */
    protected $freeBusy = self::STATUS_BUSY;

    /** @var bool Is all day event */
    protected $isAllDayEvent = false;

    /** @var mixed Calendar */
    protected $calendar = null;

    /** @var string[] Date fields */
    protected $dates = ['startDate', 'endDate'];

    /**
     * @param  array<string, mixed>  $data
     * @param  mixed  $calendar
     */
    public function __construct(array $data = [], $calendar = null)
    {
        if ($calendar !== null) {
            $this->setCalendar($calendar);
        }
        $this->setPropertiesFromArray($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function setPropertiesFromArray(array $data): void
    {
        foreach ($data as $key => $val) {
            if (in_array($key, $this->dates)) {
                /** @var \DateTimeInterface|float|int|string|null $date */
                $date = $val;
                $val = new Carbon($date);
            }
            if (property_exists($this, $key)) {
                $setter = 'set'.ucfirst($key);
                if (method_exists($this, $setter)) {
                    $this->$setter($val ?? '');
                } else {
                    $this->$key = $val ?? '';
                }
            }
        }
    }

    /**
     * Getstartdate.
     */
    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    /**
     * Setstartdate.
     */
    public function setStartDate(Carbon $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * Getenddate.
     */
    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    /**
     * Setenddate.
     */
    public function setEndDate(Carbon $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * Gettitle.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Settitle.
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Getdescription.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Setdescription.
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Getlocation.
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Setlocation.
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param  string[]  $categories
     */
    public function setCategories($categories): void
    {
        $this->categories = (array) $categories;
    }

    /**
     * Isisalldayevent.
     */
    public function isIsAllDayEvent(): bool
    {
        return $this->isAllDayEvent;
    }

    /**
     * Setisalldayevent.
     */
    public function setIsAllDayEvent(bool $isAllDayEvent): void
    {
        $this->isAllDayEvent = $isAllDayEvent;
    }

    /**
     * Getfreebusy.
     */
    public function getFreeBusy(): int
    {
        return $this->freeBusy;
    }

    /**
     * Setfreebusy.
     */
    public function setFreeBusy(int $freeBusy): void
    {
        $this->freeBusy = $freeBusy;
    }

    /**
     * @return mixed
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * @param  mixed  $calendar
     */
    public function setCalendar($calendar): void
    {
        $this->calendar = $calendar;
    }

    /**
     * @return mixed
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * @param  mixed  $ID
     */
    public function setID($ID): void
    {
        $this->ID = $ID;
    }

    /**
     * @param  string  $field
     */
    public function isDateField($field): bool
    {
        return in_array($field, $this->dates);
    }
}
