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

namespace App\Reports;

use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class KonfiAppQRReport
 */
class GiroCodeQRReport extends AbstractPDFDocumentReport
{
    public const FILE_SIGNATURE = '50';

    public const FILE_TITLE = 'GiroCodes für Spenden';

    /**
     * @var string
     */
    public $title = 'GiroCodes für digitales Gottesdienstopfer';

    /**
     * @var string
     */
    public $group = 'Opfer';

    /**
     * @var string
     */
    public $description = 'Erstellt GiroCodes, mit denen digital für den Opferzweck gespendet werden kann.';

    protected $inertia = true;

    /**
     * Only active if at least one of the user's cities has an IBAN set
     */
    public function isActive(): bool
    {
        $isActive = false;
        foreach ($this->authenticatedUser()->cities as $city) {
            $isActive = $isActive || (! empty($city->iban));
        }

        return $isActive;
    }

    /**
     * @return Application|Factory|View|Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->writableCities->reject(function ($item) {
            return empty($item->iban);
        });

        return Inertia::render('Report/GiroCodeQR/Setup', compact('cities'));
    }

    /**
     * @return RedirectResponse|mixed|string
     *
     * @throws Exception
     */
    public function render(Request $request)
    {
        /** @var array{city: int, start: string, end: string, copies: int} $data */
        $data = $request->validate(
            [
                'city' => 'required|int|exists:cities,id',
                'start' => 'required|date|date_format:Y-m-d',
                'end' => 'required|date|date_format:Y-m-d',
                'copies' => 'required|int',
            ]
        );

        $allServices = Service::where('city_id', $data['city'])
            ->between(Carbon::parse($data['start']),
                Carbon::parse($data['end'])
            )->ordered()->get();

        // group by location
        $services = [];
        $service = null;
        foreach ($allServices as $service) {
            $services[$service->locationText()][] = $service;
        }
        ksort($services);

        if (count($services) === 0) {
            return redirect()->route('reports.setup', 'konfiAppQR');
        }
        $lastService = $allServices->last();
        if (! $lastService instanceof Service) {
            return redirect()->route('reports.setup', 'konfiAppQR');
        }

        /** @var string $title */
        $title = static::FILE_TITLE;

        return $this->sendToFile(
            FileNameService::make(
                $title,
                'pdf',
                static::FILE_SIGNATURE,
                $lastService->trueDate()),
            [
                'services' => $services,
                'copies' => $data['copies'],
            ],
            ['format' => 'A4-L']
        );
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function single(Request $request)
    {
        /** @var int|string|null $serviceId */
        $serviceId = $request->get('service');
        $service = Service::findOrFail((int) $serviceId);
        $services[$service->locationText()][] = $service;
        $city = $service->city;
        if ($city === null) {
            abort(404);
        }
        $types = KonfiAppIntegration::get($city)->listEventTypes();
        $copies = $request->get('copies', 1);

        return $this->sendToFile(
            $service->trueDate()->format('Ymd').' QR-Code.pdf',
            [
                'services' => $services,
                'types' => $types,
                'copies' => $copies,
            ],
            ['format' => 'A4-L']
        );
    }
}
