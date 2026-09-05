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

use App\Actions\AbstractUpdateAction;
use App\Contracts\Booking\UpdatesBookings;
use App\Events\Models\Booking\UpdatedBooking;
use App\Models\People\User;
use App\Models\Seating\Booking;
use App\Rules\Seatable;
use App\Rules\SeatableFixed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateBooking extends AbstractUpdateAction implements UpdatesBookings
{
    protected Booking $booking;

    public function redirectTo(): string
    {
        return route('service.edit', ['service' => $this->booking->service->slug, 'tab' => 'registrations']);
    }

    public function update(User $user, Booking $booking, array $input): Booking
    {
        $this->booking = $booking;
        Gate::forUser($user)->authorize('update', $booking);
        $input['service_id'] = $booking->service_id;
        $input['booking_id'] = $booking->id;
        $input = $this->validateAndNormalize($input);
        unset($input['booking_id']);

        $booking->update($input);
        UpdatedBooking::dispatch($user, $booking);
        $this->messages = ['success' => 'Die Buchung wurde erfolgreich geändert.'];

        return $booking;
    }

    protected function validateAndNormalize(array $input): array
    {
        $rules = Booking::$validationRules;
        $rules['number'][] = new Seatable('booking_id');
        $rules['fixed_seat'][] = new SeatableFixed('booking_id');

        $input = Validator::make($input, $rules)->validateWithBag('updateBooking');
        $input['fixed_seat'] = strtoupper($input['fixed_seat'] ?? '');
        $input['override_seats'] = strtoupper((string)($input['override_seats'] ?? ''));
        $input['override_split'] = strtoupper($input['override_split'] ?? '');

        return $input;
    }
}
