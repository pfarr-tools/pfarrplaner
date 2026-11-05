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

namespace App\Actions\City;

use App\Actions\AbstractCreateAction;
use App\Contracts\City\CreatesCities;
use App\Events\Models\City\CreatedCity;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateCity extends AbstractCreateAction implements CreatesCities
{
    public function redirectTo(): string
    {
        return City::getSingleRoute('web', 'index');
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(User $user, array $input): City
    {
        Gate::forUser($user)->authorize('create', City::class);
        $input = Validator::make($input, City::$validationRules)->validateWithBag('createCity');
        $city = City::create($input);
        $city->setChildrenFromInput($input);
        CreatedCity::dispatch($user, $city);
        $this->messages = ['success' => 'Die neue Kirchengemeinde wurde gespeichert.'];

        return $city;
    }
}
