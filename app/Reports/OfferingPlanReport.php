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

use App\Models\Calendar\Occurence;
use App\Models\Places\City;
use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;


/**
 * Class OfferingPlanReport
 * @package App\Reports
 */
class OfferingPlanReport extends AbstractPDFDocumentReport
{
    public const FILE_SIGNATURE = '77';
    public const FILE_TITLE = 'Opferplan';

    /**
     * @var string
     */
    public $title = 'Opferplan';
    /**
     * @var string
     */
    public $group = 'Opfer';
    /**
     * @var string
     */
    public $description = 'Übersicht aller Opferzwecke für ein Jahr';

    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
        return Inertia::render('Report/OfferingPlan/Setup', compact('cities'));
    }

    /**
     * @param Request $request
     * @return mixed|string
     */
    public function render(Request $request)
    {

        $data = $request->validate(
            [
                'cities.*' => 'required|int|exists:cities,id',
                'year' => 'required|int',
                'includeOfferingCounters' => 'bool',
                'emptyAsOwn' => 'bool',
                'highlightEmpty' => 'bool',
            ]
        );

        $data['occurences'] = Occurence::with('event')->whereHas('event', function ($query) use ($data) {
            $query->servicesOnly()
                ->inCities($data['cities'])
                ->between(Carbon::parse('01-01-'.$data['year'])->startOfYear(), Carbon::parse('01-01-'.$data['year'])->endOfYear())
                ->ordered();
        })->orderBy('start')->get();
        $data['cities'] = City::whereIn('id', $data['cities'])->get();


        return $this->sendToFile(
            FileNameService::make(
                static::FILE_TITLE. ' '.$data['cities']->pluck('name')->join(' '),
                'pdf',
                static::FILE_SIGNATURE,
                $data['year'].'-01-01',
                false,
                null,
                'Y'
            ),
            $data,
            ['format' => 'A4']
        );
    }

}
