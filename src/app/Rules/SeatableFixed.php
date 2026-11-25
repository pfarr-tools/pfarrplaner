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
 */

namespace App\Rules;

use App\Models\Seating\Booking;
use App\Models\Service;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class SeatableFixed implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * @param  string|null  $bookingIdKey  Form field name that holds the existing booking ID (null for new bookings)
     */
    public function __construct(protected ?string $bookingIdKey = null) {}

    /**
     * @param  array<string, mixed>  $data  Validation data passed by the validator.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  string  $attribute  Attribute name.\n     * @param  mixed  $value  Value to validate.\n     * @param  Closure  $fail  Failure callback.\n     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = $this->data;
        $data['number'] = 1;

        /** @var Service $service */
        $service = Service::findOrFail($data['service_id']);
        $seatFinder = $service->getSeatFinder();

        if ($this->bookingIdKey && isset($data[$this->bookingIdKey])) {
            /** @var int|string $bookingId ID der zu ändernden Buchung. */
            $bookingId = $data[$this->bookingIdKey];
            $booking = Booking::find((int) $bookingId);
            if ($booking === null) {
                $fail('validation.seatable')->translate();

                return;
            }
            $success = $seatFinder->checkIfBookingCanBeChanged($booking, $data);
        } else {
            /** @var string $fixedSeat Gewünschter fester Platz. */
            $fixedSeat = $data['fixed_seat'] ?? '';
            /** @var string $overrideSeats Überschreibung der Sitzplatzanzahl. */
            $overrideSeats = $data['override_seats'];
            /** @var string $overrideSplit Überschreibung der Aufteilung. */
            $overrideSplit = $data['override_split'];
            $success = $seatFinder->find(
                $data['number'],
                $fixedSeat,
                $overrideSeats,
                $overrideSplit
            );
        }

        if (! $success) {
            $fail('validation.seatable_fixed')->translate();
        }
    }
}
