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

namespace App\Http\Controllers\DAV\Backends;

use App\DAV\DAVCalendarItem;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\People\User;
use Illuminate\Support\Str;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

class CalendarBackend extends AbstractBackend implements SyncSupport
{
    /**
     * Find the user identified by a DAV principal URI.
     *
     * @param  string  $principalUri  Principal URI whose e-mail address identifies the user.
     * @return User|null The matching user, or null if the principal has no user.
     */
    protected function getUser($principalUri): ?User
    {
        return User::where('email', Str::after($principalUri, PrincipalBackend::PRINCIPAL_PREFIX))->first();
    }

    /**
     * List the calendars owned by a DAV principal.
     *
     * @param  string  $principalUri  Principal URI whose calendars are requested.
     * @return list<array<string, mixed>> Calendar descriptions in Sabre's CalDAV backend format.
     */
    public function getCalendarsForUser($principalUri): array
    {
        $calendars = [];
        $user = $this->getUser($principalUri);
        if ($user === null) {
            return [];
        }
        foreach ($user->calendarConnections as $calendarConnection) {
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

    /**
     * Handle a calendar creation request, which is not implemented for external calendars.
     *
     * @param  string  $principalUri  Principal URI the new calendar would belong to.
     * @param  string  $calendarUri  Requested URI of the new calendar.
     * @param  array<string, mixed>  $properties  WebDAV properties supplied with the request.
     * @return mixed No calendar identifier is produced because nothing is created.
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        // not implemented
    }

    /**
     * Handle a calendar deletion request, which is not implemented for external calendars.
     *
     * @param  mixed  $calendarId  Identifier of the calendar to delete.
     */
    public function deleteCalendar($calendarId): void
    {
        // not implemented
    }

    /**
     * List all objects of an external calendar.
     *
     * @param  mixed  $calendarId  Identifier of the calendar to read.
     * @return list<array<string, mixed>> Calendar objects in Sabre's backend format.
     */
    public function getCalendarObjects($calendarId): array
    {
        /** @var CalendarConnection $calendarConnection */
        $calendarConnection = CalendarConnection::findOrFail($calendarId);

        $objects = [];
        foreach ($calendarConnection->getCalendarItems() as $item) {
            $objects[] = $item->toCalendarObject();
        }

        return $objects;
    }

    /**
     * Return a single calendar object identified by its URI.
     *
     * @param  mixed  $calendarId  Identifier of the containing calendar.
     * @param  string  $objectUri  Basename of the requested calendar object.
     * @return array<string, mixed> Calendar object data including its iCalendar payload.
     */
    public function getCalendarObject($calendarId, $objectUri): array
    {
        $item = DAVCalendarItem::fromUri($objectUri);
        abort_unless($item !== null, 404);

        return $item->toCalendarObject();
    }

    /**
     * Handle an object creation request, which is not implemented for external calendars.
     *
     * @param  mixed  $calendarId  Identifier of the target calendar.
     * @param  string  $objectUri  Basename requested for the new object.
     * @param  string  $calendarData  iCalendar payload supplied by the client.
     * @return string|null No ETag is returned because the object is not stored.
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        // not implemented

        return null;
    }

    /**
     * Handle an object update request, which is not implemented for external calendars.
     *
     * @param  mixed  $calendarId  Identifier of the target calendar.
     * @param  string  $objectUri  Basename of the object to update.
     * @param  string  $calendarData  Updated iCalendar payload supplied by the client.
     * @return string|null No ETag is returned because the object is not changed.
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        // not implemented

        return null;
    }

    /**
     * Handle an object deletion request, which is not implemented for external calendars.
     *
     * @param  mixed  $calendarId  Identifier of the target calendar.
     * @param  string  $objectUri  Basename of the object to delete.
     */
    public function deleteCalendarObject($calendarId, $objectUri): void
    {
        // not implemented
    }

    /**
     * Report the current sync token because incremental changes are not tracked.
     *
     * The returned array contains the keys syncToken, added, modified and deleted;
     * the three change lists are always empty for external calendars.
     *
     * @param  mixed  $calendarId  Identifier of the calendar to synchronize.
     * @param  string|null  $syncToken  Sync token supplied by the client; ignored here.
     * @param  int  $syncLevel  Requested sync depth; ignored here.
     * @param  int|null  $limit  Suggested maximum number of changes; ignored here.
     * @return array<string, mixed> Current sync token together with empty change lists.
     */
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null): array
    {
        return [
            'syncToken' => CalendarConnection::query()->whereKey($calendarId)->firstOrFail()->getCurrentSyncToken(),
            'added' => [],
            'modified' => [],
            'deleted' => [],
        ];
    }
}
