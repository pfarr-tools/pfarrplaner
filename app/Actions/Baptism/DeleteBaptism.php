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

namespace App\Actions\Baptism;

use App\Actions\AbstractDeleteAction;
use App\Contracts\Baptism\DeletesBaptisms;
use App\Events\Models\Baptism\DeletedBaptism;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class DeleteBaptism extends AbstractDeleteAction implements DeletesBaptisms
{
    protected string $redirectUrl = '';

    /**
     * @return string
     */
    public function redirectTo(): string
    {
        return $this->redirectUrl ?: route('home');
    }

    /**
     * @param User $user
     * @param Baptism $baptism
     * @return bool|null
     */
    public function delete(User $user, Baptism $baptism): ?bool
    {
        Gate::forUser($user)->authorize('delete', $baptism);

        $redirect = $baptism->service
            ? route('service.edit', ['service' => $this->getServiceSlug($baptism->service), 'tab' => 'rites'])
            : route('home');
        $result = $baptism->delete();
        $this->redirectUrl = $redirect;

        DeletedBaptism::dispatch($user, $baptism);
        $this->messages = ['success' => 'Die Taufe wurde gelöscht.'];

        return $result;
    }

    /**
     * @param Service $service
     * @return string
     */
    protected function getServiceSlug(Service $service): string
    {
        return $service->slug ?: $service->createSlug();
    }
}
