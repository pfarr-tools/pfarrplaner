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

namespace App\Calendars\Exchange;


use Carbon\Carbon;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangeDescriptionsType;
use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\LegacyFreeBusyType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Type\BodyContentType;
use jamesiarmes\PhpEws\Type\BodyType;
use jamesiarmes\PhpEws\Type\CalendarItemType;
use jamesiarmes\PhpEws\Type\ItemChangeType;
use jamesiarmes\PhpEws\Type\ItemIdType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\SetItemFieldType;
use App\Calendars\AbstractCalendarItem;
use App\Calendars\Exchange\Exceptions\CalendarNotSetException;
use jamesiarmes\PhpEws\Type\TimeZoneDefinitionType;
use jamesiarmes\PhpEws\Type\TimeZoneType;

class ExchangeCalendarItem extends AbstractCalendarItem
{
    protected $changeKey = '';

    /** @var ExchangeCalendar */
    protected $calendar = null;

    protected $fieldURI = [
        'startDate' => UnindexedFieldURIType::CALENDAR_START,
        'endDate' => UnindexedFieldURIType::CALENDAR_END,
        'title' => UnindexedFieldURIType::ITEM_SUBJECT,
        'location' => UnindexedFieldURIType::CALENDAR_LOCATION,
        'description' => UnindexedFieldURIType::ITEM_BODY,
        'categories' => UnindexedFieldURIType::ITEM_CATEGORIES,
        'isAllDayEvent' => UnindexedFieldURIType::CALENDAR_IS_ALL_DAY_EVENT,
        'freeBusy' => UnindexedFieldURIType::CALENDAR_LEGACY_FREE_BUSY_STATUS,
    ];

    public static $busyStatus = [
        0 => LegacyFreeBusyType::FREE,
        1 => LegacyFreeBusyType::TENTATIVE,
        2 => LegacyFreeBusyType::BUSY,
        3 => LegacyFreeBusyType::OUT_OF_OFFICE,
        4 => LegacyFreeBusyType::WORKING_ELSEWHERE,
        5 => LegacyFreeBusyType::NO_DATA,
    ];

    public function __construct($data = [], ExchangeCalendar $calendar = null)
    {
        if (null !== $calendar) {
            $this->setCalendar($calendar);
        }
        $this->setPropertiesFromArray($data);
    }

    public function toExchangeItem()
    {
        $event = new CalendarItemType();
        $event->Start = $this->startDate->format('c');
        $event->End = $this->endDate->format('c');
        $event->Subject = $this->title ?: '';
        $event->Body = new BodyType();
        $event->Body->BodyType = BodyTypeType::HTML;
        $event->Body->_ = $this->description ?: '';
        $event->Location = $this->location;
        $event->Categories = $this->categories;
        $event->IsAllDayEvent = $this->isAllDayEvent;
        $event->LegacyFreeBusyStatus = self::$busyStatus[$this->freeBusy] ?? LegacyFreeBusyType::BUSY;

        // do not set reminder for past items!
        if ($this->startDate <= Carbon::now()) {
            $event->ReminderIsSet = false;
        }

        return $event;
    }

    public static function fromExchangeItem(CalendarItemType $item, $calendar = null): ExchangeCalendarItem
    {
        $busyStatus = array_search($item->LegacyFreeBusyStatus, self::$busyStatus) ?: 2;

        $data = [
            'startDate' => new Carbon($item->Start),
            'endDate' => new Carbon($item->End),
            'title' => $item->Subject ?? '',
            'location' => $item->Location ?? '',
            'description' => $item->Body->_ ?? '',
            'categories' => $item->Categories,
            'isAllDayEvent' => $item->IsAllDayEvent,
            'ID' => $item->ItemId->Id,
            'changeKey' => $item->ItemId->ChangeKey,
            'freeBusy' => $busyStatus,
        ];
        return new self($data, $calendar);
    }

    public function delete()
    {
        if (null === $this->calendar) {
            throw new CalendarNotSetException();
        }
        return $this->calendar->delete($this->getID());
    }

    public function update($data)
    {
        if (null === $this->calendar) {
            throw new CalendarNotSetException();
        }
        return $this->calendar->update($this->getID(), $data);
    }

    public function getItemChangeType($data)
    {
        $change = new ItemChangeType();
        $change->ItemId = new ItemIdType();
        $change->ItemId->Id = $this->getID();
        $change->ItemId->ChangeKey = $this->getChangeKey();
        $change->Updates = new NonEmptyArrayOfItemChangeDescriptionsType();

        foreach ($data as $key => $value) {
            if ($this->isDateField($key)) {
                $value = (new Carbon($value))->format('c');
            }
            $field = new SetItemFieldType();
            $field->FieldURI = new PathToUnindexedFieldType();
            $field->FieldURI->FieldURI = $this->fieldURI[$key];
            $field->CalendarItem = new CalendarItemType();

            switch ($key) {
                case 'startDate':
                    $field->CalendarItem->Start = is_object($value) ? $value->format('c') : $value;
                    break;
                case 'endDate':
                    $field->CalendarItem->End = is_object($value) ? $value->format('c') : $value;
                    break;
                case 'title':
                    $field->CalendarItem->Subject = $value ?? '';
                    break;
                case 'description':
                    if (!$field->CalendarItem->Body) $field->CalendarItem->Body = new BodyType();
                    $field->CalendarItem->Body->BodyType = BodyTypeType::HTML;
                    $field->CalendarItem->Body->_ = $value ?? '';
                    break;
                case 'location':
                    $field->CalendarItem->Location = $value ?? '';
                    break;
                case 'categories':
                    $field->CalendarItem->Categories = $value ?? '';
                    break;
                case 'isAllDayEvent':
                    $field->CalendarItem->IsAllDayEvent = $value ?? false;
                    break;
                case 'freeBusy':
                    $field->CalendarItem->LegacyFreeBusyStatus = self::$busyStatus[$value] ?? LegacyFreeBusyType::BUSY;
                    break;
            }
            $change->Updates->SetItemField[] = $field;
        }
        return $change;
    }

    /**
     * @return string
     */
    public function getChangeKey(): string
    {
        return $this->changeKey;
    }

    /**
     * @param string $changeKey
     */
    public function setChangeKey(string $changeKey): void
    {
        $this->changeKey = $changeKey;
    }


}
