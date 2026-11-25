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

use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpWord\Shared\Converter;

/**
 * Class ServiceTableReport
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

    public function setup(): Response
    {
        $cities = $this->authenticatedUser()->cities;
        $locations = Location::inCities($cities->pluck('id'))->get();

        return Inertia::render('Report/ServiceThemes/Setup', compact('cities', 'locations'));
    }

    /**
     * Baut eine Zelladresse aus Spaltenbuchstabe und Zeilennummer.
     *
     * @param  string  $col  Spaltenbuchstabe, zum Beispiel "A"
     * @param  int  $row  Zeilennummer
     * @return string Zelladresse, zum Beispiel "A12"
     */
    private function cellAddress(string $col, int $row): string
    {
        return $col.$row;
    }

    /**
     * @return string|void
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'cities.*' => 'required|integer|exists:cities,id',
                'locations' => 'nullable|array',
                'locations.*' => 'nullable|integer|exists:locations,id',
                'year' => 'required|integer',
            ]
        );
        /** @var array{cities: array<int, int>, locations?: array<int, int>|null, year: int, ministries?: array<int, string>|null, name_format?: int} $data */
        Carbon::setLocale('de');

        $serviceList = Service::between(
            Carbon::createFromDate($data['year'], 1, 1),
            Carbon::createFromDate($data['year'], 12, 31)->setTime(23, 59, 59),
        )->displayable()
            ->inCitiesAndLocations($data['cities'], $data['locations'] ?? null)
            ->ordered()
            ->get()
            ->groupBy('key_date');

        $cities = City::whereIn('id', $data['cities'])->get();
        /** @var string $cityNames */
        $cityNames = $cities->pluck('name')->join(', ');

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
                '&LEvangelische Kirchengemeinde'.(count($cities) > 1 ? 'n ' : ' ').$cityNames.'&Themenplan '.$data['year'].' - Blatt &P von &N'
            )
            ->setEvenHeader(
                '&LEvangelische Kirchengemeinde'.(count($cities) > 1 ? 'n ' : ' ').$cityNames.'&Themenplan für Gottesdienste '.$data['year'].' - Blatt &P von &N'
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
                if ($service->funerals()->count()) {
                    continue;
                }
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

                $richtext = new RichText;
                $textrun = $richtext->createTextRun($service->trueDate()->isoFormat('dddd, DD.MM.YYYY'));
                $font = $textrun->getFont();
                if ($font === null) {
                    throw new \LogicException('Die Schriftart für die Tabellenzelle konnte nicht geladen werden.');
                }
                $font->setName('Arial')->setSize(8)->setBold(true);
                /** @var array<string, mixed> $liturgicalInfo */
                $liturgicalInfo = $service->liturgicalInfo;
                $sheet->getCell($this->cellAddress('A', $row))->setValue($richtext);
                $sheet->setCellValue("B{$row}", $service->city->name ?? '');
                $sheet->setCellValue($this->cellAddress('C', $row), $liturgicalInfo['title'] ?? '');
                $sheet->setCellValue($this->cellAddress('D', $row), $service->descriptionText());
                $sheet->setCellValue($this->cellAddress('E', $row), $service->timeText(false));
                $sheet->setCellValue($this->cellAddress('F', $row), $service->locationText());
                $sheet->setCellValue($this->cellAddress('G', $row), $liturgicalInfo['push_text'] ?? '');
                if ($liturgicalInfo['litTextsWeeklyQuoteText'] ?? false) {
                    /** @var string $weeklyQuoteText */
                    $weeklyQuoteText = $liturgicalInfo['litTextsWeeklyQuoteText'];
                    /** @var string $weeklyQuote */
                    $weeklyQuote = $liturgicalInfo['litTextsWeeklyQuote'];
                    $sheet->setCellValue($this->cellAddress('H', $row), $weeklyQuoteText.' ('.$weeklyQuote.')');
                }
                if ($liturgicalInfo['perikope'] ?? false) {
                    /** @var int|string $perikope */
                    $perikope = $liturgicalInfo['perikope'];
                    /** @var string $perikopeText */
                    $perikopeText = $liturgicalInfo['litTextsPerikope'.$perikope] ?? '';
                    $sheet->setCellValue($this->cellAddress('I', $row), $perikopeText);
                }

                // liturgical color
                if ($liturgicalInfo['litColor'] ?? false) {
                    /** @var string $litColor */
                    $litColor = $liturgicalInfo['litColor'];
                    $sheet->getStyle($this->cellAddress('B', $row))->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($colors[$litColor]);
                }

                // yellow for special location
                if (! is_object($service->location)) {
                    $sheet->getStyle($this->cellAddress('E', $row))->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('ffffff00');
                }
            }
        }

        // output
        /** @var string $fileTitle */
        $fileTitle = static::FILE_TITLE;

        return $this->sendToBrowser(
            FileNameService::make(
                $fileTitle.' '.$cityNames,
                'xlsx',
                static::FILE_SIGNATURE,
                $data['year'].'-01-01', false, null, 'Y')
        );
    }

    /**
     * Formatiert eine Liste von Personen als kommaseparierten Text.
     *
     * @param  Collection<int, User>  $people  Zu formatierende Personen
     * @param  int  $format  Namensformat, einer der User::NAME_FORMAT_*-Werte
     * @return string Namen der Personen, kommasepariert
     */
    protected function peopleListFormatted(Collection $people, int $format): string
    {
        $recs = [];
        foreach ($people as $person) {
            $recs[] = $person->formattedName($format);
        }

        return implode(', ', $recs);
    }
}
