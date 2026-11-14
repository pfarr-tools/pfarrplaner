<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $
 */

namespace App\Reports;

use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Render an offering certificate for one recorded service.
 */
class OfferingCertificateReport extends AbstractPDFDocumentReport
{
    public const FILE_SIGNATURE = '77.1';

    public const FILE_TITLE = 'Opferurkunde';

    /** @var string Report title. */
    public $title = 'Opferurkunde';

    /** @var string Report group. */
    public $group = 'Opfer';

    /** @var string Report description. */
    public $description = 'Opferurkunde für einen erfassten Gottesdienst';

    protected $inertia = true;

    /**
     * Show services available for the authenticated user's cities.
     *
     * @return Response Report setup page.
     */
    public function setup(): Response
    {
        $services = $this->accessibleServices()
            ->whereNotNull('offering_amount')
            ->where('offering_amount', '!=', '')
            ->orderByDesc('date')
            ->limit(100)
            ->get();

        return Inertia::render('Report/OfferingCertificate/Setup', compact('services'));
    }

    /**
     * Render one certificate as a PDF download.
     *
     * @param  Request  $request  Report request.
     * @return mixed PDF download response.
     */
    public function render(Request $request)
    {
        $data = $request->validate(['service' => ['required', 'integer']]);
        $service = $this->accessibleServices()->whereKey($data['service'])->firstOrFail();
        /** @var City $city */
        $city = $service->city;

        return $this->sendToFile(
            FileNameService::make(
                self::FILE_TITLE.' '.$city->name,
                'pdf',
                self::FILE_SIGNATURE,
                $service->date,
                false,
                Auth::user(),
            ),
            ['service' => $service],
            ['format' => 'A4']
        );
    }

    /**
     * Return services in cities available to the authenticated user.
     *
     * @return Builder<Service> Accessible service query.
     */
    private function accessibleServices()
    {
        $query = Service::query();
        if (! Auth::user()->isAdmin) {
            $query->whereIn('city_id', Auth::user()->cities->pluck('id'));
        }

        return $query;
    }
}
