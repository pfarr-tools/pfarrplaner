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

use App\Imports\EventCalendarImport;
use App\Imports\OPEventsImport;
use App\Models\Calendar\Occurence;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;


/**
 * Class NewsletterReport
 * @package App\Reports
 */
class NewsletterReport extends AbstractWordDocumentReport
{

    /**
     * @var string
     */
    public $title = 'Newsletter';
    /**
     * @var string
     */
    public $group = 'Veröffentlichungen';
    /**
     * @var string
     */
    public $description = 'Gottesdienstliste für den Newsletter';
    /**
     * @var string
     */
    public $icon = 'fa fa-file-code';


    protected $inertia = true;


    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
        return Inertia::render('Report/Newsletter/Setup', compact('cities'));
    }


    /**
     * @param Request $request
     * @return \Inertia\Response
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'cities.*' => 'required|int|exists:cities,id',
                'start' => 'required|date',
                'end' => 'required|date',
                'includeWeeklyVerse' => 'bool'
            ]
        );

        $start = Carbon::parse($data['start'])->setTime(0,0,0);
        $end = Carbon::parse($data['end'])->setTime(23,59,59);

        $events = Occurence::with('event')
            ->between($start, $end)
            ->whereHas('service', function ($query) use ($data, $start, $end) {
                $query->inCities($data['cities'])
                    ->displayable($start)
                    ->notHidden();
            })
            ->orderBy('start')
            ->get()
            ->groupBy(function ($item) {
                return $item->start->format('Y-m-d');
            });


        $featuredEvents = Occurence::with('event')
            ->adRunningAt('newsletter', $start)
            ->whereHas('service', function ($query) use ($data, $start, $end) {
                $query->inCities($data['cities'])
                    ->displayable($start)
                    ->notHidden();
            })
            ->orderBy('start')
            ->get();

        $html = View::make('reports.newsletter.html',
                           compact('events', 'data', 'start', 'end', 'featuredEvents'))
            ->render();

        return Inertia::render('Report/Newsletter/Render', compact('html'));

    }


}
