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

namespace App\Reports;

use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Models\Calendar\Day;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Inertia\Inertia;


/**
 * Class KonfiAppQRReport
 * @package App\Reports
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
     * @return bool
     */
    public function isActive(): bool
    {
        $isActive = false;
        foreach (Auth::user()->cities as $city) {
            $isActive = $isActive || (!empty($city->iban));
        }
        return $isActive;
    }


    /**
     * @return Application|Factory|View
     */
    public function setup()
    {
        $cities = Auth::user()->writableCities->reject(function ($item) {
            return empty($item->iban);
        });
        return Inertia::render('Report/GiroCodeQR/Setup', compact( 'cities'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse|mixed|string
     * @throws Exception
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'city' => 'required|int|exists:cities,id',
                'start' => 'required|date|date_format:Y-m-d',
                'end' => 'required|date|date_format:Y-m-d',
                'copies' => 'required|int',
            ]
        );

        $allServices = Service::where('city_id', $data['city'])
            ->between(Carbon::createFromFormat('Y-m-d', $data['start']),
                      Carbon::createFromFormat('Y-m-d', $data['end'])
            )->ordered()->get();


        // group by location
        $services = [];
        foreach ($allServices as $service) {
            $services[$service->locationText()][] = $service;
        }
        ksort($services);


        if (count($services) == '0') {
            return redirect()->route('reports.setup', 'konfiAppQR');
        }


        return $this->sendToFile(
            FileNameService::make(
                static::FILE_TITLE,
                'pdf',
                static::FILE_SIGNATURE,
                $service->date),
            [
                'services' => $services,
                'copies' => $data['copies'],
            ],
            ['format' => 'A4-L']
        );
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    public function single(Request $request)
    {
        $service = Service::findOrFail($request->get('service'));
        $services[$service->locationText()][] = $service;
        $types = KonfiAppIntegration::get($service->city)->listEventTypes();
        $copies = $request->get('copies', 1);
        return $this->sendToFile(
            $service->date->format('Ymd') . ' QR-Code.pdf',
            [
                'services' => $services,
                'types' => $types,
                'copies' => $copies,
            ],
            ['format' => 'A4-L']
        );
    }

}
