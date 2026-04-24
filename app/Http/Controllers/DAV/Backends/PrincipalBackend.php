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

use App\Models\People\User;
use Illuminate\Support\Str;
use Sabre\DAV\Server;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;

class PrincipalBackend implements BackendInterface
{

    /**
     * This is the prefix that will be used to generate principal urls.
     *
     * @var string
     */
    public const PRINCIPAL_PREFIX = 'principals/';


    public function getPrincipals()
    {
        $principals = [];
        foreach (User::whereHas('calendarConnections')->get() as $user) {
            $principals[] = [
                'uri'               => static::PRINCIPAL_PREFIX.$user->email,
                '{DAV:}displayname' => $user->fullName(),
                '{'.Server::NS_SABREDAV.'}email-address' => $user->email,
                'read-only' => true,
            ];
        }
        return $principals;
    }

    public function getPrincipalsByPrefix($prefixPath)
    {
        $prefixPath = Str::finish($prefixPath, '/');
        return array_filter($this->getPrincipals(), function ($principal) use ($prefixPath) {
            return ! $prefixPath || strpos($principal['uri'], $prefixPath) == 0;
        });
    }

    public function getPrincipalByPath($path)
    {
        foreach ($this->getPrincipalsByPrefix(static::PRINCIPAL_PREFIX) as $principal) {
            if ($principal['uri'] === $path) {
                return $principal;
            }
        }

        return [];
    }

    public function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch)
    {
        // not implemented
    }

    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        $result = [];
        $principals = $this->getPrincipalsByPrefix($prefixPath);
        if (! $principals) {
            return $result;
        }

        foreach ($principals as $principal) {
            $ok = false;
            foreach ($searchProperties as $key => $value) {
                if ($principal[$key] == $value) {
                    $ok = true;
                } elseif ($test == 'allof') {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $result[] = $principal['uri'];
            }
        }

        return $result;
    }

    public function findByUri($uri, $principalPrefix)
    {
        dd('findByUri', $uri, $principalPrefix, Str::after($uri, $principalPrefix));
    }

    public function getGroupMemberSet($principal)
    {
        $principal = $this->getPrincipalByPath($principal);
        if (! $principal) {
            return [];
        }

        return [
            $principal['uri'],
        ];
    }

    public function getGroupMembership($principal)
    {
        return $this->getGroupMemberSet($principal);
    }

    public function setGroupMemberSet($principal, array $members)
    {
        // not implemented
    }
}
