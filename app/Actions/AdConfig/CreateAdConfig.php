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

namespace App\Actions\AdConfig;

use App\Actions\AbstractCreateAction;
use App\Contracts\AdConfig\CreatesAdConfigs;
use App\Events\Models\AdConfig\CreatedAdConfig;
use App\Models\Ads\AdConfig;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateAdConfig extends AbstractCreateAction implements CreatesAdConfigs
{
    public function redirectTo(): string
    {
        return '';
    }

    public function create(User $user, Service $service, array $input): AdConfig
    {
        Gate::forUser($user)->authorize('create', [AdConfig::class, $service]);
        $input['service_id'] = $service->id;
        $input = Validator::make($input, AdConfig::$validationRules)->validateWithBag('createAdConfig');
        $adConfig = AdConfig::create($input);
        CreatedAdConfig::dispatch($user, $adConfig);
        $this->messages = ['success' => 'Die Anzeigenkonfiguration wurde gespeichert.'];

        return $adConfig;
    }
}
