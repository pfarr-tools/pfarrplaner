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

use App\Models\People\ListedPerson;
use App\Models\People\Participant;
use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\MinistryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class PersonReport
 */
class ReplaceableServicesReport extends AbstractPDFDocumentReport
{
    public const FILE_TITLE = 'Gottesdienstvertretung';

    public const FILE_SIGNATURE = '50.0';

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
     * @return Response
     */
    public function setup()
    {
        $users = User::visibleFor($this->authenticatedUser())->get();

        return Inertia::render('Report/ReplaceableServices/Setup', compact('users'));
    }

    /**
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
        /** @var array{person: int|string, start: string, end: string} $data */
        $user = User::findOrFail((int) $data['person']);

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
            FileNameService::make(
                self::FILE_TITLE,
                'pdf',
                static::FILE_SIGNATURE,
                [$data['start'], $data['end']],
                false,
                $user
            ),
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

    /**
     * Show the wizard for replacing services of a selected person.
     *
     * @return Response The Inertia response containing the wizard data.
     */
    public function wizard(Request $request): Response
    {
        $data = $request->validate(
            [
                'person' => 'required|int|exists:users,id',
                'start' => 'required|date',
                'end' => 'required|date',
            ]
        );
        /** @var array{person: int|string, start: string, end: string} $data */
        $user = User::findOrFail((int) $data['person']);
        $people = ListedPerson::select(['id', 'name'])
            ->visibleFor($this->authenticatedUser())
            ->get();
        $teams = Team::with('users')->get();

        $services = Participant::with('service')
            ->join('services', 'service_id', 'services.id')
            ->select('services.date', 'service_user.*')
            ->whereHas('service', function ($query) use ($data) {
                $query->between(Carbon::parse($data['start']), Carbon::parse($data['end']));
            })
            ->where('user_id', $user->id)->orderBy('date')->get();

        $ministries = MinistryService::POMA();

        return Inertia::render('Report/ReplaceableServices/Wizard', compact('user', 'people', 'services', 'ministries', 'teams'));
    }
}
