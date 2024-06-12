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

namespace App\Actions\Pool;

use App\Actions\AbstractUpdateAction;
use App\Contracts\Pool\UpdatesPools;
use App\Events\Models\Pool\UpdatedPool;
use App\Models\Leave\Pool;
use App\Models\People\User;
use Google\Service\PeopleService\Resource\People;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdatePool extends AbstractUpdateAction implements UpdatesPools
{

    /**
     * @inheritDoc
     */
    public function redirectTo(): string
    {
        return route('admin.pools.index');
    }

    public function update(User $user, Pool $pool, array $input): Pool
    {
        Gate::forUser($user)->authorize('update', $pool);
        $input = Validator::make($input, Pool::$validationRules)->validateWithBag('updatePool');
        $pool->update($input);
        $pool->cities()->sync($input['cities'] ?? '');
        $pool->users()->sync($input['users'] ?? '');
        $pool->refresh();
        UpdatedPool::dispatch($user, $pool);
        $this->messages = ['success' => 'Der Pool wurde geändert.'];
        return $pool;
    }
}
