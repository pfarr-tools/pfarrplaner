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
 */

namespace App\Reports;

use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\MinistryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

/**
 * Class ServiceExcelTableReport
 *
 * @package Pfarrplaner
 */
class ServiceExcelTableReport extends AbstractExcelDocumentReport
{
    public const FILE_TITLE = 'Veranstaltungstabelle';
    public const FILE_SIGNATURE = '50.1';
    public const SETTINGS_KEY = 'report.service_excel_table';

    /**
     * @var string
     */
    public $title = 'Excel-Tabelle der Gottesdienste/Veranstaltungen';

    /**
     * @var string
     */
    public $description = 'Große Exceltabelle mit Gottesdiensten, Veranstaltungsdaten, Opfern und Diensten.';

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
        $user = Auth::user();
        $cities = $user->cities;
        $locations = Location::inCities($cities->pluck('id'))->get();
        $ministries = MinistryService::selectList();
        $settings = $user->getSetting(static::SETTINGS_KEY, []);
        $savedMinistries = is_array($settings['ministries'] ?? null) ? $settings['ministries'] : [];
        $readableHeaders = (int)($settings['readable_headers'] ?? 0);
        $servicesOnly = (int)($settings['services_only'] ?? 1);
        $start = Carbon::now()->addMonth()->startOfMonth()->format('Y-m-d');
        $end = Carbon::now()->addMonths(2)->endOfMonth()->format('Y-m-d');

        return Inertia::render(
            'Report/ServiceExcelTable/Setup',
            compact(
                'cities',
                'locations',
                'ministries',
                'savedMinistries',
                'readableHeaders',
                'servicesOnly',
                'start',
                'end'
            )
        );
    }

    /**
     * @param Request $request
     * @return mixed
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
                'start' => 'required|date',
                'end' => 'required|date',
                'ministries' => 'nullable|array',
                'ministries.*' => 'nullable|string',
                'readable_headers' => 'nullable|boolean',
                'services_only' => 'nullable|boolean',
            ]
        );

        $user = Auth::user();
        $ministries = array_values(array_unique($data['ministries'] ?? []));
        $readableHeaders = (bool)($data['readable_headers'] ?? false);
        $servicesOnly = (bool)($data['services_only'] ?? true);
        $user->setSetting(
            static::SETTINGS_KEY,
            [
                'ministries' => $ministries,
                'readable_headers' => $readableHeaders ? 1 : 0,
                'services_only' => $servicesOnly ? 1 : 0,
            ]
        );

        $start = Carbon::parse($data['start'])->startOfDay();
        $end = Carbon::parse($data['end'])->endOfDay();
        $cities = City::whereIn('id', $data['cities'])->get();

        $query = Service::with(['city', 'location'])->between($start, $end)
            ->inCitiesAndLocations($data['cities'], $data['locations'] ?? null)
            ->ordered();

        if (!$servicesOnly) {
            $query->includeAllEventTypes();
        }

        $services = $query->get();

        $columns = $this->buildColumns($ministries, $readableHeaders, $servicesOnly);
        $lastColumnIndex = count($columns);
        $lastColumnLetter = $this->columnLetter($lastColumnIndex);
        $dateColumnLetter = $this->columnLetter($this->findColumnIndex($columns, 'date'));
        $timeColumnLetter = $this->columnLetter($this->findColumnIndex($columns, 'time'));
        $amountColumnLetter = $this->columnLetter($this->findColumnIndex($columns, 'offering_amount'));

        $this->spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $this->spreadsheet->setActiveSheetIndex(0);
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Gottesdienste');
        $sheet->freezePane('A2');

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        foreach ($columns as $index => $column) {
            $letter = $this->columnLetter($index + 1);
            $sheet->setCellValue("{$letter}1", $column['label']);
            $sheet->getColumnDimension($letter)->setAutoSize(false);
            $sheet->getColumnDimension($letter)->setWidth($column['width']);
        }

        $sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray(
            [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'wrapText' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9E2F3'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]
        );
        $sheet->getRowDimension(1)->setRowHeight(32);

        $row = 2;
        foreach ($services as $service) {
            $localDate = $service->date->copy()->setTimezone('Europe/Berlin');
            $liturgy = $service->liturgicalInfo;

            foreach ($columns as $index => $column) {
                $letter = $this->columnLetter($index + 1);
                $value = $this->resolveColumnValue($service, $column['key'], $ministries, $localDate);
                $sheet->setCellValue("{$letter}{$row}", $value);
            }

            $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")->applyFromArray(
                [
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFCCCCCC'],
                        ],
                    ],
                ]
            );

            $sheet->getStyle("{$dateColumnLetter}{$row}")->getNumberFormat()->setFormatCode('dd.mm.yyyy');
            $sheet->getStyle("{$timeColumnLetter}{$row}")->getNumberFormat()->setFormatCode('hh:mm');
            $sheet->getStyle("{$amountColumnLetter}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->resolveLiturgicalColor($service, $liturgy));

            if (($row % 2) === 0) {
                $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8F8F8');
                $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($this->resolveLiturgicalColor($service, $liturgy));
            }

            $row++;
        }

        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE . ' ' . $cities->pluck('name')->join(', '),
                'xlsx',
                static::FILE_SIGNATURE,
                [$data['start'], $data['end']]
            )
        );
    }

    /**
     * @param array $ministries
     * @param bool $readableHeaders
     * @param bool $servicesOnly
     * @return array
     */
    protected function buildColumns(array $ministries, bool $readableHeaders, bool $servicesOnly): array
    {
        $pastorLabel = MinistryService::title('P');
        $organistLabel = MinistryService::title('O');
        $sacristanLabel = MinistryService::title('M');

        $columns = [
            ['key' => 'liturgy', 'label' => $this->headerLabel('SonnFesttagFarbe', 'Sonn-/Festtag-Farbe', $readableHeaders), 'width' => 24],
            ['key' => 'date', 'label' => $this->headerLabel('Datum', 'Datum', $readableHeaders), 'width' => 12],
            ['key' => 'time', 'label' => $this->headerLabel('Zeit', 'Zeit', $readableHeaders), 'width' => 10],
            ['key' => 'hidden', 'label' => $this->headerLabel('NOe', 'NÖ', $readableHeaders), 'width' => 6],
            ['key' => 'baptism', 'label' => $this->headerLabel('TG', 'TG', $readableHeaders), 'width' => 6],
            ['key' => 'eucharist', 'label' => $this->headerLabel('AM', 'AM', $readableHeaders), 'width' => 6],
            ['key' => 'title', 'label' => $this->headerLabel('Titel', 'Titel', $readableHeaders), 'width' => 28],
            ['key' => 'description', 'label' => $this->headerLabel('AnmerkungenGD', 'Anmerkungen GD', $readableHeaders), 'width' => 28],
            ['key' => 'location', 'label' => $this->headerLabel('KircheOrt', 'Kirche/Ort', $readableHeaders), 'width' => 20],
            ['key' => 'city', 'label' => $this->headerLabel('Ort', 'Ort', $readableHeaders), 'width' => 18],
            ['key' => 'internal_remarks', 'label' => $this->headerLabel('AnmerkungenIntern', 'Anmerkungen intern', $readableHeaders), 'width' => 28],
            ['key' => 'announcements', 'label' => $this->headerLabel('ZusBekanntgaben', 'Zus. Bekanntgaben', $readableHeaders), 'width' => 28],
            [
                'key' => 'pastors',
                'label' => $readableHeaders ? $pastorLabel : $this->toPascalCaseHeading($pastorLabel),
                'width' => 24
            ],
            [
                'key' => 'organists',
                'label' => $readableHeaders ? $organistLabel : $this->toPascalCaseHeading($organistLabel),
                'width' => 24
            ],
            [
                'key' => 'sacristans',
                'label' => $readableHeaders ? $sacristanLabel : $this->toPascalCaseHeading($sacristanLabel),
                'width' => 24
            ],
        ];

        if (!$servicesOnly) {
            array_splice(
                $columns,
                10,
                0,
                [['key' => 'event_class', 'label' => $this->headerLabel('VeranstaltungsTyp', 'Veranstaltungstyp', $readableHeaders), 'width' => 20]]
            );
        }

        foreach ($ministries as $ministry) {
            $title = MinistryService::title($ministry);
            $columns[] = [
                'key' => 'ministry:' . $ministry,
                'label' => $readableHeaders ? $title : $this->toPascalCaseHeading($title),
                'width' => 24,
            ];
        }

        return array_merge(
            $columns,
            [
                ['key' => 'offerings_counter1', 'label' => $this->headerLabel('Opferzaehler1', 'Opferzähler 1', $readableHeaders), 'width' => 14],
                ['key' => 'offerings_counter2', 'label' => $this->headerLabel('Opferzaehler2', 'Opferzähler 2', $readableHeaders), 'width' => 14],
                ['key' => 'offering_goal', 'label' => $this->headerLabel('OpferBestimmung', 'Opferbestimmung', $readableHeaders), 'width' => 28],
                ['key' => 'offering_description', 'label' => $this->headerLabel('OpferAnmerkungen', 'Opferanmerkungen', $readableHeaders), 'width' => 28],
                ['key' => 'offering_amount', 'label' => $this->headerLabel('OpferBetrag', 'Opferbetrag', $readableHeaders), 'width' => 14],
            ]
        );
    }

    /**
     * @param string $pascalCaseLabel
     * @param string $readableLabel
     * @param bool $readableHeaders
     * @return string
     */
    protected function headerLabel(string $pascalCaseLabel, string $readableLabel, bool $readableHeaders): string
    {
        return $readableHeaders ? $readableLabel : $pascalCaseLabel;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function toPascalCaseHeading(string $value): string
    {
        $words = preg_split('/[^[:alnum:]]+/u', $value) ?: [];
        $words = array_filter($words, fn ($word) => $word !== '');
        return implode('', array_map(fn ($word) => mb_convert_case($word, MB_CASE_TITLE, 'UTF-8'), $words));
    }

    /**
     * @param Service $service
     * @param string $key
     * @param array $ministries
     * @param Carbon $localDate
     * @return mixed
     */
    protected function resolveColumnValue(
        Service $service,
        string $key,
        array $ministries,
        Carbon $localDate
    )
    {
        if (str_starts_with($key, 'ministry:')) {
            $category = substr($key, 9);
            if (!in_array($category, $ministries, true)) {
                return '';
            }
            return $this->peopleListFormatted($service->participantsByCategory($category));
        }

        return match ($key) {
            'liturgy' => $service->liturgicalInfo['Bezeichnung'] ?? '',
            'date' => ExcelDate::PHPToExcel($localDate->copy()->startOfDay()),
            'time' => ExcelDate::PHPToExcel($localDate),
            'hidden' => $service->hidden ? 'X' : '',
            'baptism' => $service->baptism ? 'X' : '',
            'eucharist' => $service->eucharist ? 'X' : '',
            'title' => $service->titleText(false, false),
            'description' => (string)$service->description,
            'location' => $service->locationText(),
            'city' => $service->city->name ?? '',
            'event_class' => $this->eventClassLabel($service->event_class),
            'internal_remarks' => (string)$service->internal_remarks,
            'announcements' => (string)$service->announcements,
            'pastors' => $this->peopleListFormatted($service->pastors),
            'organists' => $this->peopleListFormatted($service->organists),
            'sacristans' => $this->peopleListFormatted($service->sacristans),
            'offerings_counter1' => (string)$service->offerings_counter1,
            'offerings_counter2' => (string)$service->offerings_counter2,
            'offering_goal' => $service->offeringGoal(),
            'offering_description' => $service->offeringDescription(),
            'offering_amount' => is_numeric(str_replace(',', '.', (string)$service->offering_amount))
                ? (float)str_replace(',', '.', (string)$service->offering_amount)
                : (string)$service->offering_amount,
            default => '',
        };
    }

    /**
     * @param Service $service
     * @param array $liturgy
     * @return string
     */
    protected function resolveLiturgicalColor(Service $service, array $liturgy): string
    {
        $colors = [
            'white' => 'FFFFFFFF',
            'green' => 'FF00DA05',
            'purple' => 'FFDB05FF',
            'black' => 'FF808080',
            'red' => 'FFFF0000',
        ];

        if ($service->hasDescription('Konfirmation') || $service->hasDescription('Konfirmandenabendmahl')) {
            return $colors['red'];
        }

        return $colors[$liturgy['CSS-Farbe'] ?? 'white'] ?? $colors['white'];
    }

    /**
     * @param string|null $eventClass
     * @return string
     */
    protected function eventClassLabel(?string $eventClass): string
    {
        return match ($eventClass) {
            'service' => 'Gottesdienst',
            'event' => 'Andere Veranstaltung',
            default => (string)$eventClass,
        };
    }

    /**
     * @param Collection $people
     * @return string
     */
    protected function peopleListFormatted(Collection $people): string
    {
        $recs = [];
        foreach ($people as $person) {
            if ($person instanceof User) {
                $recs[] = $person->formattedName(User::NAME_FORMAT_FIRST_AND_LAST);
            }
        }
        return join(', ', $recs);
    }

    /**
     * @param int $index
     * @return string
     */
    protected function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }
        return $letter;
    }

    /**
     * @param array $columns
     * @param string $key
     * @return int
     */
    protected function findColumnIndex(array $columns, string $key): int
    {
        foreach ($columns as $index => $column) {
            if (($column['key'] ?? null) === $key) {
                return $index + 1;
            }
        }

        return 1;
    }
}
