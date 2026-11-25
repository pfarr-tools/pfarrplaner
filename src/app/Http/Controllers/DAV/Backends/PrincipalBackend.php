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

use App\Models\People\User;
use Illuminate\Support\Str;
use Sabre\DAV\PropPatch;
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

    /**
     * List all principals that have calendar connections.
     *
     * @return list<array{uri: string, ...}> Principals in Sabre's principal backend format.
     */
    public function getPrincipals(): array
    {
        $principals = [];
        foreach (User::whereHas('calendarConnections')->get() as $user) {
            $principals[] = [
                'uri' => static::PRINCIPAL_PREFIX.$user->email,
                '{DAV:}displayname' => $user->fullName(),
                '{'.Server::NS_SABREDAV.'}email-address' => $user->email,
                'read-only' => true,
            ];
        }

        return $principals;
    }

    /**
     * List principals whose URI starts with the given prefix.
     *
     * @param  string  $prefixPath  Principal URI prefix to filter for.
     * @return array<int, array{uri: string, ...}> Matching principals in Sabre's principal backend format.
     */
    public function getPrincipalsByPrefix($prefixPath): array
    {
        $prefixPath = Str::finish($prefixPath, '/');

        return array_filter($this->getPrincipals(), function ($principal) use ($prefixPath) {
            return ! $prefixPath || strpos($principal['uri'], $prefixPath) == 0;
        });
    }

    /**
     * Return the principal with the given URI.
     *
     * @param  string  $path  Full principal URI to look up.
     * @return array{uri: string, ...}|array{} The principal, or an empty array if none matches.
     */
    public function getPrincipalByPath($path): array
    {
        foreach ($this->getPrincipalsByPrefix(static::PRINCIPAL_PREFIX) as $principal) {
            if ($principal['uri'] === $path) {
                return $principal;
            }
        }

        return [];
    }

    /**
     * Handle property updates, which are not supported for external principals.
     *
     * @param  string  $path  Full principal URI to update.
     * @param  PropPatch  $propPatch  Requested property mutations.
     */
    public function updatePrincipal($path, PropPatch $propPatch): void
    {
        // not implemented
    }

    /**
     * Search principals by WebDAV property values.
     *
     * @param  string  $prefixPath  Principal URI prefix to search in.
     * @param  array<string, mixed>  $searchProperties  Property names mapped to expected values.
     * @param  string  $test  Combination mode: 'allof' or 'anyof'.
     * @return list<string> URIs of the matching principals.
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof'): array
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

    /**
     * @param  string  $uri  Principal URI.
     * @param  string  $principalPrefix  Prefix to search in.
     * @return string|null
     */
    public function findByUri($uri, $principalPrefix)
    {
        $principalPrefix = Str::finish($principalPrefix, '/');

        foreach ($this->getPrincipalsByPrefix($principalPrefix) as $principal) {
            if (($principal['{'.Server::NS_SABREDAV.'}email-address'] ?? null) === $uri) {
                return $principal['uri'];
            }
        }

        return null;
    }

    /**
     * Return the group members of a principal, which is the principal itself.
     *
     * @param  string  $principal  Full principal URI to look up.
     * @return list<string> URIs of the group members.
     */
    public function getGroupMemberSet($principal): array
    {
        $principal = $this->getPrincipalByPath($principal);
        if (! $principal) {
            return [];
        }

        return [
            $principal['uri'],
        ];
    }

    /**
     * Return the groups a principal belongs to, which is the principal itself.
     *
     * @param  string  $principal  Full principal URI to look up.
     * @return list<string> URIs of the groups.
     */
    public function getGroupMembership($principal): array
    {
        return $this->getGroupMemberSet($principal);
    }

    /**
     * Handle group member updates, which are not supported for external principals.
     *
     * @param  string  $principal  Full principal URI to update.
     * @param  list<string>  $members  URIs of the requested group members.
     */
    public function setGroupMemberSet($principal, array $members): void
    {
        // not implemented
    }
}
