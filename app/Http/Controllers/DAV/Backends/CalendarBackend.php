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

namespace App\Http\Controllers\DAV\Backends;

use App\DAV\DAVCalendarItem;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

class CalendarBackend extends AbstractBackend implements SyncSupport
{

    protected function getUser($principalUri) {
        return User::where('email', Str::after($principalUri, PrincipalBackend::PRINCIPAL_PREFIX))->first();
    }

    public function getCalendarsForUser($principalUri)
    {
        $calendars = [];
        foreach ($this->getUser($principalUri)->calendarConnections as $calendarConnection) {
            $calendars[] = [
                'id' => $calendarConnection->id,
                'uri' => Str::slug($calendarConnection->title),
                'principaluri' => $principalUri,
                '{http://sabredav.org/ns}sync-token' => $calendarConnection->getCurrentSyncToken(),
                '{DAV:}displayname' => $calendarConnection->title,
                '{'.CalDAVPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet([]),
                'read-only' => true,
            ];
        }
        return $calendars;
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        // not implemented
    }

    public function deleteCalendar($calendarId)
    {
        // not implemented
    }

    public function getCalendarObjects($calendarId)
    {
        /** @var CalendarConnection $calendarConnection */
        $calendarConnection = CalendarConnection::findOrFail($calendarId);

        $objects = [];
        foreach ($calendarConnection->getCalendarItems() as $item) $objects[] = $item->toCalendarObject();

        return $objects;
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        return DAVCalendarItem::fromUri($objectUri)->toCalendarObject();
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        // not implemented
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        // not implemented
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
        // not implemented
    }


    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        dd('getChangesForCalendar', $calendarId, $syncLevel, $syncLevel, $limit, Carbon::parse($syncToken), Carbon::now());
    }
}
