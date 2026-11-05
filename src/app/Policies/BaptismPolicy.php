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

namespace App\Policies;

use App\Models\People\User;
use App\Models\Rites\Baptism;
use Illuminate\Auth\Access\HandlesAuthorization;

class BaptismPolicy
{
    use HandlesAuthorization;

    public function index(User $user): bool
    {
        return $this->create($user);
    }

    protected function hasCityPermission(User $user, Baptism $baptism): bool
    {
        return $user->writableCities->pluck('id')->contains($baptism->city_id);
    }

    protected function mayChange(User $user, ?Baptism $baptism = null): bool
    {
        if (! $user->hasPermissionTo('gd-bearbeiten')) {
            return false;
        }
        if ($baptism === null) {
            return true;
        }

        return $this->hasCityPermission($user, $baptism);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('gd-bearbeiten');
    }

    public function update(User $user, ?Baptism $baptism = null): bool
    {
        return $this->mayChange($user, $baptism) || ($baptism !== null && $baptism->city_id === null && $this->create($user));
    }

    public function delete(User $user, ?Baptism $baptism = null): bool
    {
        return $this->update($user, $baptism);
    }

    public function restore(User $user, Baptism $baptism): bool
    {
        return $this->delete($user, $baptism);
    }

    public function forceDelete(User $user, Baptism $baptism): bool
    {
        return $this->delete($user, $baptism);
    }
}
