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

namespace App\Http\Controllers;


use App\Models\Seating\Booking;
use App\Models\Service;
use App\Rules\SeatableFixed;
use App\Services\FileNameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Documents\PDF;

class BookingController extends AbstractCRUDController
{
    protected string $modelClass = Booking::class;

    /**
     * BookingController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        /** @var Booking|null $model */
        $service = $model?->service;
        if (!$service && $request->has('service_id')) {
            $service = Service::findOrFail($request->integer('service_id'));
        }
        if ($service) {
            $service->setAppends([]);
        }

        return ['service' => $service];
    }

    public function index(Request $request, ?Service $service = null)
    {
        abort_if(!$service, 404);
        Gate::authorize('update', $service);
        return response()->json($service->bookings()->get());
    }

    /**
     * @param Request $request
     * @param Service $service
     * @return \Inertia\Response
     */
    public function findSeat(Request $request, Service $service)
    {
        Gate::authorize('create', [Booking::class, $service]);
        $service->setAppends([]);
        $booking = Booking::getEmptyModel();
        $booking->service_id = $service->id;
        return Inertia::render('Service/Registrations/BookingEditor', compact('service', 'booking'));
    }

    /**
     * Finalize seating and export the seating list to PDF
     * @param Service $service
     * @return mixed
     */
    public function finalize(Service $service)
    {
        $result = $service->getSeatFinder()->finalList();
        $viewName = $service->getSeatFinder()->viewName;

        $participants = [];
        foreach ($service->participants as $participant) {
            $participants[] = $participant->first_name ? $participant->last_name . ', ' . $participant->first_name : $participant->fullName(
            );
        }
        $participants = array_unique($participants);
        sort($participants);


        return PDF::fromView('bookings.pdf.list.' . $viewName,
                                            array_merge($result, compact('service', 'participants')))
            ->download(FileNameService::make('Sitzplan', 'pdf', '50.0', $service->date, true));
    }

    /**
     * Pin a booking to a fixed seatde
     * @param Request $request
     * @param Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    protected function pin(Request $request, Booking $booking)
    {
        Gate::authorize('update', $booking);
        $data = $request->validate(['fixed_seat' => ['required', 'string', new SeatableFixed('booking_id')]]);
        $booking->update($data);
        return response()->json($booking);
    }

    public function destroy(Request $request, $modelId)
    {
        $model = $this->getSingleModel($request, $modelId);
        $deleter = $model->getContractedAction('delete');
        $deleter->delete($request->user(), $model);
        return response()->json();
    }

}
