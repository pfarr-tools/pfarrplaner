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

use App\Baptism;
use App\Funeral;
use App\ListedPerson;
use App\Ministry;
use App\Participant;
use App\Service;
use App\Team;
use App\User;
use App\Wedding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


/**
 * Class PersonReport
 * @package App\Reports
 */
class ReplaceableServicesReport extends AbstractPDFDocumentReport
{

    /**
     * @var string
     */
    public $title = 'Zu vertretende Dienste';
    /**
     * @var string
     */
    public $group = 'Listen';
    /**
     * @var string
     */
    public $description = 'Liste mit allen Gottesdiensten und Kasualien einer Person innerhalb eines bestimmten Zeitraums';

    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $users = User::visibleFor(Auth::user())->get();
        return Inertia::render('Report/ReplaceableServices/Setup', compact('users'));
    }

    /**
     * @param Request $request
     * @return mixed|string
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'person' => 'required|int|exists:users,id',
                'start' => 'required|date',
                'end' => 'required|date',
            ]
        );

        $user = User::find($data['person']);

        $services = Service::with(['location'])
            ->between(Carbon::parse($data['start']), Carbon::parse($data['end']))
            ->userParticipates($user)
            ->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->ordered()
            ->get();


        $weddings = Wedding::join('services', 'service_id', 'services.id')
            ->select(['weddings.*', 'services.date'])
            ->whereHas('service', function ($query) use ($user, $data) {
                $query->userParticipates($user)
                    ->between(Carbon::parse($data['start']), Carbon::parse($data['end']));
            })->orderBy('date')->get();

        $funerals = Funeral::join('services', 'service_id', 'services.id')
            ->select(['funerals.*', 'services.date'])
            ->whereHas('service', function ($query) use ($user, $data) {
                $query->userParticipates($user)
                    ->between(Carbon::parse($data['start']), Carbon::parse($data['end']));
            })->orderBy('date')->get();

        $baptisms = Baptism::join('services', 'service_id', 'services.id')
            ->select(['baptisms.*', 'services.date'])
            ->whereHas('service', function ($query) use ($user, $data) {
                $query->userParticipates($user)
                    ->between(Carbon::parse($data['start']), Carbon::parse($data['end']));
            })->orderBy('date')->get();

        return $this->sendToBrowser(
            date('Ymd') . ' Zu vertretende Dienste ' . $request->get('highlight') . '.pdf',
            [
                'start' => $request->get('start'),
                'end' => $request->get('end'),
                'user' => $user,
                'services' => $services,
                'baptisms' => $baptisms,
                'funerals' => $funerals,
                'weddings' => $weddings,
            ],
            ['format' => 'A4']
        );
    }

    public function wizard(Request $request)
    {
        $data = $request->validate(
            [
                'person' => 'required|int|exists:users,id',
                'start' => 'required|date',
                'end' => 'required|date',
            ]
        );

        $user = User::find($data['person']);
        $people =  ListedPerson::select(['id', 'name'])
            ->visibleFor(Auth::user())
            ->get();
        $teams = Team::with('users')->get();

        $services = Participant::with('service')
            ->join('services', 'service_id', 'services.id')
            ->select('services.date', 'service_user.*')
            ->whereHas('service', function ($query) use ($data) {
                $query->between(Carbon::parse($data['start']), Carbon::parse($data['end']));
                })
            ->where('user_id', $user->id)->orderBy('date')->get();

        $ministries = Ministry::POMA();
        return Inertia::render('Report/ReplaceableServices/Wizard', compact('user', 'people', 'services', 'ministries', 'teams'));
    }


}
