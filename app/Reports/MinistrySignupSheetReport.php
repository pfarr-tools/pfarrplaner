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

use App\Models\Calendar\Day;
use App\Services\FileNameService;
use App\Services\MinistryService;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MinistrySignupSheetReport extends AbstractPDFDocumentReport
{

    public const FILE_SIGNATURE = '50.0';
    public const FILE_TITLE = 'Leerer Dienstplan';


    /**
     * @var string
     */
    public $title = 'Leerer Dienstplan';
    /**
     * @var string
     */
    public $group = 'Listen';
    /**
     * @var string
     */
    public $description = 'Dienstplan für bestimmte Dienste zum Eintragen im Umlaufverfahren';

    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->writableCities;
        $ministries = MinistryService::selectList();
        return Inertia::render('Report/MinistrySignupSheet/Setup', compact('cities', 'ministries'));
    }

    public function render(Request $request)
    {
        $data = $request->validate([
                                       'cities.*' => 'required|int|exists:cities,id',
                                       'ministries.*' => 'string',
                                       'start' => 'required|date_format:d.m.Y',
                                       'end' => 'required|date_format:d.m.Y',
                                   ]);

        foreach ($data['cities'] as $key => $id) {
            $data['cities'][$key] = City::find($id);
        }
        $data['cities'] = collect($data['cities']);
        $data['start'] = Carbon::createFromFormat('d.m.Y H:i:s', $data['start'] . ' 0:00:00');
        $data['end'] = Carbon::createFromFormat('d.m.Y H:i:s', $data['end'] . ' 23:59:00');
        $data['services'] = Service::between($data['start'], $data['end'])
            ->whereIn('city_id', $data['cities']->pluck('id'))->ordered()->get();

        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE . ' ' . join(', ', $data['ministries']),
                'pdf',
                static::FILE_SIGNATURE,
                [$data['start'], $data['end']]
            ),
            $data,
            ['format' => 'A4-L']
        );
    }


}
