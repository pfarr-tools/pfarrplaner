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

namespace App\Actions\Parish;

use App\Actions\AbstractDeleteAction;
use App\Contracts\Parish\DeletesParishes;
use App\Events\Models\Parish\DeletedParish;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Support\Facades\Gate;

class DeleteParish extends AbstractDeleteAction implements DeletesParishes
{
    /** @var City $city */
    protected City $city;

    /**
     * Get return route
     * @return string
     */
    public function redirectTo(): string
    {
        return route('admin.city.edit', ['modelId' => $this->city->id, 'tab' => 'parishes']);
    }

    /**
     * Delete a parish
     * @param User $user
     * @param Parish $parish
     * @return Parish|bool|null
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(User $user, Parish $parish)
    {
        Gate::forUser($user)->authorize('delete', $parish);
        $this->city = $parish->owningCity;
        DeletedParish::dispatch($user, $parish);
        $this->messages = ['success' => 'Das Pfarramt wurde gelöscht.'];
        return $parish->delete();

    }

}
