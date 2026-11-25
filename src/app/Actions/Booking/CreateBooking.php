<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
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

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $user, array $input): Booking
    {
        /** @var int|string $serviceId */
        $serviceId = $input['service_id'];
        $this->service = Service::findOrFail((int) $serviceId);
        Gate::forUser($user)->authorize('create', [Booking::class, $this->service]);
        $input = $this->validateAndNormalize($input);
        $input['code'] = $input['code'] ?? Booking::createCode();

        $booking = Booking::create($input);
        CreatedBooking::dispatch($user, $booking);
        /** @var int|string $number */
        $number = $input['number'] ?? 1;
        $this->messages = ['success' => ($number == 1)
            ? 'Der Platz wurde reserviert.'
            : $number.' zusammenhängende Plätze wurden reserviert.'];

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function validateAndNormalize(array $input): array
    {
        /** @var array{number: array<int, mixed>, fixed_seat: array<int, mixed>} $rules */
        $rules = Booking::$validationRules;
        $rules['number'][] = new Seatable('booking_id');
        $rules['fixed_seat'][] = new SeatableFixed('booking_id');

        /** @var array<string, mixed> $input */
        $input = Validator::make($input, $rules)->validateWithBag('createBooking');
        $fixedSeat = $input['fixed_seat'] ?? '';
        $input['fixed_seat'] = strtoupper(is_string($fixedSeat) ? $fixedSeat : '');
        $overrideSeats = $input['override_seats'] ?? '';
        $input['override_seats'] = strtoupper(is_scalar($overrideSeats) ? (string) $overrideSeats : '');
        $overrideSplit = $input['override_split'] ?? '';
        $input['override_split'] = strtoupper(is_string($overrideSplit) ? $overrideSplit : '');

        return $input;
    }
}
