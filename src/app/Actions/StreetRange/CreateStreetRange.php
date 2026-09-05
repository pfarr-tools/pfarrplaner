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

namespace App\Actions\StreetRange;

use App\Actions\AbstractCreateAction;
use App\Contracts\StreetRange\CreatesStreetRanges;
use App\Events\Models\StreetRange\CreatedStreetRange;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\StreetRange;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateStreetRange extends AbstractCreateAction implements CreatesStreetRanges
{
    public function redirectTo(): string
    {
        return '';
    }

    public function create(User $user, Parish $parish, array $input): StreetRange
    {
        Gate::forUser($user)->authorize('create', [StreetRange::class, $parish]);
        $input['parish_id'] = $parish->id;
        $input = Validator::make($input, StreetRange::$validationRules)->validateWithBag('createStreetRange');
        $streetRange = StreetRange::create($input);
        CreatedStreetRange::dispatch($user, $streetRange);
        $this->messages = ['success' => 'Der Straßenbereich wurde gespeichert.'];

        return $streetRange;
    }
}
