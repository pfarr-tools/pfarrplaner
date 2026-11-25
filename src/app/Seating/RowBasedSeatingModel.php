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
use App\Models\Seating\SeatingRow;

class RowBasedSeatingModel extends AbstractSeatingModel
{
    /** @var string Display name of the row-based seating model. */
    protected $title = 'Reihenbasiert';

    /**
     * @param  SeatingRow  $row  Row to inspect.\n     * @return int
     */
    public function rowCapacity(SeatingRow $row)
    {
        // check if we still have space in this row
        if ($x = count($row->bookings)) {
            // all divided seats booked?
            if ($x >= $row->divides_into) {
                return 0;
            }
            // only one booking, but complete row?
            if ($row->bookings[0]->number > 2) {
                return 0;
            }

            return ($row->divides_into - $x) * 2;
        } else {
            return $row->seats ?? 0;
        }
    }

    /**
     * @param  SeatingRow  $row  Row to inspect.\n     * @param  mixed  $number  Number of seats.\n     * @return mixed
     */
    public function impact(SeatingRow $row, $number)
    {
        if ($number > $row->seats) {
            return -1;
        }

        // check if we still have space in this row
        if ($x = count($row->bookings)) {
            // partially booked -> no more than two people possible
            if ($number > 2) {
                return -1;
            }
            // all divided seats booked?
            if ($x >= $row->divides_into) {
                return -1;
            }
            // only one booking, but complete row?
            if ($row->bookings[0]->number > 2) {
                return -1;
            }
        }

        if (($row->divides_into > 1) && ($number <= 2)) {
            $remaining = ($row->divides_into - 1) * 2;

            return $row->seats - $remaining;
        } else {
            return $row->seats ?? 0;
        }
    }

    /**
     * @param  SeatingRow  $row  Row to book.\n     * @param  Booking  $booking  Booking to place.\n     * @return mixed
     */
    public function book(SeatingRow $row, Booking $booking)
    {
        $row->bookings[] = $booking;
    }

    /**
     * @param  SeatingRow  $row  Row to check.\n     * @return bool
     */
    public function isAvailable(SeatingRow $row)
    {
        if ($x = count($row->bookings)) {
            if ($x >= $row->divides_into) {
                return false;
            }
            if ($row->bookings[0]->number > 2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine the number of remaining seats in the given row.
     *
     * @param  SeatingRow  $row  Row to inspect.
     * @return int Remaining seats, or -1 if the number cannot be determined.
     */
    public function remainingSeats(SeatingRow $row)
    {
        if (! $this->isAvailable($row)) {
            return 0;
        }
        if (count($row->bookings) == 0) {
            return $row->seats ?? 0;
        }

        return -1;
    }
}
