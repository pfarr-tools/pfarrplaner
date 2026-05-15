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

namespace App\Actions\Baptism;

use App\Actions\AbstractUpdateAction;
use App\Contracts\Baptism\UpdatesBaptisms;
use App\Events\Models\Baptism\UpdatedBaptism;
use App\Events\ServiceUpdated;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateBaptism extends AbstractUpdateAction implements UpdatesBaptisms
{
    use NormalizesBaptismData;

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
     * @param array $input
     * @return Baptism
     */
    public function update(User $user, Baptism $baptism, array $input): Baptism
    {
        Gate::forUser($user)->authorize('update', $baptism);

        $normalized = $this->normalizeInput($user, array_merge($baptism->only($baptism->getFillable()), $input));
        $validated = Validator::make($normalized, Baptism::$validationRules)->validateWithBag('updateBaptism');

        $baptism->update($validated);
        if ($baptism->service) {
            ServiceUpdated::dispatch($baptism->service, $baptism->service->participants);
            $this->redirectUrl = route('service.edit', ['service' => $this->getServiceSlug($baptism->service), 'tab' => 'rites']);
        } else {
            $this->redirectUrl = route('home');
        }

        UpdatedBaptism::dispatch($user, $baptism);
        $this->messages = ['success' => 'Die Änderungen wurden gespeichert.'];

        return $baptism;
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
