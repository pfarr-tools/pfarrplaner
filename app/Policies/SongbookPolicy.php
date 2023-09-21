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

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Liturgy\Songbook;

class SongbookPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can view the index of all songbooks.
     *
     * @param User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin || $user->isLocalAdmin || $user->hasPermissionTo('liederbuecher-bearbeiten');
    }


    /**
     * Determine whether the user can view the songbook.
     *
     * @param User $user
     * @param Songbook $songbook
     * @return mixed
     */
    public function view(User $user, Songbook $songbook)
    {
        return $user->isAdmin || $user->isLocalAdmin || $user->hasPermissionTo('liederbuecher-bearbeiten');
    }

    /**
     * Determine whether the user can create services.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isAdmin || $user->isLocalAdmin || $user->hasPermissionTo('liederbuecher-bearbeiten');
    }

    /**
     * Determine whether the user can update the songbook.
     *
     * @param User $user
     * @param Songbook $songbook
     * @return mixed
     */
    public function update(User $user, Songbook $songbook)
    {
        return $user->isAdmin || $user->isLocalAdmin || $user->hasPermissionTo('liederbuecher-bearbeiten');
    }

    /**
     * Determine whether the user can delete the songbook.
     *
     * @param User $user
     * @param Songbook $songbook
     * @return mixed
     */
    public function delete(User $user, Songbook $songbook)
    {
        return $user->isAdmin || $user->isLocalAdmin || $user->hasPermissionTo('liederbuecher-bearbeiten');
    }

    /**
     * Determine whether the user can restore the songbook.
     *
     * @param User $user
     * @param Songbook $songbook
     * @return mixed
     */
    public function restore(User $user, Songbook $songbook)
    {
        return $user->isAdmin || $user->isLocalAdmin || $user->hasPermissionTo('liederbuecher-bearbeiten');
    }

    /**
     * Determine whether the user can permanently delete the songbook.
     *
     * @param User $user
     * @param Songbook $songbook
     * @return mixed
     */
    public function forceDelete(User $user, Songbook $songbook)
    {
        return $user->isAdmin || $user->isLocalAdmin || $user->hasPermissionTo('liederbuecher-bearbeiten');
    }

}
