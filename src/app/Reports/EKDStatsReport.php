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

use App\Services\FileNameService;
use App\Services\LiturgyService;
use App\Services\MinistryService;
use App\Models\Calendar\Day;
use App\Models\Places\City;
use App\Models\Service;
use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpWord\Shared\Converter;

/**
 * Class ServiceTableReport
 * @package App\Reports
 */
class EKDStatsReport extends AbstractExcelDocumentReport
{
    public const FILE_TITLE = 'EKD Statistik';
    public const FILE_SIGNATURE = '50.0';


    /**
     * @var string
     */
    public $title = 'EKD Statistik';
    /**
     * @var string
     */
    public $description = 'Gottesdienste für EKD Statistik';
    /**
     * @var string
     */
    public $group = 'Listen';

    protected $inertia = true;


    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
        return Inertia::render('Report/EKDStats/Setup', compact('cities'));
    }


    private function cellAddress($col, $row)
    {
        return $col . $row;
    }

    /**
     * @param Request $request
     * @return string|void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'cities.*' => 'required|integer|exists:cities,id',
                'year' => 'required|integer',
            ]
        );

        Carbon::setLocale('de');

        $serviceList = Service::query()->with([])->between(
            Carbon::createFromDate($data['year'], 1, 1),
            Carbon::createFromDate($data['year'], 12, 31)->setTime(23, 59, 59),
        )->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->inCities($data['cities'])
            ->ordered();

        $dates = Service::query()->with([])->between(
            Carbon::createFromDate($data['year'], 1, 1),
            Carbon::createFromDate($data['year'], 12, 31)->setTime(23, 59, 59),
        )->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->inCities($data['cities'])
            ->ordered()
            ->selectRaw('services.*, DATE(services.date) AS dayDate')
            ->distinct()
            ->pluck('dayDate');

        $sundays = [];
        $weekdays = [];

        foreach ($dates as $date) {
            if (Carbon::parse($date)->isSunday()) {
                $sundays[] = $date;
            } elseif (count(LiturgyService::getLiturgyInfoByDate($date))) {
                $sundays[] = $date;
            } else {
                $weekdays[] = $date;
            }
        }


        $ctSundays = Service::query()->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->whereHas('pastors')
            ->inCities($data['cities'])
            ->whereIn(DB::raw('DATE(date)'), $sundays)
            ->count();
        $ctWeekdays = Service::query()->with([])->between(
            Carbon::createFromDate($data['year'], 1, 1),
            Carbon::createFromDate($data['year'], 12, 31)->setTime(23, 59, 59),
        )->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->whereHas('pastors')
            ->inCities($data['cities'])
            ->whereIn(DB::raw('DATE(date)'), $weekdays)
            ->count();

        $weekdayServices = Service::query()->with([])->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->inCities($data['cities'])
            ->whereIn(DB::raw('DATE(date)'), $weekdays)
            ->whereHas('pastors')
            ->get()
            ->groupBy(function($item) {
                return $item->locationTextWithCity;
            });


        $ctChristmasEve = Service::with([])->between(
            Carbon::createFromDate($data['year'], 12, 24, 0, 0, 0),
            Carbon::createFromDate($data['year'], 12, 24)->setTime(23, 59, 59),
        )->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->inCities($data['cities'])
            ->count();


        $ctOnline = Service::query()->with([])->between(
            Carbon::createFromDate($data['year'], 1, 1),
            Carbon::createFromDate($data['year'], 12, 31)->setTime(23, 59, 59),
        )->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->inCities($data['cities'])
            ->whereHas('pastors')
            ->where('youtube_url', '!=', '')
            ->count();

        $ctEucharist = Service::query()->with([])->between(
            Carbon::createFromDate($data['year'], 1, 1),
            Carbon::createFromDate($data['year'], 12, 31)->setTime(23, 59, 59),
        )->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->inCities($data['cities'])
            ->whereHas('pastors')
            ->where('eucharist', 1)
            ->count();


        $cities = City::whereIn('id', $data['cities'])->get();



        $columns = [
            'A' => 35,
            'B' => 5,
            'C' => 12,
            'D' => 18,
        ];


        $this->spreadsheet->getDefaultStyle()
            ->getFont()
            ->setName('Arial')
            ->setSize(10);
        $this->spreadsheet->setActiveSheetIndex(0);
        $sheet = $this->spreadsheet->getActiveSheet();

        // page layout
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1);
        $sheet->getPageMargins()
            ->setTop(Converter::cmToInch(1))
            ->setBottom(Converter::cmToInch(1))
            ->setLeft(Converter::cmToInch(.7))
            ->setRight(Converter::cmToInch(.7));


        // column width
        foreach ($columns as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // content rows
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->setCellValue('A1', 'Statistikdaten für '.$data['year']);

        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->setCellValue('A3', 'Gottesdienste an Sonn- und Feiertagen');
        $sheet->setCellValue('B3', $ctSundays);

        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->setCellValue('A4', 'Gottesdienste an Werktagen');
        $sheet->setCellValue('B4', $ctWeekdays);

        $row = 6;
        foreach ($weekdayServices as $location => $services) {
            $sheet->setCellValue('A'.$row, '--> '.$location);
            $sheet->setCellValue('B'.$row, count($services));
            $row++;

            /** @var Service $service */
            foreach ($services as $service) {
                $sheet->getStyle('C'.$row)->getFont()->setSize(8);
                $sheet->setCellValue('C'.$row, $service->date->setTimezone('Europe/Berlin')->format('d.m.Y H:i'));
                $sheet->getStyle('D'.$row)->getFont()->setSize(8);
                $sheet->setCellValue('D'.$row, $service->titleText(false));
                $row++;
            }

            $row++;
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->setCellValue("A{$row}", 'Gottesdienste am 24.12.');
        $sheet->setCellValue("B{$row}", $ctChristmasEve);
        $row += 2;

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->setCellValue("A{$row}", 'Zusätzlich online verfügbare Gottesdienste');
        $sheet->setCellValue("B{$row}", $ctOnline);
        $row += 2;

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->setCellValue("A{$row}", 'Abendmahlsgottesdienste');
        $sheet->setCellValue("B{$row}", $ctEucharist);
        $row += 2;



        // output
        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE.' '.$cities->pluck('name')->join(', '),
                'xlsx',
                static::FILE_SIGNATURE,
                $data['year'].'-01-01', false, null, 'Y')
        );
    }

    /**
     * @param $people
     * @param $format
     * @return string
     */
    protected function peopleListFormatted($people, $format)
    {
        $recs = [];
        foreach ($people as $person) {
            $recs[] = $person->formattedName($format);
        }
        return join(', ', $recs);
    }
}
