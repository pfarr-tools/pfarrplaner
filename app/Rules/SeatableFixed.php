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
 */

namespace App\Rules;

use App\Models\Seating\Booking;
use App\Models\Service;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class SeatableFixed implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    /**
     * @param string|null $bookingIdKey Form field name that holds the existing booking ID (null for new bookings)
     */
    public function __construct(protected ?string $bookingIdKey = null) {}

    /**
     * @param array $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = $this->data;
        $data['number'] = 1;

        /** @var Service $service */
        $service = Service::findOrFail($data['service_id']);
        $seatFinder = $service->getSeatFinder();

        if ($this->bookingIdKey && isset($data[$this->bookingIdKey])) {
            $booking = Booking::find($data[$this->bookingIdKey]);
            $success = $seatFinder->checkIfBookingCanBeChanged($booking, $data);
        } else {
            $success = $seatFinder->find(
                $data['number'],
                $data['fixed_seat'] ?? '',
                $data['override_seats'],
                $data['override_split']
            );
        }

        if (!$success) {
            $fail('validation.seatable_fixed')->translate();
        }
    }
}
