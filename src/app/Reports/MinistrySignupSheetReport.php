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

use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\MinistryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MinistrySignupSheetReport extends AbstractPDFDocumentReport
{
    public const FILE_SIGNATURE = '50.0';

    public const FILE_TITLE = 'Leerer Dienstplan';

    protected $landscape = true;

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
     * @return Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->writableCities;
        $ministries = MinistryService::selectList();

        return Inertia::render('Report/MinistrySignupSheet/Setup', compact('cities', 'ministries'));
    }

    /**
     * Erzeugt einen leeren Dienstplan als PDF-Download.
     *
     * @param  Request  $request  Anfrage mit Städten, Diensten und Zeitraum.
     * @return string Der PDF-Download.
     */
    public function render(Request $request)
    {
        $data = $request->validate([
            'cities.*' => 'required|int|exists:cities,id',
            'ministries.*' => 'string',
            'start' => 'required|date_format:d.m.Y',
            'end' => 'required|date_format:d.m.Y',
        ]);
        /** @var array{cities: array<int, int|string>, ministries: array<int, string>, start: string, end: string} $data */
        $cities = [];
        foreach ($data['cities'] as $key => $id) {
            $cities[$key] = City::findOrFail($id);
        }
        $cities = collect($cities);
        $start = $this->requireParsedDate(Carbon::createFromFormat('d.m.Y H:i:s', $data['start'].' 0:00:00'));
        $end = $this->requireParsedDate(Carbon::createFromFormat('d.m.Y H:i:s', $data['end'].' 23:59:00'));
        $services = Service::between($start, $end)
            ->inCities($cities->pluck('id'))->ordered()->get();

        $viewData = [
            'cities' => $cities,
            'ministries' => $data['ministries'],
            'start' => $start,
            'end' => $end,
            'services' => $services,
        ];

        /** @var string $result */
        $result = $this->sendToBrowser(
            FileNameService::make(
                self::FILE_TITLE.' '.implode(', ', $data['ministries']),
                'pdf',
                static::FILE_SIGNATURE,
                [$start, $end]
            ),
            $viewData,
            null
        );

        return $result;
    }
}
