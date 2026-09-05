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

namespace App\Calendars;


use Carbon\Carbon;

class AbstractCalendarItem
{
    public const STATUS_FREE = 0;
    public const STATUS_TENTATIVE = 1;
    public const STATUS_BUSY = 2;
    public const STATUS_OUT_OF_OFFICE = 3;
    public const STATUS_WORKING_ELSEWHERE = 4;
    public const NO_DATA = 5;


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

    protected $isAllDayEvent = false;

    protected $calendar = null;

    protected $dates = ['startDate', 'endDate'];



    public function __construct(array $data = [], $calendar = null)
    {
        if (null !== $calendar) $this->setCalendar($calendar);
        $this->setPropertiesFromArray($data);
    }

    protected function setPropertiesFromArray(array $data)
    {
        foreach ($data as $key => $val) {
            if (in_array($key, $this->dates)) $val = new Carbon($val);
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
     * @return Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    /**
     * @param Carbon $startDate
     */
    public function setStartDate(Carbon $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @return Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    /**
     * @param Carbon $endDate
     */
    public function setEndDate(Carbon $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param string[] $categories
     */
    public function setCategories($categories): void
    {
        $this->categories = (array)$categories;
    }

    /**
     * @return bool
     */
    public function isIsAllDayEvent(): bool
    {
        return $this->isAllDayEvent;
    }

    /**
     * @param bool $isAllDayEvent
     */
    public function setIsAllDayEvent(bool $isAllDayEvent): void
    {
        $this->isAllDayEvent = $isAllDayEvent;
    }

    /**
     * @return int
     */
    public function getFreeBusy(): int
    {
        return $this->freeBusy;
    }

    /**
     * @param int $freeBusy
     */
    public function setFreeBusy(int $freeBusy): void
    {
        $this->freeBusy = $freeBusy;
    }


    /**
     * @return null
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * @param null $calendar
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
     * @param mixed $ID
     */
    public function setID($ID): void
    {
        $this->ID = $ID;
    }

    public function isDateField($field): bool {
        return in_array($field, $this->dates);
    }

}
