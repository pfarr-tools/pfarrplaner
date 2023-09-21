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

namespace App\Calendars\SharePoint;


use Carbon\Carbon;
use App\Calendars\AbstractCalendarItem;
use Thybag\SharePointAPI;

class SharePointCalendarItem extends AbstractCalendarItem
{
    /** @var SharePointCalendar */
    protected $calendar = null;

    protected function setPropertiesFromArray($data) {
        parent::setPropertiesFromArray($data);
        if (isset($data['id'])) {
            $this->setID($data['id']);
        }
    }

    protected function setProperty($property, $datum)
    {
        if (!in_array($property, $this->dates)) {
            $this->$property = $datum;
        } else {
            $this->$property = new Carbon($datum);
        }
    }

    public static function fromSharepointListItem($data, $calendar = null) {
        $myData = [
            'startDate' => $data['eventdate'],
            'endDate' => $data['enddate'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'location' => $data['location'] ?? '',
            'ID' => $data['id'] ?? null,
        ];
        return new self($myData, $calendar);
    }

    public function toArray()
    {
        return  [
            'EventDate' => $this->getStartDate()->format('Y-m-d H:i:s'),
            'EndDate' => $this->getEndDate()->format('Y-m-d H:i:s'),
            'Title' => $this->getTitle(),
            'Description' => $this->getDescription(),
            'Location' => $this->getLocation(),
        ];
    }

    public function update(array$data) {
        $this->setPropertiesFromArray($data);
        if (null !== $this->calendar) return $this->calendar->update($this);
        return $this;
    }

    public function delete() {
        if (null !== $this->calendar) $this->calendar->delete($this);
    }

    public function setStartDate(Carbon $date): void
    {
        $this->startDate = $date->setTimezone('Europe/Berlin');
    }

    public function setEndDate(Carbon $date): void
    {
        $this->endDate = $date->setTimezone('Europe/Berlin');
    }

}
