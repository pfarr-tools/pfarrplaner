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
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Class BulletinReport
 */
class RitesReport extends AbstractWordDocumentReport
{
    public const FILE_TITLE = 'Freud und Leid';

    public const FILE_SIGNATURE = '91.8';

    /**
     * @var string
     */
    public $title = 'Freud & Leid';

    /**
     * @var string
     */
    public $group = 'Listen';

    /**
     * @var string
     */
    public $description = 'Freud & Leid (Kasualien) für den Gemeindebrief';

    /**
     * @var array<string, mixed> Validierte Berichtsparameter.
     */
    protected $data = [];

    protected $inertia = true;

    /**
     * @return Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->cities;

        return Inertia::render('Report/Rites/Setup', compact('cities'));
    }

    /**
     * @return RedirectResponse|string
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'includeCities' => 'required',
                'includeCities.*' => 'int|exists:cities,id',
                'start' => 'required|date',
                'end' => 'required|date',
                'baptismDatesStart' => 'required|date',
                'baptismDatesEnd' => 'required|date',
            ]
        );
        /** @var array{includeCities: array<int, int>, start: string, end: string, baptismDatesStart: string, baptismDatesEnd: string} $data */
        $this->data = $data;

        $section = $this->commonDocumentSetup();
        $start = Carbon::parse($data['start'])->startOfDay();
        $end = Carbon::parse($data['end'])->endOfDay();
        $baptismDatesStart = Carbon::parse($data['baptismDatesStart'])->startOfDay();
        $baptismDatesEnd = Carbon::parse($data['baptismDatesEnd'])->endOfDay();

        $this->renderBaptisms(
            $section,
            Service::with('baptisms')
                ->whereHas('baptisms')
                ->inCities($data['includeCities'])
                ->between($start, $end)
                ->ordered()
                ->get()
        );

        $this->renderBaptismDates(
            $section,
            Service::where('baptism', 1)
                ->inCities($data['includeCities'])
                ->between($baptismDatesStart, $baptismDatesEnd)
                ->ordered()
                ->get()
        );

        $this->renderWeddings(
            $section,
            Service::with('weddings')
                ->whereHas('weddings')
                ->inCities($data['includeCities'])
                ->between($start, $end)
                ->ordered()
                ->get()
        );

        $this->renderFunerals(
            $section,
            Service::with('funerals')
                ->whereHas('funerals', function ($q) {
                    $q->where('funerals.type', '!=', 'Urnenbeisetzung');
                })
                ->inCities($data['includeCities'])
                ->between($start, $end)
                ->ordered()
                ->get()
        );

        return $this->sendToBrowser(
            FileNameService::make(
                self::FILE_TITLE,
                'docx',
                static::FILE_SIGNATURE,
                [$start, $end]
            )

        );

    }

    /**
     * @return Section
     */
    public function commonDocumentSetup()
    {
        $this->wordDocument->setDefaultFontName('Quicksand');
        $this->wordDocument->setDefaultFontSize(10);
        $section = $this->wordDocument->addSection(
            [
                'marginTop' => Converter::cmToTwip(1.9),
                'marginBottom' => Converter::cmToTwip(0.25),
                'marginLeft' => Converter::cmToTwip(1.59),
                'marginRight' => Converter::cmToTwip(0.25),
            ]
        );

        return $section;
    }

    /**
     * Schreibt eine Überschrift in den Bericht.
     *
     * @param  string  $title  Überschriftstext.
     */
    public function heading(Section $section, string $title): void
    {
        $run = $section->addTextRun();
        $run->addText($title, ['name' => 'Yanone Kaffeesatz', 'size' => 20, 'bold' => true]);
    }

    public function getListRun(Section $section): TextRun
    {
        return $section->addTextRun([
            'tabs' => [
                new Tab('right', (int) Converter::cmToTwip(6)),
                new Tab('left', (int) Converter::cmToTwip(6.7)),
                new Tab('left', (int) Converter::cmToTwip(7)),
            ],
        ]);
    }

    /**
     * Schreibt die Datumsspalte für einen Gottesdienst.
     *
     * @param  Service  $service  Gottesdienst, dessen Datum dargestellt wird.
     * @param  Carbon|null  $lastDate  Zuletzt dargestelltes Datum.
     */
    public function renderDateColumn(TextRun $run, Service $service, ?Carbon &$lastDate): void
    {
        $run->addText("\t");
        if ($lastDate === null || $service->trueDate()->format('Ymd') != $lastDate->format('Ymd')) {
            $run->addText($service->trueDate()->isoFormat('LL'));
        }
        $lastDate = $service->trueDate();
        $run->addText("\t");
        /** @var array{includeCities: array<int, int>} $data */
        $data = $this->data;
        if (count($data['includeCities']) > 1 && $service->city !== null) {
            $run->addText('('.substr(utf8_decode($service->city->name), 0, 1).')');
        }
        $run->addText("\t");
    }

    /**
     * @param  Collection<int, Service>  $baptisms  Services with baptisms to render.
     */
    public function renderBaptisms(Section $section, Collection $baptisms): void
    {
        if (! count($baptisms)) {
            return;
        }
        $this->heading($section, 'Getauft wurden:');

        $run = $this->getListRun($section);
        $lastDate = Carbon::now();
        foreach ($baptisms as $service) {
            foreach ($service->baptisms as $baptism) {
                $this->renderDateColumn($run, $service, $lastDate);
                /** @var string $candidateName */
                $candidateName = $baptism->candidate_name;
                $run->addText(NameService::fromName($candidateName)->format(NameService::FIRST_LAST));
                $run->addTextBreak();
            }
        }
    }

    /**
     * @param  Collection<int, Service>  $weddings  Services with weddings to render.
     */
    public function renderWeddings(Section $section, Collection $weddings): void
    {
        if (! count($weddings)) {
            return;
        }
        $this->heading($section, 'Kirchlich getraut wurden:');

        $run = $this->getListRun($section);
        $lastDate = Carbon::now();
        foreach ($weddings as $service) {
            foreach ($service->weddings as $wedding) {
                $this->renderDateColumn($run, $service, $lastDate);

                $partners = [];
                foreach ([1, 2] as $index) {
                    $nameField = 'spouse'.$index.'_name';
                    /** @var string $spouseName */
                    $spouseName = $wedding->$nameField;
                    $name = NameService::fromName($spouseName);
                    $nameField = 'spouse'.$index.'_birth_name';
                    /** @var string|null $birthName */
                    $birthName = $wedding->$nameField;

                    $partners[] = $name->format(NameService::FIRST_LAST).($birthName ? ', geb. '.$birthName : '');
                }
                $run->addText(implode(' und ', $partners));
                $run->addTextBreak();
            }
        }
    }

    /**
     * @param  Collection<int, Service>  $baptismalServices  Services with baptisms.
     */
    public function renderBaptismDates(Section $section, Collection $baptismalServices): void
    {
        if (! count($baptismalServices)) {
            return;
        }
        $this->heading($section, 'Nächste Tauftermine:');
        $baptismalServices = $baptismalServices->groupBy('city_id');
        $run = $this->getListRun($section);
        /** @var array{includeCities: array<int, int>} $data */
        $data = $this->data;
        foreach ($data['includeCities'] as $cityId) {
            $city = City::findOrFail((int) $cityId);
            $run->addText("\t");
            $run->addText($city->name."\t\t");

            $dates = [];
            foreach ($baptismalServices->get($cityId, collect()) as $baptismalService) {
                /** @var Service $baptismalService */
                $dates[] = $baptismalService->trueDate()->isoFormat('D. MMMM');
            }
            $run->addText(implode(', ', $dates));
            $run->addTextBreak();
        }
    }

    /**
     * @param  Collection<int, Service>  $funerals  Services with funerals to render.
     */
    public function renderFunerals(Section $section, Collection $funerals): void
    {
        if (! count($funerals)) {
            return;
        }
        $this->heading($section, 'Bestattet wurden:');

        $run = $this->getListRun($section);
        $lastDate = Carbon::now();
        foreach ($funerals as $service) {
            foreach ($service->funerals as $funeral) {
                $this->renderDateColumn($run, $service, $lastDate);
                /** @var string $buriedName */
                $buriedName = $funeral->buried_name;
                $name = NameService::fromName($buriedName);
                $run->addText(
                    $name->format(NameService::FIRST_LAST)
                    .($funeral->birthName ? ', geb. '.$funeral->birthName : '')
                    .', '.$funeral->age.' Jahre'
                );
                $run->addTextBreak();
            }
        }
    }
}
