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

namespace App\Actions\AdChannel;

use App\Actions\AbstractCreateAction;
use App\Contracts\AdChannel\CreatesAdChannels;
use App\Contracts\AdChannel\CreatesLocations;
use App\Events\Models\AdChannel\CreatedAdChannel;
use App\Models\Ads\AdChannel;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdChannel extends AbstractCreateAction implements CreatesAdChannels
{

    /** @var City $city */
    protected $city;


    /**
     * Get the return route
     * @return string
     */
    public function redirectTo(): string
    {
        return route('admin.city.edit', ['modelId' => $this->city->id, 'tab' => 'ads']);
    }

    /**
     * Create a new AdChannel
     * @param User $user
     * @param array $input
     * @return AdChannel
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(User $user, array $input)
    {
        $input = Validator::make($input, AdChannel::$validationRules)->validateWithBag('createTag');
        $this->city = City::findOrFail($input['city_id']);
        Gate::forUser($user)->authorize('create', [AdChannel::class, $this->city]);
        if (empty($input['code'] ?? '')) {
            $input['code'] = Str::slug($input['name']);
        }
        $input['slug'] = Str::slug($input['name']);
        $adChannel = AdChannel::create($input);
        CreatedAdChannel::dispatch($user, $adChannel);
        $this->messages = ['success' => 'Der neue Werbekanal wurde gespeichert.'];
        return $adChannel;
    }
}
