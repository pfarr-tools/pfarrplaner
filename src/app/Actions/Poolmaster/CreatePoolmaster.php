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

namespace App\Actions\Poolmaster;

use App\Actions\AbstractCreateAction;
use App\Contracts\Poolmaster\CreatesPoolmasters;
use App\Events\Models\Poolmaster\CreatedPoolmaster;
use App\Models\Leave\Poolmaster;
use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreatePoolmaster extends AbstractCreateAction implements CreatesPoolmasters
{

    /** @var Poolmaster|null  */
    protected $poolmaster = null;

    /**
     * @inheritDoc
     */
    public function redirectTo(): string
    {
        return route('absences.index', $this->poolmaster ? ['year' => Carbon::parse($this->poolmaster->start)->year, 'month' => Carbon::parse($this->poolmaster->start)->month]: null);
    }

    /**
     * @param User $user
     * @param array $input
     * @return Poolmaster
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(User $user, array $input): Poolmaster
    {
        Gate::forUser($user)->authorize('create', Poolmaster::class);
        $input = Validator::make($input, Poolmaster::$validationRules)->validateWithBag('createPoolmaster');
        $this->poolmaster = Poolmaster::create($input);
        CreatedPoolmaster::dispatch($user, $this->poolmaster);
        $this->messages = ['success' => 'Der Einsatz als Poolmaster:in wurde gespeichert.'];
        return $this->poolmaster;
    }
}
