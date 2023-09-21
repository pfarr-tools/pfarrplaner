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

namespace App\Http\Controllers\DAV;

use App\Http\Controllers\DAV\Backends\AuthBackend;
use App\Http\Controllers\DAV\Backends\CalendarBackend;
use App\Http\Controllers\DAV\Backends\PrincipalBackend;
use Sabre\CalDAV\CalendarRoot;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\DAVACL\Plugin as DAVACLPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\Server;
use Sabre\DAVACL\PrincipalCollection;

class DAVController extends \App\Http\Controllers\Controller
{

    public function handle()
    {
        $authBackend = new AuthBackend();
        $authBackend->setRealm(config('app.name') . ' DAV');

        $principalBackend = new PrincipalBackend();
        $calendarBackend = new CalendarBackend();

        $server = new Server([
                                 new PrincipalCollection($principalBackend),
                                 new CalendarRoot($principalBackend, $calendarBackend),
                             ]);

        $server->setBaseUri('/dav');

        $server->addPlugin(new AuthPlugin($authBackend));
        $server->addPlugin(new CalDAVPlugin());
        $server->addPlugin(new DAVACLPlugin());
        $server->addPlugin(new BrowserPlugin());

        $server->start();
    }

}
