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

namespace App\Actions\Booking;

use App\Actions\AbstractCreateAction;
use App\Contracts\Booking\CreatesBookings;
use App\Events\Models\Booking\CreatedBooking;
use App\Models\People\User;
use App\Models\Seating\Booking;
use App\Models\Service;
use App\Rules\Seatable;
use App\Rules\SeatableFixed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateBooking extends AbstractCreateAction implements CreatesBookings
{
    protected Service $service;

    public function redirectTo(): string
    {
        return route('service.edit', ['service' => $this->service->slug, 'tab' => 'registrations']);
    }

    public function create(User $user, array $input): Booking
    {
        $this->service = Service::findOrFail($input['service_id']);
        Gate::forUser($user)->authorize('create', [Booking::class, $this->service]);
        $input = $this->validateAndNormalize($input);
        $input['code'] = $input['code'] ?? Booking::createCode();

        $booking = Booking::create($input);
        CreatedBooking::dispatch($user, $booking);
        $this->messages = ['success' => (($input['number'] ?? 1) == 1)
            ? 'Der Platz wurde reserviert.'
            : $input['number'] . ' zusammenhängende Plätze wurden reserviert.'];

        return $booking;
    }

    protected function validateAndNormalize(array $input): array
    {
        $rules = Booking::$validationRules;
        $rules['number'][] = new Seatable('booking_id');
        $rules['fixed_seat'][] = new SeatableFixed('booking_id');

        $input = Validator::make($input, $rules)->validateWithBag('createBooking');
        $input['fixed_seat'] = strtoupper($input['fixed_seat'] ?? '');
        $input['override_seats'] = strtoupper((string)($input['override_seats'] ?? ''));
        $input['override_split'] = strtoupper($input['override_split'] ?? '');

        return $input;
    }
}
