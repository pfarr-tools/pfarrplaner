<?php
/**
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.de
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

use App\City;
use App\Day;
use App\Ministry;
use App\Participant;
use App\Service;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpWord\Shared\Converter;


/**
 * Class PersonReport
 * @package App\Reports
 */
class SingleMinistryReport extends AbstractPDFDocumentReport
{

    /**
     * @var string
     */
    public $title = 'Dienstplan für einen bestimmten Dienst';
    /**
     * @var string
     */
    public $group = 'Listen';
    /**
     * @var string
     */
    public $description = 'Liste mit allen eingeteilten Personen für bestimmte Dienste (PDF oder Excel)';
    public $icon = 'fa fa-file';


    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
        $ministries = $this->getAvailableMinistries();
        return Inertia::render('Report/SingleMinistry/Setup', compact('cities', 'ministries'));
    }

    /**
     * @param Request $request
     * @return mixed|string
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'start' => 'required|date',
                'end' => 'required|date',
                'cities.*' => 'required|int|exists:cities,id',
                'ministries.*' => 'string',
                'file_format' => 'required|string',
                'includeHeader' => 'bool',
            ]
        );

        $allMinistries = $this->getAvailableMinistries();
        $ministries = [];
        foreach ($data['ministries'] as $ministry) {
            $ministries[$ministry] = $allMinistries[$ministry];
        }
        $data['ministries'] = $ministries;

        $services = Service::with(['location'])
            ->between(Carbon::parse($data['start']), Carbon::parse($data['end']))
            ->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->whereIn('city_id', $data['cities'])
            ->ordered()
            ->get();

        $methodName = 'renderDataTo' . strtoupper($data['file_format']);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($data, $services);
        }
        abort(404);
    }

    public function renderDataToPDF($data, $services)
    {
        return $this->sendToBrowser(
            date('Ymd') . ' Dienstplan ' . join(',', $data['ministries']) . '.pdf',
            [
                'start' => $data['start'],
                'end' => $data['end'],
                'services' => $services,
                'ministries' => $data['ministries'],
                'cities' => $data['cities'],
                'includeHeader' => $data['includeHeader']
            ],
            ['format' => 'A4']
        );
    }

    public function renderDataToXLSX($data, $services)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()
            ->getFont()
            ->setName('Arial')
            ->setSize(11);
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();


        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setRowsToRepeatAtTopByStartAndEnd(3, 4);
        $sheet->getPageMargins()
            ->setTop(Converter::cmToInch(1.5))
            ->setBottom(Converter::cmToInch(1))
            ->setHeader(Converter::cmToInch(0.8))
            ->setLeft(Converter::cmToInch(1))
            ->setRight(Converter::cmToInch(1))
            ->setFooter(0);
        $sheet->getHeaderFooter()
            ->setOddHeader(
                '&LDienstplan ' . join(',', $data['ministries']) . ' &RBlatt &P von &N'
            )
            ->setEvenHeader(
                '&LDienstplan ' . join(',', $data['ministries']) . ' &RBlatt &P von &N'
            )
            ->setOddFooter('&CAusdruck vom &D, &T')
            ->setEvenFooter('&CAusdruck vom &D, &T');

        $row = 1;
        if ($data['includeHeader']) {
            // title row
            $sheet->getRowDimension('1')->setRowHeight(21);
            $sheet->setCellValue('A1', 'Dienstplan für ' . join(', ', $data['ministries']))
                ->getStyle('A1')
                ->getFont()->setBold(true)->setSize(16);
            $sheet->setCellValue('A2', 'Von ' . $data['start'] . ' bis ' . $data['end'])
                ->getStyle('A2')
                ->getFont()->setBold(true);
            $sheet->setCellValue(
                'A3',
                \Carbon\Carbon::now()
                    ->setTimezone('Europe/Berlin')
                    ->format('d.m.Y H:i')
                . ' Uhr. Immer aktuell auf www.pfarrplaner.de'
            );
            $row = 5;
        }

        $cols = [
            'Datum' => 10,
            'Zeit' => 10,
            'Ort' => 15,
            '-' => 15,
        ];
        foreach ($data['ministries'] as $ministryKey => $ministry) {
            $cols[$ministry] = 12;
        }
        if (count($data['cities'])<2) unset($cols['-']);

        $c = 64;
        foreach ($cols as $header => $size) {
            $c++;
            $sheet->getColumnDimension(chr($c))->setWidth($size);
            if ($header != '-')
                $sheet->setCellValue(chr($c).$row, $header)->getStyle(chr($c).$row)
                    ->getFont()->setBold(true);
        }
        $row++;

        foreach ($services as $service) {
            $sheet->setCellValue('A'.$row, $service->date->format('d.m.Y'));
            $sheet->setCellValue('B'.$row, $service->timeText());
            $c = 67;
            if (count($data['cities']) > 1) {
                $sheet->setCellValue('C'.$row, $service->city->name);
                $c = 68;
            }
            $sheet->setCellValue(chr($c).$row, $service->locationText());
            foreach ($data['ministries'] as $ministryKey => $ministry) {
                $c++;
                $sheet->setCellValue(chr($c).$row, $service->participantsText($ministryKey));
            }

            $row++;
        }


        $fileName = date('Ymd') . ' Dienstplan ' . join(',', $data['ministries']) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    protected function getAvailableMinistries()
    {
        $tmpMinistries = Participant::all()
            ->pluck('category')
            ->unique();

        $ministries = [];
        foreach ($tmpMinistries as $ministry) {
            switch ($ministry) {
                case 'P':
                    if (Auth::user()->can('gd-pfarrer-bearbeiten')) {
                        $ministries[$ministry] = 'Pfarrer*in';
                    }
                    break;
                case 'O':
                    if (Auth::user()->can('gd-organist-bearbeiten')) {
                        $ministries[$ministry] = 'Organist*in';
                    }
                    break;
                case 'M':
                    if (Auth::user()->can('gd-mesner-bearbeiten')) {
                        $ministries[$ministry] = 'Mesner*in';
                    }
                    break;
                case 'A':
                    if (Auth::user()->can('gd-allgemein-bearbeiten')) {
                        $ministries[$ministry] = 'Weitere Beteiligte';
                    }
                    break;
                default:
                    if (Auth::user()->can('gd-allgemein-bearbeiten')) {
                        $ministries[$ministry] = $ministry;
                    }
            }
        }

        return $ministries;
    }
}
