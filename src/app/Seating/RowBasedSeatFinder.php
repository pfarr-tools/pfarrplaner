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

use App\Models\Location;
use App\Models\Seating\Booking;
use App\Models\Seating\SeatingRow;
use App\Models\Seating\SeatingSection;
use App\Models\Service;
use Illuminate\Support\Collection;

class RowBasedSeatFinder extends AbstractSeatFinder
{
    /** @var string View used to render this finder. */
    public $viewName = 'rowBased';

    protected const ROW_TOO_SMALL = -1;

    protected const ROW_FULL = -2;

    /** @var Location */
    protected $location;

    /** @var Service Service whose seating is planned. */
    protected $service;

    /** @var int Maximum capacity. */
    protected $capacity = 0;

    /** @var array<string, array{row: SeatingRow, booking: Booking|null}> */
    protected $grid = [];

    /** @var array<string, array{row: SeatingRow, booking: Booking|null}> */
    protected $originalGrid = [];

    /** @var array<string, string> */
    protected $errors = [];

    /** @var bool Whether bookings have been loaded. */
    protected $loaded = false;

    /** @var int Remaining free seats. */
    protected $remain = 0;

    /** @var array<string, array{row: SeatingRow, booking: Booking|null}> */
    protected $reserved = [];

    /**
     * SeatFinder constructor.
     *
     * @param  bool  $preloadExistingBookings  Optional: Preload existing bookings?
     */
    public function __construct(Service $service, $preloadExistingBookings = true)
    {
        parent::__construct($service, $preloadExistingBookings);
        /** @var Location $location */
        $location = $service->location;
        $this->location = $location;
        $this->grid = $this->originalGrid = $this->buildGrid();
        if ($preloadExistingBookings) {
            $this->loadExistingBookings();
            $this->loaded = true;
        }
        $this->remain = $this->remainingCapacity();
    }

    /**
     * Initialize a grid array with all rows
     * The row title is used as the array key.
     * The array is sorted by row titles
     *
     * @return array<string, array{row: SeatingRow, booking: Booking|null}>
     */
    protected function buildGrid()
    {
        $grid = [];
        foreach ($this->getAllRows() as $row) {
            $grid[$row->title] = ['row' => $row, 'booking' => null];
        }
        ksort($grid);

        return $grid;
    }

    /**
     * @return void
     */
    protected function dumpGrid()
    {
        ksort($this->grid);
        echo '<table>';
        foreach ($this->grid as $key => $place) {
            /** @var int $seats */
            $seats = $place['row']->seats;
            /** @var string $name */
            $name = $place['booking'] !== null ? $place['booking']['name'] : '';
            /** @var string $number */
            $number = $place['booking'] !== null ? $place['booking']['number'] : '';
            echo '<tr><td>'.$key.'</td><td>['.$seats.']</td><td>'.$name.' ('.$number.')</td></tr>';
        }
        echo '</table><hr />';
    }

    /**
     * Check if a specified number of people can still be seated in the location
     *
     * @param  $number  Number of people
     * @param  string  $fixedSeat  Desired fixed seating position
     * @param  int  $number  Number of people.
     * @param  string  $overrideSeats  Override the default maximum number of seats for a fixed position
     * @param  string  $overrideSplit  Override the default splitting configuration for a fixed position
     * @param  Collection<int, Booking>  $bookings  Existing bookings used for the check
     * @return bool True if this booking can be added
     */
    public function find($number, $fixedSeat = '', $overrideSeats = '', $overrideSplit = '', $bookings = null)
    {
        if ($number > $this->remain) {
            return false;
        }

        $savedGrid = $this->grid;

        // reset whole Grid
        $this->grid = $this->originalGrid;

        // add a fake booking to the existing bookings and try again
        if ($bookings === null) {
            $bookings = $this->service->bookings;
        }
        $myBooking = new Booking(
            [
                'service_id' => $this->service->id,
                'name' => '___FINDER___',
                'number' => $number,
                'fixed_seat' => $fixedSeat,
                'override_seats' => $overrideSeats,
                'override_split' => $overrideSplit,
            ]
        );
        $bookings->push($myBooking);
        $success = $this->loadExistingBookings($bookings);

        // reset grid
        if ($success) {
            $this->grid = $savedGrid;
        }

        return $success;
    }

    /**
     * Check if seating is still possible after proposed changes to a booking
     *
     * @param  mixed  $data  Proposed booking data.
     * @return bool True if this booking can be changed
     */
    public function checkIfBookingCanBeChanged(Booking $booking, $data): bool
    {
        // test: remove this booking from the existing list and re-add it with changed data
        $bookings = $this->service->bookings->filter(function ($item) use ($booking) {
            return $item->id != $booking->id;
        });
        $remain = $this->remain;
        /** @var int $bookingNumber */
        $bookingNumber = $booking['number'];
        $this->remain += $bookingNumber;
        /** @var array{number: int, fixed_seat: string, override_seats: string, override_split: string} $data */
        $success = $this->find(
            $data['number'],
            $data['fixed_seat'],
            $data['override_seats'],
            $data['override_split'],
            $bookings
        );
        $this->remain = $remain;

        return $success;
    }

    /**
     * Find a place for all existing bookings
     *
     * @param  Collection<int, Booking>|array<int, Booking>|null  $bookings  Existing bookings.
     * @return bool Success
     */
    protected function loadExistingBookings($bookings = null)
    {
        if ($bookings === null) {
            $bookings = $this->service->bookings->sortByDesc('number');
        }

        // override default grid settings with manually booked bookings
        foreach ($bookings as $booking) {
            if (($booking->override_seats != '') && ($booking->fixed_seat != '')) {
                $rowNumber = str_pad((string) preg_replace('/[^0-9]/', '', $booking->fixed_seat), 2, '0', STR_PAD_LEFT);
                if (isset($this->grid[$rowNumber])) {
                    /** @var int<0, max> $seats */
                    $seats = (int) $booking->override_seats;
                    $this->grid[$rowNumber]['row']->seats = $seats;
                }
            }
        }

        // book manually placed bookings
        $autoBookings = [];
        foreach ($bookings as $booking) {
            if ($booking->fixed_seat == '') {
                $autoBookings[] = $booking;
            } else {
                $success = $this->manualPlacement($booking);
                if (! $success) {
                    return false;
                }
            }
        }

        // book automatically assigned seats
        foreach ($autoBookings as $booking) {
            $success = $this->getSeating($booking);
            if (! $success) {
                $this->setError(
                    'number',
                    $booking->number == 1 ? 'Es konnte kein Sitzplatz in diesem Gottesdienst reserviert werden.' : 'Es konnten keine '.$booking->number.' zusammenhängenden Sitzplätze in diesem Gottesdienst reserviert werden.'
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Place a booking manually
     *
     * @param  Booking  $booking  Booking with settings for manual placement
     * @return bool True if placement succeeded
     */
    protected function manualPlacement(Booking $booking)
    {
        if ($booking->fixed_seat == '') {
            return false;
        }
        // whole row?
        if (is_numeric($booking->fixed_seat)) {
            $rowKey = str_pad($booking->fixed_seat, 2, '0', STR_PAD_LEFT);
        } else {
            $rowNumber = str_pad((string) preg_replace('/[^0-9]/', '', $booking->fixed_seat), 2, '0', STR_PAD_LEFT);
            $placeLetter = preg_replace('/[0-9]/', '', $booking->fixed_seat);
            $rowKey = $rowNumber.$placeLetter;
            // row not yet split?
            if (isset($this->grid[$rowNumber])) {
                // override split?
                if ($booking->override_split != '') {
                    $this->grid[$rowNumber]['row']->split = $booking->override_split;
                }
                $this->splitRow($rowNumber);
            }
        }
        if (isset($this->grid[$rowKey])) {
            if ($this->grid[$rowKey]['booking'] == null) {
                $this->grid[$rowKey]['booking'] = $booking;

                return true;
            } else {
                /** @var Booking $existingBooking */
                $existingBooking = $this->grid[$rowKey]['booking'];
                /** @var string $existingName */
                $existingName = $existingBooking->name;
                /** @var string $existingFirstName */
                $existingFirstName = $existingBooking->first_name;
                $this->setError(
                    'fixed_seat',
                    'Der gewünschte Platz '.$rowKey.' ist bereits für '
                    .$existingName
                    .($existingFirstName ? ', '.$existingFirstName : '')
                    .' reserviert.'
                );

                return false;
            }
        } else {
            $this->setError('fixed_seat', 'Der gewünschte Platz '.$rowKey.' ist nicht verfügbar.');

            return false;
        }
    }

    /**
     * Optimize seating
     *
     * This will move people as far to the front as possible
     *
     * @return void
     */
    protected function optimize()
    {
        do {
            $result = $this->optimizeOne();
        } while ($result);
    }

    /**
     * Do one step of optimization
     *
     * @return bool True, if there was still something to be optimized
     */
    protected function optimizeOne()
    {
        ksort($this->grid);
        foreach ($this->grid as $key => $place) {
            if ($place['booking'] === null) {
                // try what we gain by optimizing whole row
                $ptsWholeRow = $this->optimizeWholeRow($key, $place, false);
                $ptsSplitRow = $this->optimizeSplitRow($key, $place, false);

                if (max($ptsSplitRow, $ptsWholeRow) == 0) {
                    return false;
                }

                if (! (($ptsSplitRow == $ptsWholeRow) && ($ptsWholeRow == -1))) {
                    if ($ptsWholeRow > $ptsSplitRow) {
                        $this->optimizeWholeRow($key, $place);
                    } else {
                        $this->optimizeSplitRow($key, $place);
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Optimize a row using the whole row as one
     *
     * @param  string  $key  Grid key
     * @param  array<string, mixed>  $place  Grid element
     * @param  bool  $do  Actually do this (false will only simulate optimization)
     * @return int Point gained by this move
     */
    protected function optimizeWholeRow($key, $place, $do = true)
    {
        $found = 0;
        $foundKey = null;
        /** @var SeatingRow $row */
        $row = $place['row'];
        /** @var int $rowSeats */
        $rowSeats = $row->seats;

        foreach ($this->grid as $searchKey => $searchPlace) {
            if (
                ($searchPlace['booking'] !== null)
                && ($searchPlace['booking']->fixed_seat == '')
                && ($searchKey > $key)
                && ($searchPlace['booking']->number > $found)
                && ($searchPlace['booking']->number <= $rowSeats)
            ) {
                $found = $searchPlace['booking']->number;
                $foundKey = $searchKey;
            }
        }
        if ($foundKey !== null) {
            if ($do) {
                $grid = $this->grid;
                $grid[$key] = ['row' => $grid[$key]['row'], 'booking' => $grid[$foundKey]['booking']];
                $grid[$foundKey] = ['row' => $grid[$foundKey]['row'], 'booking' => null];
                $this->grid = $grid;
            }

            return $found;
        }

        return -1;
    }

    /**
     * Optimize a row by splitting it into several places
     *
     * @param  string  $key  Grid key
     * @param  array<string, mixed>  $place  Grid element
     * @param  bool  $do  Actually do this (false will only simulate optimization)
     * @return int Point gained by this move, -1 if not possible
     */
    protected function optimizeSplitRow($key, $place, $do = true)
    {
        $pts = 0;
        $moved = [];
        $splits = explode(',', (string) ($this->grid[$key]['row']->split ?: $this->grid[$key]['row']->seats));
        if (count($splits) == 1) {
            return -1;
        }

        foreach ($splits as $splitKey => $split) {
            $found = 0;
            $foundKey = null;
            $foundBooking = null;
            foreach ($this->grid as $searchKey => $searchPlace) {
                if (($searchPlace['booking'] !== null)
                    && ($searchPlace['booking']->fixed_seat == '')
                    && ($searchKey > $key)
                    && ($searchPlace['booking']->number > $found)
                    && ($searchPlace['booking']->number <= $split)
                    && (! in_array($searchKey, $moved))
                ) {
                    $found = $searchPlace['booking']->number;
                    $foundKey = $searchKey;
                    $foundBooking = $searchPlace['booking'];
                }
            }
            if (($foundKey !== null) && ($foundBooking !== null)) {
                $moved[$splitKey] = $foundKey;
                $pts += $foundBooking->number;
            }
        }

        if ($do && count($moved)) {
            $this->splitRow($key);
            $grid = $this->grid;
            foreach ($moved as $target => $src) {
                $targetKey = $key.chr(65 + $target);
                $grid[$targetKey] = ['row' => $grid[$targetKey]['row'], 'booking' => $grid[$src]['booking']];
                $grid[$src] = ['row' => $grid[$src]['row'], 'booking' => null];
            }
            $this->grid = $grid;
        }

        return $pts;
    }

    /**
     * Export the seating list to an array
     *
     * @return array<string, mixed>|false
     */
    public function finalList()
    {
        $this->optimize();

        // add back all reserved rows
        $this->grid = $this->grid + $this->reserved;
        ksort($this->grid);

        // split all empty rows:
        foreach ($this->grid as $key => $place) {
            if ($place['booking'] === null) {
                $this->splitRow($key);
            }
        }
        ksort($this->grid);

        // compile lists
        $final = [];
        $empty = [];
        $number = 0;
        foreach ($this->grid as $key => $place) {
            if ($place['booking'] !== null) {
                /** @var string $bookingName */
                $bookingName = $place['booking']->name;
                /** @var string $bookingFirstName */
                $bookingFirstName = $place['booking']->first_name;
                $sortKey = $bookingName.($bookingFirstName ? ', '.$bookingFirstName : '');
                $number += $place['booking']->number;
                while (isset($final[$sortKey])) {
                    $sortKey .= '_';
                }
                $final[$sortKey] = $place;
            } else {
                $empty[$place['row']->seatingSection?->priority.$key] = $place;
            }
        }
        ksort($final);
        ksort($empty);

        return ['list' => $final, 'empty' => $empty, 'number' => $number];
    }

    /**
     * Try to place a booking on the grid with minimum loss of seats
     *
     * @param  Booking  $booking  Booking to place
     * @return false|mixed Place key, false if not placeable
     */
    protected function getSeating(Booking $booking)
    {
        $candidates = [];
        foreach ($this->grid as $key => $place) {
            $impact = $this->getImpact($key, $booking->number);
            if ($impact > 0) {
                $candidates[$impact][] = $key;
            }
        }
        if (! count($candidates)) {
            return false;
        }
        ksort($candidates);
        $targetImpact = array_key_first($candidates);
        $candidates = $candidates[$targetImpact];
        if (count($candidates)) {
            $target = $candidates[0];
            $this->capacity -= $targetImpact;
            $this->seat($target, $booking);

            return $target;
        }

        return false;
    }

    /**
     * Place a booking in a specific spot
     *
     * @param  string  $key  Grid key
     * @param  Booking  $booking  Booking
     * @return void
     */
    protected function seat($key, Booking $booking)
    {
        // first, test if we can split the row:
        $splits = explode(',', (string) ($this->grid[$key]['row']->split ?: $this->grid[$key]['row']->seats));

        // splitting doesn't work if there are no splits
        if (count($splits) == 1) {
            $this->grid[$key]['booking'] = $booking;

            return;
        }

        // splitting doesn't work if the number is too big
        if ($booking->number > max($splits)) {
            $this->grid[$key]['booking'] = $booking;

            return;
        }

        // seem's like splitting works:
        // 1. find the proper split
        $targetSplitSize = $this->grid[$key]['row']->seats;
        $targetSplit = null;
        foreach ($splits as $splitKey => $split) {
            if (($split >= $booking->number) && ($split <= $targetSplitSize)) {
                $targetSplitSize = $split;
                $targetSplit = $splitKey;
            }
        }

        // 2. split the row
        $this->splitRow($key);

        // 3. place booking
        $this->grid[$key.chr(65 + $targetSplit)]['booking'] = $booking;
    }

    /**
     * Split a row into its parts
     *
     * @param  string  $key  Grid key
     * @param  array<string, array{row: SeatingRow, booking: Booking|null}>|array<string, SeatingRow>|null  $grid  Optional: use alternative grid array instead of $this->grid
     * @param  bool  $isRowGrid  Optional: Grid only contains rows, no bookings
     * @return array<string, array{row: SeatingRow, booking: Booking|null}>|array<string, SeatingRow> Grid with split row
     */
    protected function splitRow($key, $grid = null, $isRowGrid = false)
    {

        $tmpGrid = $grid ?? $this->grid;

        if (! isset($tmpGrid[$key])) {
            return $tmpGrid;
        }

        // do not split an already split row (letter in $key)
        if (! is_numeric($key)) {
            return $tmpGrid;
        }

        /** @var SeatingRow $targetRow */
        $targetRow = $isRowGrid ? $tmpGrid[$key] : $tmpGrid[$key]['row'];
        $splits = explode(',', (string) ($targetRow->split ?: $targetRow->seats));

        // only split splittable rows
        if (count($splits) == 1) {
            return $tmpGrid;
        }

        // insert the new split rows
        foreach ($splits as $splitKey => $split) {
            $rowKey = $key.chr(65 + $splitKey);
            $row = clone $targetRow;
            $row->title = $rowKey;
            /** @var int<0, max> $rowSeats */
            $rowSeats = (int) $split;
            $row->seats = $rowSeats;
            $row->split = '';
            $tmpGrid[$rowKey] = $isRowGrid ? $row : ['row' => $row, 'booking' => null];
        }

        // delete old full row
        unset($tmpGrid[$key]);

        // restore target grid from $tmpGrid
        if ($grid === null) {
            /** @var array<string, array{row: SeatingRow, booking: Booking|null}> $tmpGrid */
            $this->grid = $tmpGrid;
        }

        /** @var array<string, array{row: SeatingRow, booking: Booking|null}> $tmpGrid */
        return $tmpGrid;
    }

    /**
     * Calculate the impact a placement would have
     *
     * Impact is defined as the number of free seats lost when a certain number of people
     * is placed in a specific row
     *
     * @param  string  $key  Grid key to place the people
     * @param  int  $number  Number of people to place
     * @return int Impact (negative, if placement is not possible)
     */
    protected function getImpact($key, $number)
    {
        // check if the place is already booked
        if ($this->grid[$key]['booking'] !== null) {
            return self::ROW_FULL;
        }

        $row = $this->grid[$key]['row'];
        /** @var int<0, max> $seats */
        $seats = $row->seats;

        // check if the place is big enough
        if ($number > $seats) {
            return self::ROW_TOO_SMALL;
        }

        // try splitting the row
        $splits = explode(',', (string) ($row->split ?: $row->seats));

        // if the row cannot be split, we need the whole row
        if (count($splits) == 1) {
            return $seats;
        }

        // if $number is greater than the maximum split number, we need the whole row
        if ($number > max($splits)) {
            return $seats;
        }

        // seems like a part of the row will be enough:
        // in this case, the impact is the row maximum minus the sum of the
        // other split seats
        $possible = $seats;
        $impact = 0;
        foreach ($splits as $split) {
            $splitSize = (int) $split;
            if (($splitSize >= $number) && ($splitSize < $possible)) {
                $possible = $splitSize;
            } else {
                $impact += $splitSize;
            }
        }

        return $seats - $impact + $possible;
    }

    /**
     * Get the SeatingSections available for seating
     *
     * @return mixed
     */
    protected function getAvailableSections()
    {
        $sections = $this->location->seatingSections->sortBy('priority');
        // take care of excluded sections
        $excludeSections = $this->service->exclude_sections ?? '';
        if (trim($excludeSections) != '') {
            $excludedSections = [];
            foreach (explode(',', $excludeSections) as $e) {
                $excludedSections[] = strtolower(trim($e));
            }
            $forgetSections = [];
            /** @var SeatingSection $section */
            foreach ($sections as $key => $section) {
                if (in_array(strtolower($section->title), $excludedSections)) {
                    $forgetSections[] = $key;
                }
            }
            foreach ($forgetSections as $section) {
                $sections->forget($section);
            }
        }

        return $sections;
    }

    /**
     * Get all rows available for seating
     *
     * @return array<string, SeatingRow>
     */
    public function getAllRows()
    {
        $rows = [];
        $sections = $this->getAvailableSections();
        if ($sections instanceof Collection) {
            foreach ($sections as $section) {
                if (! $section instanceof SeatingSection) {
                    continue;
                }
                foreach ($section->seatingRows as $row) {
                    if (! $row instanceof SeatingRow) {
                        continue;
                    }
                    $title = $row->title;
                    if (! is_string($title)) {
                        continue;
                    }
                    $rows[$title] = $row;
                }
            }
        }

        // see if any rows should be kept back
        if ($this->service->reserved_places) {
            foreach (explode(',', $this->service->reserved_places) as $reservedPlace) {
                if (! is_numeric($reservedPlace)) {
                    $rowNumber = str_pad((string) preg_replace('/[^0-9]/', '', $reservedPlace), 2, '0', STR_PAD_LEFT);
                    $placeLetter = preg_replace('/[0-9]/', '', $reservedPlace);
                    /** @var array<string, SeatingRow> $rows */
                    $rows = $this->splitRow($rowNumber, $rows, true);
                    $reservedRow = $rows[$rowNumber.$placeLetter];
                    $this->reserved[$rowNumber.$placeLetter] = ['row' => $reservedRow, 'booking' => null];
                    unset($rows[$rowNumber.$placeLetter]);
                } else {
                    $reservedPlace = str_pad($reservedPlace, 2, '0', STR_PAD_LEFT);
                    /** @var SeatingRow $reservedRow */
                    $reservedRow = $rows[$reservedPlace];
                    $this->reserved[$reservedPlace] = ['row' => $reservedRow, 'booking' => null];
                    unset($rows[$reservedPlace]);
                }

            }
        }

        // unset pre-reserved rows:
        $excludePlaces = $this->service->exclude_places ?? '';
        if (trim($excludePlaces) != '') {
            $preReservedPlaces = explode(',', $excludePlaces);
            foreach ($preReservedPlaces as $preReservedPlace) {
                $preReservedPlace = trim($preReservedPlace);
                if (! is_numeric($preReservedPlace)) {
                    $rowNumber = str_pad((string) preg_replace('/[^0-9]/', '', $preReservedPlace), 2, '0', STR_PAD_LEFT);
                    $placeLetter = preg_replace('/[0-9]/', '', $preReservedPlace);
                    /** @var array<string, SeatingRow> $rows */
                    $rows = $this->splitRow($rowNumber, $rows, true);
                    unset($rows[$rowNumber.$placeLetter]);
                } else {
                    $preReservedPlace = str_pad($preReservedPlace, 2, '0', STR_PAD_LEFT);
                    unset($rows[$preReservedPlace]);
                }
            }
        }

        return $rows;
    }

    /**
     * Get the maximum seating capacity of the venue
     *
     * @return int Number of seats
     */
    public function maximumCapacity()
    {
        $capacity = 0;
        foreach ($this->getAllRows() as $seatingRow) {
            $capacity += $seatingRow->seats;
        }
        if ($this->service->registration_max) {
            $capacity = min($capacity, $this->service->registration_max);
        }

        return $capacity;
    }

    /**
     * @param  Collection<int, Booking>|null  $bookings  Bookings to use instead of the stored ones.
     * @return int Remaining capacity.
     */
    public function remainingCapacity($bookings = null)
    {
        $saveGrid = $this->grid;
        $this->optimize();
        $capacity = 0;
        $seated = 0;
        foreach ($this->grid as $key => $place) {
            if ($place['booking'] === null) {
                $capacity += $place['row']->seats;
            } else {
                $seated += $place['booking']->number;
            }
        }
        if ($this->service->registration_max) {
            $capacity = min($capacity, $this->service->registration_max - $seated);
        }

        $this->grid = $saveGrid;

        return $capacity;
    }

    /**
     * Get the full row record, even for a partial row
     *
     * @param  string|int  $key  Grid key.
     * @return SeatingRow|null
     */
    public function getFullRow($key)
    {
        if (is_numeric($key)) {
            return $this->originalGrid[$key]['row'] ?? $this->reserved[$key]['row'] ?? null;
        }

        return $this->originalGrid[substr($key, 0, -1)]['row'] ?? $this->reserved[substr($key, 0, -1)]['row'] ?? null;
    }

    /** @return array<string, string> */
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
     * Get a seating table arranged by booking codes
     *
     * @return array<int|string, mixed>
     */
    public function getSeatingTable(): array
    {
        $savedGrid = $this->grid;
        $this->optimize();

        $list = [];
        $free = [];
        $grid = [];
        $count = 0;

        foreach ($this->grid as $placeKey => $place) {
            if ($place['booking'] !== null) {
                $list[$place['booking']->code] = $placeKey;
                $count += $place['booking']->number;
            } else {
                $free[$placeKey] = $placeKey;
            }
        }

        foreach ($this->originalGrid as $placeKey => $place) {
            $grid[$placeKey] = $place['row'];
            $split = $place['row']['split'];
            if (is_string($split) && $split !== '') {
                foreach (explode(',', $split) as $splitKey => $splitSeats) {
                    $row = clone $place['row'];
                    /** @var int<0, max> $rowSeats */
                    $rowSeats = (int) $splitSeats;
                    $row->seats = $rowSeats;
                    $grid[$placeKey.chr(65 + $splitKey)] = $row;
                }
            }
        }

        $load = $count / $this->maximumCapacity() * 100;

        $this->grid = $savedGrid;

        return compact('list', 'free', 'grid', 'count', 'load');
    }
}
