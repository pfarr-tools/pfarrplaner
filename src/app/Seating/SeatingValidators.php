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

namespace App\Seating;

use App\Models\Seating\Booking;
use App\Models\Service;
use Illuminate\Validation\Validator;

class SeatingValidators
{
    public static function register(): void {}

    /**
     * Check whether a booking can be seated in the service.
     *
     * @param  string  $attribute  Name of the validated attribute.
     * @param  mixed  $value  Value of the validated attribute.
     * @param  array<int, mixed>  $parameters  Parameters passed to the validation rule.
     * @param  Validator  $validator  Validator instance.
     * @return bool True if the booking can be seated.
     */
    public static function seatable(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        /** @var array<string, mixed> $data */
        $data = $validator->getData();
        if ($attribute == 'number') {
            $data['fixed_seat'] = '';
        } else {
            $data['number'] = 1;
        }

        /** @var Service $service */
        $service = Service::findOrFail($data['service_id']);
        /** @var AbstractSeatFinder $seatFinder */
        $seatFinder = $service->getSeatFinder();

        $parameter = $parameters[0] ?? null;
        if ((is_string($parameter) || is_int($parameter)) && isset($data[$parameter])) {
            // existing booking
            $booking = Booking::find($data[$parameter]);
            if (! $booking instanceof Booking) {
                return false;
            }
            $success = $seatFinder->checkIfBookingCanBeChanged($booking, $data);
        } else {
            // new booking
            /** @var int $number */
            $number = $data['number'];
            /** @var string $fixedSeat */
            $fixedSeat = $data['fixed_seat'];
            /** @var string $overrideSeats */
            $overrideSeats = $data['override_seats'];
            /** @var string $overrideSplit */
            $overrideSplit = $data['override_split'];
            $success = $seatFinder->find($number, $fixedSeat, $overrideSeats, $overrideSplit);
        }

        return $success;
    }
}
