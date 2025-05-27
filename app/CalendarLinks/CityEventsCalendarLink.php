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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 29.10.2019
 * Time: 13:45
 */

namespace App\CalendarLinks;


use App\Imports\EventCalendarImport;
use App\Imports\OPEventsImport;
use App\Models\Calendar\Occurence;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class CityEventsCalendarLink
 * @package App\CalendarLinks
 */
class CityEventsCalendarLink extends AbstractCalendarLink
{

    /**
     * @var string
     */
    protected $title = 'Kombinierter Veranstaltungskalender';
    /**
     * @var string
     */
    protected $description = 'Kalender, neben den Gottesdiensten auch die Veranstaltungen (aus Outlook, Online Planer) einer Kirchengemeinde enthält.';
    /**
     * @var string
     */
    protected $viewName = 'occurences';

    /** @var string[]  */
    protected $needs = ['cities', 'includeHidden'];

    /**
     * @return array
     */
    public function setupData()
    {
        $cities = Auth::user()->cities;
        return compact('cities');
    }

    /**
     * @param Request $request
     */
    public function setDataFromRequest(Request $request)
    {
        if ($request->has('cities')) {
            $this->data['cities'] = explode(',', $request->get('cities'));
        } elseif ($request->has('city')) {
            $this->data['cities'] = [$request->get('city')];
        } else {
            abort(404);
        }
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array|mixed
     */
    public function getRenderData(Request $request, User $user)
    {
        $hidden = $request->get('includeHidden', false);
        $events = [];

        $events = Occurence::with('event')
            ->startingFrom(Carbon::now()->subYear(1))
            ->whereHas('event', function($query) use ($hidden) {
                $query->whereIn('city_id', $this->data['cities']);
                if (!$hidden) $query->notHidden();
            })
            ->orderBy('start')
            ->get();
        return $events;
    }


    public function setCity(City $city)
    {
        $this->data['city'] = $city->id;
    }

}
