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

use App\Models\Liturgy\Text;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\LiturgyService;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Tab;


/**
 * Class BulletinReport
 * @package App\Reports
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

    protected $data = [];


    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
        return Inertia::render('Report/Rites/Setup', compact('cities'));
    }


    /**
     * @param Request $request
     * @return RedirectResponse|string
     */
    public function render(Request $request)
    {
        $this->data = $request->validate(
            [
                'includeCities' => 'required',
                'includeCities.*' => 'int|exists:cities,id',
                'start' => 'required|date',
                'end' => 'required|date',
                'baptismDatesStart' => 'required|date',
                'baptismDatesEnd' => 'required|date',
            ]
        );

        $section = $this->commonDocumentSetup();
        $start = Carbon::parse($this->data['start'])->startOfDay();
        $end = Carbon::parse($this->data['end'])->endOfDay();
        $baptismDatesStart = Carbon::parse($this->data['baptismDatesStart'])->startOfDay();
        $baptismDatesEnd = Carbon::parse($this->data['baptismDatesEnd'])->endOfDay();

        $this->renderBaptisms(
            $section,
            Service::with('baptisms')
                ->whereHas('baptisms')
                ->whereIn('city_id', $this->data['includeCities'])
                ->between($start, $end)
                ->ordered()
                ->get()
        );

        $this->renderBaptismDates(
            $section,
            Service::where('baptism', 1)
                ->whereIn('city_id', $this->data['includeCities'])
                ->between($baptismDatesStart, $baptismDatesEnd)
                ->ordered()
                ->get()
        );

        $this->renderWeddings(
            $section,
            Service::with('weddings')
                ->whereHas('weddings')
                ->whereIn('city_id', $this->data['includeCities'])
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
                ->whereIn('city_id', $this->data['includeCities'])
                ->between($start, $end)
                ->ordered()
                ->get()
        );

        $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE,
                null,
                static::FILE_SIGNATURE,
                [$start, $end]
            )

        );
        return;
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
                'marginTop' => Converter::cmToTwip('1.9'),
                'marginBottom' => Converter::cmToTwip('0.25'),
                'marginLeft' => Converter::cmToTwip('1.59'),
                'marginRight' => Converter::cmToTwip('0.25'),
            ]
        );
        return $section;
    }


    public function heading(Section $section, $title): void
    {
        $run = $section->addTextRun();
        $run->addText($title, ['name' => 'Yanone Kaffeesatz', 'size' => 20, 'bold' => true]);
    }

    public function getListRun(Section $section): TextRun
    {
        return $section->addTextRun([
                                        'tabs' => [
                                            new Tab('right', Converter::cmToTwip(6)),
                                            new Tab('left', Converter::cmToTwip(6.7)),
                                            new Tab('left', Converter::cmToTwip(7)),
                                        ],
                                    ]);
    }

    public function renderDateColumn(TextRun $run, $service, &$lastDate): void
    {
        $run->addText("\t");
        if ($service->date->format('Ymd') != $lastDate->format('Ymd')) {
            $run->addText($service->date->isoFormat('LL'));
        }
        $lastDate = $service->date;
        $run->addText("\t");
        if (count($this->data['includeCities']) > 1) {
            $run->addText('(' . substr(utf8_decode($service->city->name), 0, 1) . ')');
        }
        $run->addText("\t");
    }

    public function renderBaptisms(Section $section, Collection $baptisms): void
    {
        if (!count($baptisms)) {
            return;
        }
        $this->heading($section, 'Getauft wurden:');

        $run = $this->getListRun($section);
        $lastDate = Carbon::now();
        foreach ($baptisms as $service) {
            foreach ($service->baptisms as $baptism) {
                $this->renderDateColumn($run, $service, $lastDate);
                $run->addText(NameService::fromName($baptism->candidate_name)->format(NameService::FIRST_LAST));
                $run->addTextBreak();
            }
        }
    }

    public function renderWeddings(Section $section, Collection $weddings): void
    {
        if (!count($weddings)) {
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
                    $nameField = 'spouse' . $index . '_name';
                    $name = NameService::fromName($wedding->$nameField);
                    $nameField = 'spouse' . $index . '_birth_name';
                    $birthName = $wedding->$nameField;

                    $partners[] = $name->format(NameService::FIRST_LAST) . ($birthName ? ', geb. ' . $birthName : '');
                }
                $run->addText(join(' und ', $partners));
                $run->addTextBreak();
            }
        }
    }

    public function renderBaptismDates(Section $section, Collection $baptismalServices): void
    {
        if (!count($baptismalServices)) {
            return;
        }
        $this->heading($section, 'Nächste Tauftermine:');
        $baptismalServices = $baptismalServices->groupBy('city_id');
        $run = $this->getListRun($section);
        foreach ($this->data['includeCities'] as $cityId) {
            $city = City::find($cityId);
            $run->addText("\t");
            $run->addText($city->name . "\t\t");

            $dates = [];
            foreach ($baptismalServices[$cityId] as $baptismalService) {
                /** @var Service $baptismalService */
                $dates[] = $baptismalService->date->isoFormat('D. MMMM');
            }
            $run->addText(join(', ', $dates));
            $run->addTextBreak();
        }
    }


    public function renderFunerals(Section $section, Collection $funerals): void
    {
        if (!count($funerals)) {
            return;
        }
        $this->heading($section, 'Bestattet wurden:');

        $run = $this->getListRun($section);
        $lastDate = Carbon::now();
        foreach ($funerals as $service) {
            foreach ($service->funerals as $funeral) {
                $this->renderDateColumn($run, $service, $lastDate);
                $name = NameService::fromName($funeral->buried_name);
                $run->addText(
                    $name->format(NameService::FIRST_LAST)
                    . ($funeral->birthName ? ', geb. ' . $funeral->birthName : '')
                    . ', ' . $funeral->age . ' Jahre'
                );
                $run->addTextBreak();
            }
        }
    }

}
