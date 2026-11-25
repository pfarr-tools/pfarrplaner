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
use Illuminate\Support\Collection;

class AbstractSeatFinder
{
    /** @var string */
    public $viewName = '';

    /** @var bool */
    public $hasSeats = true;

    /** @var Service */
    protected $service = null;

    /** @var bool */
    protected $preloadExistingBookings = true;

    /** @var array<string, string> */
    protected $errors = [];

    /**
     * SeatFinder constructor.
     *
     * @param  bool  $preloadExistingBookings  Optional: Preload existing bookings?
     */
    public function __construct(Service $service, $preloadExistingBookings = true)
    {
        $this->service = $service;
        $this->preloadExistingBookings = $preloadExistingBookings;
    }

    /**
     * Check if a specified number of people can still be seated in the location
     *
     * @param  int  $number  Number of people
     * @param  string  $fixedSeat  Desired fixed seating position
     * @param  string  $overrideSeats  Override the default maximum number of seats for a fixed position
     * @param  string  $overrideSplit  Override the default splitting configuration for a fixed position
     * @param  Collection<int, Booking>  $bookings  Existing bookings used for the check
     * @return bool True if this booking can be added
     */
    public function find($number, $fixedSeat = '', $overrideSeats = '', $overrideSplit = '', $bookings = null)
    {
        return false;
    }

    /**
     * Check if seating is still possible after proposed changes to a booking
     *
     * @param  mixed  $data  Proposed booking data.
     * @return bool True if this booking can be changed
     */
    public function checkIfBookingCanBeChanged(Booking $booking, $data): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function finalList()
    {
        return [];
    }

    /**
     * Get all rows available for seating
     *
     * @return array<string, mixed>
     */
    public function getAllRows()
    {
        return [];
    }

    /**
     * Get the maximum seating capacity of the venue
     *
     * @return int Number of seats
     */
    public function maximumCapacity()
    {
        return 0;
    }

    /**
     * @param  Collection<int, Booking>|null  $bookings  Bookings to consider.
     * @return int
     */
    public function remainingCapacity($bookings = null)
    {
        return 0;
    }

    /**
     * Get the full row record, even for a partial row
     *
     * @param  string|int  $key  Grid key.
     * @return mixed
     */
    public function getFullRow($key) {}

    /**
     * Get the collected error messages
     *
     * @return array<string, string> Error messages indexed by error key.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<string, string>  $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * Add an error message
     */
    protected function setError(string $key, string $error): void
    {
        $this->errors[$key] = $error;
    }

    /**
     * Get the free seats text (usually something like "remaining / max")
     *
     * @return string
     */
    public function freeSeatsText()
    {
        return $this->remainingCapacity().' / '.$this->maximumCapacity();
    }

    /**
     * Get a seating table arranged by booking codes
     *
     * @return array<int|string, mixed> Seating table entries indexed by booking code or grid key.
     */
    public function getSeatingTable(): array
    {
        return [];
    }

    /**
     * Get the remaining capacity as formatted text
     *
     * @param  string  $format  Optional sprintf-compatible format receiving the remaining capacity.
     * @return string Formatted remaining capacity.
     */
    public function remainingCapacityText($format = ''): string
    {
        if ($format == '') {
            return (string) $this->remainingCapacity();
        }

        return sprintf($format, $this->remainingCapacity());
    }
}
