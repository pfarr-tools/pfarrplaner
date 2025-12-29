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

namespace App\Actions\Location;

use App\Actions\AbstractAction;
use App\Actions\AbstractUpdateAction;
use App\Contracts\Location\UpdatesLocations;
use App\Events\Models\Location\UpdatedLocation;
use App\Models\People\User;
use App\Models\Location;
use App\Models\Places\City;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UpdateLocation extends AbstractUpdateAction implements UpdatesLocations
{

    /** @var City $city */
    protected $city;

    /**
     * Get return route
     * @return string
     */
    public function redirectTo(): string
    {
        return route('admin.city.edit', ['modelId' => $this->city->id, 'tab' => 'locations']);
    }

    /**
     * Update a location
     * @param User $user
     * @param Location $location
     * @param array $input
     * @return Location
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(User $user, Location $location, array $input)
    {
        Gate::forUser($user)->authorize('update', $location);
        $this->city = $location->city;
        $input = Validator::make($input, Location::$validationRules)->validateWithBag('updateLocation');
        $location->update($input);
        UpdatedLocation::dispatch($user, $location);
        $this->messages = ['success' => 'Der Ort wurde geändert.'];
        return $location;
    }
}
