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

use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class ParishPolicy
 */
class ParishPolicy
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
     * @return bool
     */
    public function index(User $user)
    {
        if ($user->hasRole('Administrator:in')) {
            return true;
        }
        if ($user->isLocalAdmin) {
            return true;
        }

        return $user->hasPermissionTo('pfarramt-bearbeiten');
    }

    public function create(User $user, ?City $city = null): bool
    {
        if ($city === null) {
            return count($user->writableCities) > 0;
        }

        return $user->writableCities->contains($city);
    }

    /**
     * @return bool
     */
    public function update(User $user, Parish $model)
    {
        if ($user->adminCities->contains($model->owningCity)) {
            return true;
        }

        return $user->hasPermissionTo('pfarramt-bearbeiten');
    }

    /**
     * @return bool
     */
    public function delete(User $user, Parish $model)
    {
        if ($user->adminCities->contains($model->owningCity)) {
            return true;
        }

        return $user->hasPermissionTo('pfarramt-bearbeiten');
    }

    /**
     * @return bool
     */
    public function restore(User $user, Parish $model)
    {
        return $user->hasPermissionTo('pfarramt-bearbeiten');
    }

    /**
     * @return bool
     */
    public function forceDelete(User $user, Parish $model)
    {
        return $user->hasPermissionTo('pfarramt-bearbeiten');
    }
}
