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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 29.10.2019
 * Time: 13:45
 */

namespace App\CalendarLinks;

use App\Models\People\User;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AllServicesCalendarLink
 */
class AllServicesCalendarLink extends AbstractCalendarLink
{
    /**
     * @var string
     */
    protected $title = 'Gottesdienste nach Kirchengemeinden';

    /**
     * @var string
     */
    protected $description = 'Kalender, der alle Gottesdienste ausgewählter Kirchengemeinden enthält';

    protected $needs = ['cities', 'includeHidden'];

    /**
     * Prepare the setup data for the all-services calendar.
     *
     * @return array<string, mixed> Cities available to the authenticated user.
     */
    public function setupData()
    {
        $user = Auth::user();
        $cities = $user === null ? [] : $user->cities;

        return compact('cities');
    }

    /**
     * @param  Request  $request  Incoming request.
     * @return void
     */
    public function setDataFromRequest(Request $request)
    {
        $cities = $request->get('includeCities');
        $this->data['cities'] = implode('-', array_map(
            static fn ($city): string => is_scalar($city) ? (string) $city : '',
            is_array($cities) ? $cities : [],
        ));
    }

    /**
     * @return Collection<int, Service>
     */
    public function getRenderData(Request $request, User $user)
    {
        $cities = $request->get('cities', '');
        $cityIds = explode('-', is_string($cities) ? $cities : '');
        $serviceQuery = Service::with(['location'])
            ->inCities($cityIds)->startingFrom(Carbon::now()->subYears(1)->startOfYear());
        if (! $request->get('includeHidden', 0)) {
            $serviceQuery->notHidden();
        }

        return $serviceQuery->get();
    }
}
