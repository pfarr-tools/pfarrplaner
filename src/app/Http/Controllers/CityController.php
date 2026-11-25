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

namespace App\Http\Controllers;

use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Mail\OfferingCertificateMail;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\QRService;
use App\Traits\HandlesAttachedImageTrait;
use App\Traits\HandlesAttachmentsTrait;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for handling city-related functionality.
 *
 * Handles CRUD operations and additional functionality such as
 * retrieving models, managing attachments, and generating QR codes
 * for city-related events or services.
 *
 * @extends AbstractCRUDController<City>
 */
class CityController extends AbstractCRUDController
{
    use HandlesAttachedImageTrait;
    use HandlesAttachmentsTrait;

    protected string $modelClass = City::class;

    /** @var class-string<City> Model class used by the shared attachment traits. */
    protected string $model = City::class;

    /**
     * Class constructor.
     *
     * Applies the 'auth' middleware to all routes, except for the 'qr' route.
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['qr', 'publicOfferings', 'savePublicOffering']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelsForIndex(): Collection
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        if ($user->isAdmin) {
            return ($this->modelClass)::all();
        } else {
            return $user->cities()->get();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getSingleModel(Request $request, $modelId = null, $relations = []): AbstractModel
    {
        return parent::getSingleModel($request, $modelId, $relations)->load(['adChannels', 'locations', 'parishes']);
    }

    /**
     * Build editor resources including city-specific permissions and choices.
     *
     * @param  Request  $request  Current editor request.
     * @param  City|null  $model  City being edited, if any.
     * @return array<string, mixed> Editor resource payload.
     */
    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        $data = parent::getResourcesForEditor($request, $model);
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $data['canDelete'] = $user->can('delete', $model);
        /** @var \Illuminate\Database\Eloquent\Collection<int, City> $children */
        $children = $model ? $model->children : collect();
        $data['possibleChildren'] = City::where('is_org', '!=', 1)
            ->whereNull('parent_id')
            ->get()
            ->merge($children)
            ->unique()
            ->sortBy('name');
        $data['streams'] = [];
        $data['activeTab'] = 'home';
        if ($model) {
            $data['offeringsUrl'] = URL::signedRoute('offerings.public', ['city' => $model]);
            $data['offeringsQrUrl'] = route('offerings.qr', ['city' => $model]);
        }

        return $data;
    }

    /**
     * Show QR codes
     *
     * @param  string  $city
     * @return Response
     */
    public function qr(Request $request, $city)
    {
        $city = City::where('name', 'like', '%'.Str::replace(['-', 'ae', 'oe', 'ue'], [' ', 'ä', 'ö', 'ü'], $city).'%')->first();
        abort_unless($city !== null, 404);

        $services = Service::where('city_id', $city->id)->whereDate('date', Carbon::now()->setTime(0, 0, 0))
            ->whereNotNull('konfiapp_event_qr')->get();
        $types = KonfiAppIntegration::isActive($city)
            ? KonfiAppIntegration::get($city)->listEventTypes()
            : collect();

        $servicesWithoutQR = Service::where('city_id', $city->id)->whereDate('date', Carbon::now()->setTime(0, 0, 0))
            ->whereNull('konfiapp_event_qr')->get();

        return Inertia::render('Public/City/QR', compact('services', 'city', 'types', 'servicesWithoutQR'));
    }

    /**
     * Show the signed public offerings page for a city.
     *
     * @param  Request  $request  Current signed request.
     * @param  City  $city  Selected city.
     * @return Response Public offerings page.
     */
    public function publicOfferings(Request $request, City $city): Response
    {
        $today = Carbon::now('Europe/Berlin')->startOfDay();
        $services = $this->offeringServicesForDate($city, $today);
        $fallback = $services->isEmpty();
        /** @var Collection<int, Service> $nearbyServices */
        $nearbyServices = collect();

        if ($fallback) {
            $services = $this->offeringServicesNearDate($city, $today);
        } else {
            $nearbyServices = $this->offeringServicesNearDate($city, $today)
                ->reject(fn (Service $service): bool => $service->date
                    ?->copy()
                    ->setTimezone('Europe/Berlin')
                    ->toDateString() === $today->toDateString())
                ->values();
        }

        return Inertia::render('Public/City/Offerings', [
            'city' => $city->only(['id', 'name', 'official_name']),
            'services' => $services->map(fn (Service $service): array => $this->publicOfferingService($service))->values(),
            'nearbyServices' => $nearbyServices->map(fn (Service $service): array => $this->publicOfferingService($service))->values(),
            'fallback' => $fallback,
            'denominations' => $this->offeringDenominations(),
        ]);
    }

    /**
     * Save an offering through the signed public city link.
     *
     * @param  Request  $request  Current signed request.
     * @param  City  $city  Selected city.
     * @return RedirectResponse Redirect back to the signed page.
     */
    public function savePublicOffering(Request $request, City $city)
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'offering_amount' => ['nullable', 'string', 'max:32'],
            'offering_denominations' => ['nullable', 'array'],
            'offerings_counter1' => ['nullable', 'string', 'max:255'],
            'offerings_counter2' => ['nullable', 'string', 'max:255'],
        ]);

        $today = Carbon::now('Europe/Berlin')->startOfDay();
        $service = Service::query()
            ->where('city_id', $city->id)
            ->whereKey($data['service_id'])
            ->whereBetween('date', [$today->copy()->subDays(7)->utc(), $today->copy()->addDays(7)->endOfDay()->utc()])
            ->firstOrFail();

        abort_if(filled($service->offering_amount), 409, 'Die Opferangaben wurden bereits gespeichert.');

        /** @var array<int|string, mixed> $denominationsInput */
        $denominationsInput = $data['offering_denominations'] ?? [];
        $denominations = $this->validatedOfferingDenominations($denominationsInput);
        $amount = $this->offeringAmountFromDenominations($denominations);
        if ($amount === null) {
            /** @var string|null $amountInput */
            $amountInput = $data['offering_amount'] ?? null;
            $amount = $this->normalizeOfferingAmount($amountInput);
        }

        abort_unless($amount !== null, 422, 'Bitte eine gültige Opfersumme eingeben.');

        $service->update([
            'offering_amount' => $amount,
            'offering_denominations' => $denominations ?: null,
            'offerings_counter1' => $data['offerings_counter1'] ?? null,
            'offerings_counter2' => $data['offerings_counter2'] ?? null,
        ]);

        if ($city->offering_certificate_email) {
            Mail::to($city->offering_certificate_email)->queue(new OfferingCertificateMail($service->refresh()));
        }

        return back()->with('success', 'Die Opferangaben wurden gespeichert.');
    }

    /**
     * Render the QR code for the signed public offerings URL.
     *
     * @param  City  $city  Selected city.
     * @return \Illuminate\Http\Response QR code image response.
     */
    public function offeringsQr(City $city)
    {
        $file = QRService::generate(URL::signedRoute('offerings.public', ['city' => $city]));
        $png = file_get_contents($file);
        unlink($file);
        abort_if($png === false, 500, 'Der QR-Code konnte nicht gelesen werden.');

        return response($png)->header('Content-Type', 'image/png');
    }

    /**
     * Return current-day services for the public offerings page.
     *
     * @param  City  $city  Selected city.
     * @param  Carbon  $date  Local Berlin date.
     * @return Collection<int, Service> Matching services.
     */
    private function offeringServicesForDate(City $city, Carbon $date): Collection
    {
        return Service::query()
            ->where('city_id', $city->id)
            ->where(function ($query): void {
                $query->whereNull('offering_amount')->orWhere('offering_amount', '');
            })
            ->whereBetween('date', [$date->copy()->utc(), $date->copy()->endOfDay()->utc()])
            ->orderBy('date')
            ->get();
    }

    /**
     * Return services in the seven-day temporal surroundings of a date.
     *
     * @param  City  $city  Selected city.
     * @param  Carbon  $date  Local Berlin date.
     * @return Collection<int, Service> Nearby services.
     */
    private function offeringServicesNearDate(City $city, Carbon $date): Collection
    {
        return Service::query()
            ->where('city_id', $city->id)
            ->where(function ($query): void {
                $query->whereNull('offering_amount')->orWhere('offering_amount', '');
            })
            ->whereBetween('date', [$date->copy()->subDays(7)->utc(), $date->copy()->addDays(7)->endOfDay()->utc()])
            ->orderBy('date')
            ->get();
    }

    /**
     * Map a service to the public offerings payload.
     *
     * @param  Service  $service  Service to expose.
     * @return array<string, mixed> Public service data.
     */
    private function publicOfferingService(Service $service): array
    {
        return [
            'id' => $service->id,
            'date' => $service->date?->setTimezone('Europe/Berlin')->format('Y-m-d'),
            'time' => $service->time,
            'title' => $service->titleText(),
            'location' => $service->locationText(),
            'offering_goal' => $service->offeringGoal(),
            'offering_amount' => $service->offering_amount,
            'offering_denominations' => $service->offering_denominations ?? [],
            'offerings_counter1' => $service->offerings_counter1,
            'offerings_counter2' => $service->offerings_counter2,
        ];
    }

    /**
     * Return the supported Euro denominations in cents.
     *
     * @return array<int, string> Denomination labels keyed by cents.
     */
    private function offeringDenominations(): array
    {
        return [
            10000 => '100 €-Scheine',
            5000 => '50 €-Scheine',
            2000 => '20 €-Scheine',
            1000 => '10 €-Scheine',
            500 => '5 €-Scheine',
            200 => '2 €-Münzen',
            100 => '1 €-Münzen',
            50 => '50 Ct.-Münzen',
            20 => '20 Ct.-Münzen',
            10 => '10 Ct.-Münzen',
            5 => '5 Ct.-Münzen',
            2 => '2 Ct.-Münzen',
            1 => '1 Ct.-Münzen',
        ];
    }

    /**
     * Validate and normalize denomination counts.
     *
     * @param  array<string|int, mixed>  $input  Submitted counts.
     * @return array<int|string, int> Normalized positive counts.
     */
    private function validatedOfferingDenominations(array $input): array
    {
        $allowed = $this->offeringDenominations();
        /** @var array<int|string, int> $result */
        $result = [];
        foreach ($input as $cents => $count) {
            /** @var int|string $count */
            abort_unless(array_key_exists((int) $cents, $allowed), 422, 'Ungültige Stückelung.');
            abort_unless(filter_var($count, FILTER_VALIDATE_INT) !== false && (int) $count >= 0, 422, 'Ungültige Stückzahl.');
            if ((int) $count > 0) {
                $result[(string) (int) $cents] = (int) $count;
            }
        }

        return $result;
    }

    /**
     * Calculate a formatted Euro amount from denomination counts.
     *
     * @param  array<int|string, int>  $denominations  Counts keyed by cents.
     * @return string|null Formatted amount or null when no count was entered.
     */
    private function offeringAmountFromDenominations(array $denominations): ?string
    {
        if ($denominations === []) {
            return null;
        }

        $cents = 0;
        foreach ($denominations as $denomination => $count) {
            $cents += (int) $denomination * $count;
        }

        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Normalize a German or plain decimal offering amount.
     *
     * @param  string|null  $amount  Submitted amount.
     * @return string|null Formatted amount or null for invalid input.
     */
    private function normalizeOfferingAmount(?string $amount): ?string
    {
        if ($amount === null || trim($amount) === '' || ! preg_match('/^\d{1,3}(?:[. ]?\d{3})*(?:,\d{1,2}|\.\d{1,2})?$/', trim($amount))) {
            return null;
        }

        $normalized = str_contains($amount, ',')
            ? str_replace(',', '.', str_replace(['.', ' '], '', trim($amount)))
            : str_replace(' ', '', trim($amount));

        return number_format((float) $normalized, 2, '.', '');
    }
}
