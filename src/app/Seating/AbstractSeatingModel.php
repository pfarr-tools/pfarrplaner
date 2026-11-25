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

/**
 * Base class for the available seating models.
 *
 * @property string $title Display name of the seating model.
 */
class AbstractSeatingModel
{
    /** @var string Display name of the seating model. */
    protected $title;

    /** @var array<int, Booking> */
    protected $bookings = [];

    public function getKey(): string
    {
        return str_replace('App\\Seating\\', '', basename(get_called_class()));
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param  string  $title  Title.
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Determine the number of seats a booking occupies in the given row.
     *
     * @param  int  $number  Number of people to be seated.
     * @return int Occupied seats, or -1 if the row cannot be used.
     */
    public function impact(SeatingRow $row, $number)
    {
        return -1;
    }

    /** @return void */
    public function book(SeatingRow $row, Booking $booking) {}

    /** @return bool */
    public function isAvailable(SeatingRow $row)
    {
        return true;
    }

    /** @return int */
    public function rowCapacity(SeatingRow $row)
    {
        return 0;
    }
}
