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
class ServiceThemesReport extends AbstractExcelDocumentReport
{
    public const FILE_TITLE = 'Themenplan';
    public const FILE_SIGNATURE = '50.0';


    /**
     * @var string
     */
    public $title = 'Thematischer Plan der Gottesdienste';
    /**
     * @var string
     */
    public $description = 'Exceltabelle mit Übersicht zu Gottesdiensten und Themen';
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
        return Inertia::render('Report/ServiceThemes/Setup', compact('cities'));
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

        $serviceList = Service::between(
            Carbon::createFromDate($data['year'], 1, 1),
            Carbon::createFromDate($data['year'], 12, 31)->setTime(23, 59, 59),
        )->displayable()
            ->inCities($data['cities'])
            ->ordered()
            ->get()
            ->groupBy('key_date');

        $cities = City::whereIn('id', $data['cities'])->get();


        $ministries = $data['ministries'] ?? [];
        $nameFormat = $data['name_format'] ?? User::NAME_FORMAT_DEFAULT;

        $columns = [
            'A' => 13,
            'B' => 13,
            'C' => 15,
            'D' => 18,
            'E' => 6,
            'F' => 14,
            'G' => 12,
            'H' => 16,
            'I' => 12,
        ];


        $colors = [
            'white' => 'ffffffff',
            'green' => 'ff00da05',
            'purple' => 'ffdb05ff',
            'black' => 'ff808080',
            'red' => 'ffff0000',
        ];

        $headers = [
            'Ort',
            'Datum',
            "Sonn-/Festtag\nFarbe",
            'Anmerkung zum Gottesdienst',
            "Uhr-\nzeit",
            'Kirche/Ort',
            'Thema',
            'Wochenspruch',
            'Predigttext',
        ];

        $lastColumn = 'I';
        $colCtr = 0;
        foreach ($ministries as $ministry) {
            $colCtr++;
            $col = chr(78 + $colCtr);
            $columns[$col] = 10.73046875;
        }

        $this->spreadsheet->getDefaultStyle()
            ->getFont()
            ->setName('Arial')
            ->setSize(8);
        $this->spreadsheet->setActiveSheetIndex(0);
        $sheet = $this->spreadsheet->getActiveSheet();

        // page layout
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1);
        $sheet->getPageMargins()
            ->setTop(Converter::cmToInch(1.5))
            ->setBottom(Converter::cmToInch(1))
            ->setHeader(Converter::cmToInch(0.8))
            ->setLeft(Converter::cmToInch(1))
            ->setRight(Converter::cmToInch(1))
            ->setFooter(0);
        $sheet->getHeaderFooter()
            ->setOddHeader(
                '&LEvangelische Kirchengemeinde' . (count($cities) > 1 ? 'n ' : ' ') . $cities->pluck('name')->join(
                    ', '
                ) . '&Themenplan ' . $data['year'] . ' - Blatt &P von &N'
            )
            ->setEvenHeader(
                '&LEvangelische Kirchengemeinde' . (count($cities) > 1 ? 'n ' : ' ') . $cities->pluck('name')->join(
                    ', '
                ) . '&Themenplan für Gottesdienste ' . $data['year'] . ' - Blatt &P von &N'
            )
            ->setOddFooter('&CAusdruck vom &D, &T')
            ->setEvenFooter('&CAusdruck vom &D, &T');


        // column width
        foreach ($columns as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $colors = [
            'white' => 'ffffffff',
            'green' => 'ff00da05',
            'purple' => 'ffdb05ff',
            'black' => 'ff808080',
            'red' => 'ffff0000',
        ];

        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue("{$column}1", $header);
            $style = $sheet->getStyle("{$column}1");
            $style->getFont()->setBold(true)->setSize(8);
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $style->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(
                'ff000000'
            );
            $sheet->getStyle("{$column}1")->getBorders()->getOutline()->setBorderStyle(
                Border::BORDER_THIN
            )->getColor()->setARGB('ff000000');
        }



        // content rows

        $row = 2;
        foreach ($serviceList as $myKey => $services) {
            foreach ($services as $service) {
                if ($service->funerals()->count()) continue;
                $row++;

                foreach (array_keys($headers) as $index) {
                    $column = chr(65 + $index);
                    $style = $sheet->getStyle("{$column}{$row}");
                    $style->getFont()->setSize(8);
                    $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                    $style->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(
                        'ff000000'
                    );
                    $sheet->getStyle("{$column}{$row}:{$column}{$row}")->getBorders()->getOutline()->setBorderStyle(
                        Border::BORDER_THIN
                    )->getColor()->setARGB('ff000000');
                }


                $richtext = new RichText();
                $textrun = $richtext->createTextRun($service->date->isoFormat('dddd, DD.MM.YYYY'));
                $textrun->getFont()->setName('Arial')->setSize(8)->setBold(true);
                $sheet->getCell($this->cellAddress('A', $row, $cities))->setValue($richtext);
                $sheet->setCellValue("B{$row}", $service->city->name);
                $sheet->setCellValue($this->cellAddress('C', $row, $cities), $service->liturgicalInfo['title'] ?? '');
                $sheet->setCellValue($this->cellAddress('D', $row, $cities), $service->descriptionText());
                $sheet->setCellValue($this->cellAddress('E', $row, $cities), $service->timeText(false));
                $sheet->setCellValue($this->cellAddress('F', $row, $cities), $service->locationText());
                $sheet->setCellValue($this->cellAddress('G', $row, $cities), $service->liturgicalInfo['push_text'] ?? '');
                if ($service->liturgicalInfo['litTextsWeeklyQuoteText'] ?? false) {
                    $sheet->setCellValue($this->cellAddress('H', $row, $cities), $service->liturgicalInfo['litTextsWeeklyQuoteText']
                                                                               .' ('.$service->liturgicalInfo['litTextsWeeklyQuote'].')');
                }
                if ($service->liturgicalInfo['perikope'] ?? false) {
                    $sheet->setCellValue(
                        $this->cellAddress('I', $row, $cities),
                        $service->liturgicalInfo['litTextsPerikope' . $service->liturgicalInfo['perikope']]
                    );
                }

                // liturgical color
                if ($service->liturgicalInfo['litColor'] ?? false) {
                    $sheet->getStyle($this->cellAddress('B', $row, $cities))->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($colors[$service->liturgicalInfo['litColor']]);
                }

                // yellow for special location
                if (!is_object($service->location)) {
                    $sheet->getStyle($this->cellAddress('E', $row, $cities))->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('ffffff00');
                }
            }
        }


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
