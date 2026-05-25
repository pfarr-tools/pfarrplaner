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

use App\Attachments\AttachmentFactory;
use App\Liturgy\PronounSets\PronounSets;
use App\Models\Attachment;
use App\Models\Rites\Baptism;
use App\Models\Service;
use App\Traits\HandlesAttachmentsTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

/**
 * Class BaptismController
 * @package App\Http\Controllers
 */
class BaptismController extends AbstractCRUDController
{

    use HandlesAttachmentsTrait;

    protected string $modelClass = Baptism::class;

    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @param int $serviceId Service Id
     * @return Response
     */
    public function create(Request $request)
    {
        if ($request->filled('service_id')) {
            Gate::authorize('update', Service::findOrFail($request->get('service_id')));
        } else {
            Gate::authorize('create', Baptism::class);
        }
        $creator = app(Baptism::getContractName('create'));
        $baptism = $creator->create($request->user(), $request->all());
        return redirect()->route('baptisms.edit', $baptism->id);
    }

    /**
     * @param Service $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function add(Service $service)
    {
        Gate::authorize('update', $service);
        return redirect()->route('baptisms.create', ['service_id' => $service->id]);
    }

    /**
     * @param int $baptismFlag
     * @return mixed
     */
    protected function getBaptismalServices($baptismFlag = 1)
    {
        return Service::setEagerLoads([])
            ->with(['baptisms', 'participants'])
            ->where('baptism', '=', $baptismFlag)
            ->whereDoesntHave('funerals')
            ->inCities(Auth::user()->cities)
            ->startingFrom(Carbon::now())
            ->ordered()
            ->get();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Baptism $baptism
     * @return Response
     */
    protected function getResourcesForEditor(Request $request, $baptism = null): array
    {
        $baptismalServicesQuery = Service::setEagerLoads([])
            ->with(['baptisms', 'participants'])
            ->where('baptism', '=', 1)
            ->inCities(Auth::user()->cities)
            ->startingFrom(Carbon::now())
            ->ordered();
        if ($baptism?->service_id) {
            $baptismalServicesQuery->orWhere('services.id', (int)$baptism->service_id);
        }
        $baptismalServices = $baptismalServicesQuery->get();

        $cities = Auth::user()->writableCities;
        $pronounSets = PronounSets::toArray();

        $otherServices = $this->getBaptismalServices(0);

        $attachments = AttachmentFactory::getList('baptism');

        $services = [];
        foreach (
            [
                'Taufgottesdienste' => $baptismalServices,
                'Andere Gottesdienste' => $otherServices
            ] as $category => $theseServices
        ) {
            foreach ($theseServices as $service) {
                $services[] = [
                    'id' => $service->id,
                    'name' => $service->dateTime->setTimeZone('Europe/Berlin')->format(
                            'd.m.Y, H:i'
                        ) . ' Uhr (' . $service->locationText() . '), '
                        . ($service->titleText() == 'GD' ? '' : $service->titleText(false) . ', ')
                        . $service->participantsText('P')
                        . (count($service->baptisms) ? ' [bisherige Taufen: ' . count($service->baptisms) . ']' : ''),
                    'category' => $category,
                ];
            }
        }

        return compact('services', 'cities', 'pronounSets', 'attachments');
    }

    /**
     * @param Baptism $baptism
     * @return Application|ResponseFactory|Response
     */
    public
    function appointmentIcal(
        Baptism $baptism
    ) {
        Gate::authorize('update', $baptism);
        $service = Service::find($baptism->service_id);
        $raw = View::make('baptisms.appointment.ical', compact('baptism', 'service'));
        $raw = str_replace(
            "\r\n\r\n",
            "\r\n",
            str_replace('@@@@', "\r\n", str_replace("\n", "\r\n", str_replace("\r\n", '@@@@', $raw)))
        );
        return response($raw, 200)
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Expires', '0')
            ->header('Content-Type', 'text/calendar')
            ->header('Content-Disposition', 'inline; filename=Taufgespraech-' . $baptism->id . '.ics');
    }

    /**
     * @param Baptism $baptism
     * @return false|string
     */
    public
    function done(
        Baptism $baptism
    ) {
        Gate::authorize('update', $baptism);
        $baptism->done = true;
        $baptism->save();
        return json_encode(true);
    }


    /**
     * @param Request $request
     * @param Baptism $baptism
     * @return \Illuminate\Http\JsonResponse
     */
    public function attach(Request $request, Baptism $baptism)
    {
        Gate::authorize('update', $baptism);
        $this->handleAttachments($request, $baptism);
        $baptism->refresh();
        return response()->json($baptism->attachments);
    }

    /**
     * @param Request $request
     * @param Baptism $baptism
     * @param Attachment $attachment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public
    function detach(
        Request $request,
        Baptism $baptism,
        Attachment $attachment
    ) {
        Gate::authorize('update', $baptism);
        $attachment = $baptism->attachments()->findOrFail($attachment->id);
        $file = $attachment->file;
        $baptism->attachments()->where('id', $attachment->id)->delete();
        Storage::delete($file);
        $attachment->delete();
        $baptism->refresh();
        return response()->json($baptism->attachments);
    }
}
