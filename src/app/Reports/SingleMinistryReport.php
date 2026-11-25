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
use App\Models\People\Participant;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpWord\Shared\Converter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class PersonReport
 */
class SingleMinistryReport extends AbstractPDFDocumentReport
{
    public const FILE_TITLE = 'Dienstplan';

    public const FILE_SIGNATURE = '50.0';

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
     * @return Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->cities;
        $locations = Location::inCities($cities->pluck('id'))->get();
        $ministries = $this->getAvailableMinistries();

        return Inertia::render('Report/SingleMinistry/Setup', compact('cities', 'locations', 'ministries'));
    }

    /**
     * @return mixed|string
     */
    public function render(Request $request)
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate(
            [
                'start' => 'required|date',
                'end' => 'required|date',
                'cities.*' => 'required|int|exists:cities,id',
                'locations' => 'nullable|array',
                'locations.*' => 'nullable|int|exists:locations,id',
                'ministries.*' => 'string',
                'file_format' => 'required|string',
                'includeHeader' => 'bool',
            ]
        );

        /** @var array<int, string> $requestedMinistries */
        $requestedMinistries = $data['ministries'] ?? [];
        $allMinistries = $this->getAvailableMinistries();
        $ministries = [];
        foreach ($requestedMinistries as $ministry) {
            $ministries[$ministry] = $allMinistries[$ministry];
        }
        $data['ministries'] = $ministries;

        /** @var string $startDate */
        $startDate = $data['start'];
        /** @var string $endDate */
        $endDate = $data['end'];
        /** @var string $fileFormat */
        $fileFormat = $data['file_format'];

        $services = Service::with(['location'])
            ->where('event_class', 'service') // I wonder why the global scope doesn't work here?
            ->between(Carbon::parse($startDate), Carbon::parse($endDate))
            ->whereDoesntHave('funerals')
            ->whereDoesntHave('weddings')
            ->inCitiesAndLocations($data['cities'], $data['locations'] ?? null)
            ->ordered()
            ->get();

        $methodName = 'renderDataTo'.strtoupper($fileFormat);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($data, $services);
        }
        abort(404);
    }

    /**
     * Erzeugt den PDF-Download für den ausgewählten Dienstplan.
     *
     * @param  array<string, mixed>  $data  Validierte Berichtsparameter.
     * @param  Collection<int, Service>  $services  Dienste, die dargestellt werden sollen.
     * @return mixed PDF-Download-Antwort.
     */
    public function renderDataToPDF(array $data, Collection $services)
    {
        /** @var array<string, string> $ministries */
        $ministries = $data['ministries'];
        /** @var string $startDate */
        $startDate = $data['start'];
        /** @var string $endDate */
        $endDate = $data['end'];

        return $this->sendToBrowser(
            FileNameService::make(
                self::FILE_TITLE.' '.implode(',', $ministries),
                'pdf',
                static::FILE_SIGNATURE,
                [$startDate, $endDate]
            ),
            [
                'start' => $startDate,
                'end' => $endDate,
                'services' => $services,
                'ministries' => $ministries,
                'cities' => $data['cities'],
                'includeHeader' => $data['includeHeader'],
            ],
            ['format' => 'A4']
        );
    }

    /**
     * Erzeugt den XLSX-Download für den ausgewählten Dienstplan.
     *
     * @param  array<string, mixed>  $data  Validierte Berichtsparameter.
     * @param  Collection<int, Service>  $services  Dienste, die dargestellt werden sollen.
     * @return mixed XLSX-Download-Antwort.
     */
    public function renderDataToXLSX(array $data, Collection $services)
    {
        /** @var array<string, string> $ministries */
        $ministries = $data['ministries'];
        /** @var string $startDate */
        $startDate = $data['start'];
        /** @var string $endDate */
        $endDate = $data['end'];
        /** @var array<int, int> $cities */
        $cities = $data['cities'];

        $spreadsheet = new Spreadsheet;
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
                '&LDienstplan '.implode(',', $ministries).' &RBlatt &P von &N'
            )
            ->setEvenHeader(
                '&LDienstplan '.implode(',', $ministries).' &RBlatt &P von &N'
            )
            ->setOddFooter('&CAusdruck vom &D, &T')
            ->setEvenFooter('&CAusdruck vom &D, &T');

        $row = 1;
        if ($data['includeHeader']) {
            // title row
            $sheet->getRowDimension(1)->setRowHeight(21);
            $sheet->setCellValue('A1', 'Dienstplan für '.implode(', ', $ministries))
                ->getStyle('A1')
                ->getFont()->setBold(true)->setSize(16);
            $sheet->setCellValue('A2', 'Von '.$startDate.' bis '.$endDate)
                ->getStyle('A2')
                ->getFont()->setBold(true);
            $sheet->setCellValue(
                'A3',
                Carbon::now()
                    ->setTimezone('Europe/Berlin')
                    ->format('d.m.Y H:i')
                .' Uhr. Immer aktuell auf www.pfarrplaner.de'
            );
            $row = 5;
        }

        $cols = [
            'Datum' => 10,
            'Zeit' => 10,
            'Ort' => 15,
            '-' => 15,
        ];
        foreach ($ministries as $ministryKey => $ministry) {
            $cols[$ministry] = 12;
        }
        if (count($cities) < 2) {
            unset($cols['-']);
        }

        $c = 64;
        foreach ($cols as $header => $size) {
            $c++;
            $sheet->getColumnDimension(chr($c))->setWidth($size);
            if ($header != '-') {
                $sheet->setCellValue(chr($c).$row, $header)->getStyle(chr($c).$row)
                    ->getFont()->setBold(true);
            }
        }
        $row++;

        foreach ($services as $service) {
            $sheet->setCellValue('A'.$row, $service->trueDate()->format('d.m.Y'));
            $sheet->setCellValue('B'.$row, $service->timeText());
            $c = 67;
            if (count($cities) > 1) {
                $sheet->setCellValue('C'.$row, $service->city->name ?? '');
                $c = 68;
            }
            $sheet->setCellValue(chr($c).$row, $service->locationText());
            foreach ($ministries as $ministryKey => $ministry) {
                $c++;
                $sheet->setCellValue(chr($c).$row, $service->participantsText($ministryKey));
            }

            $row++;
        }

        return $this->sendXlsxToBrowser($fileName = FileNameService::make(
            self::FILE_TITLE.' '.implode(',', $ministries),
            'xlsx',
            static::FILE_SIGNATURE,
            [$startDate, $endDate]
        ), $spreadsheet);

    }

    /**
     * Ermittelt die Dienste, die der aktuelle Benutzer bearbeiten darf.
     *
     * @return array<string, string> Dienste mit ihren Anzeigenamen.
     */
    protected function getAvailableMinistries(): array
    {
        $tmpMinistries = Participant::query()
            ->pluck('category')
            ->unique();

        $ministries = [];
        foreach ($tmpMinistries as $ministry) {
            if (! is_string($ministry)) {
                continue;
            }
            switch ($ministry) {
                case 'P':
                    if ($this->authenticatedUser()->can('gd-pfarrer-bearbeiten')) {
                        /** @var string $label */
                        $label = config('labels.pastor');
                        $ministries[$ministry] = $label;
                    }
                    break;
                case 'O':
                    if ($this->authenticatedUser()->can('gd-organist-bearbeiten')) {
                        /** @var string $label */
                        $label = config('labels.organist');
                        $ministries[$ministry] = $label;
                    }
                    break;
                case 'M':
                    if ($this->authenticatedUser()->can('gd-mesner-bearbeiten')) {
                        /** @var string $label */
                        $label = config('labels.sacristan');
                        $ministries[$ministry] = $label;
                    }
                    break;
                case 'A':
                    if ($this->authenticatedUser()->can('gd-allgemein-bearbeiten')) {
                        $ministries[$ministry] = 'Weitere Beteiligte';
                    }
                    break;
                default:
                    if ($this->authenticatedUser()->can('gd-allgemein-bearbeiten')) {
                        $ministries[$ministry] = $ministry;
                    }
            }
        }

        return $ministries;
    }

    /**
     * Sendet die Tabelle als XLSX-Download an den Browser.
     *
     * @param  string  $filename  Dateiname des Downloads.
     * @param  Spreadsheet  $spreadsheet  Zu exportierende Tabelle.
     */
    public function sendXlsxToBrowser(string $filename, Spreadsheet $spreadsheet): BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->deleteFileAfterSend(true);
    }
}
