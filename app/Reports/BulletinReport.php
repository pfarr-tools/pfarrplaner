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

use App\Models\Places\City;
use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Tab;


/**
 * Class BulletinReport
 * @package App\Reports
 */
class BulletinReport extends AbstractWordDocumentReport
{
    public const FILE_SIGNATURE = '91.8';
    public const FILE_TITLE = 'Gottesdienstliste Gemeindebrief';

    /**
     * @var string
     */
    public $title = 'Gemeindebrief';
    /**
     * @var string
     */
    public $group = 'Listen';
    /**
     * @var string
     */
    public $description = 'Gottesdienstliste für den Gemeindebrief';

    /**
     * @var string[]
     */
    public $formats = ['Tailfingen', 'Truchtelfingen', 'Gäufelden'];

    public $cities = [];


    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
        $formats = $this->formats;
        return Inertia::render('Report/Bulletin/Setup', compact('cities', 'formats'));
    }


    /**
     * @param Request $request
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
                'format' => 'nullable',
            ]
        );


        $cities = City::whereIn('id', $data['includeCities'])->get();

        $start = Carbon::parse($data['start']);
        $serviceList = Service::withGlobalScope('servicesOnly', ServicesOnlyScope::class)
            ->between($start, Carbon::parse($data['end']))
            ->displayable($start)
            ->inCities($cities)
            ->ordered()
            ->get()
            ->groupBy('key_date');

        $this->cities = $data['includeCities'];


        $format = ucfirst(Str::slug($data['format'] ?? $this->formats[0]));

        $renderMethod = "render{$format}Format";
        if (method_exists($this, $renderMethod)) {
            return $this->$renderMethod($serviceList);
        } else {
            return redirect()->route('reports.setup', $this->getKey());
        }
    }

    /**
     * @param $days
     * @param $serviceList
     */
    public function renderTailfingenFormat($serviceList)
    {
        $section = $this->commonDocumentSetup();
        $table = $section->addTable('table');

        foreach ($serviceList as $dayList) {
            $ctr = 0;
            foreach ($dayList as $service) {
                $ctr++;
                $textRun = $section->addTextRun('list');
                if ($ctr == 1) {
                    $textRun->addText($service->date->format('d.m.Y'));
                }
                if ($ctr == 2) {
                    $textRun->addText(htmlspecialchars(LiturgyService::getDayInfo($service->date)['title'] ?? ''));
                }
                $textRun->addText("\t");
                $textRun->addText($service->timeText() . "\t");
                if (!is_object($service->location)) {
                    $textRun->addText(htmlspecialchars($service->special_location) . "\t");
                } else {
                    $textRun->addText(htmlspecialchars($service->location->name) . "\t");
                }
                $textRun->addText(htmlspecialchars($service->participantsText('P', false, false)));
                if ($x = $service->titleAndDescriptionCombinedText()) {
                    $textRun->addText(' - ' . htmlspecialchars($x));
                }
            }
            $textRun = $section->addTextRun('list');
        }

        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE,
                '.docx',
                static::FILE_SIGNATURE,
                $service->date)
        );
    }

    /**
     * @return Section
     */
    public function commonDocumentSetup()
    {
        $this->wordDocument->addParagraphStyle(
            'list',
            [
                'tabs' => [
                    new Tab('left', Converter::cmToTwip(4.5)),
                    new Tab('left', Converter::cmToTwip(6.7)),
                    new Tab('left', Converter::cmToTwip(9)),
                ],
                'spaceAfter' => 0,
            ]
        );
        $this->wordDocument->setDefaultFontName('Sarabun Light');
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

    /**
     * @param $days
     * @param $serviceList
     */
    public function renderTruchtelfingenFormat($serviceList)
    {
        $section = $this->commonDocumentSetup();
        $table = $section->addTable('table');

        foreach ($serviceList as $day => $dayList) {
            $liturgy = LiturgyService::getDayInfo($day);
            /** @var Service $service */
            $first = true;
            foreach ($dayList as $service) {
                $table->addRow();
                $table->addCell(Converter::cmToTwip(2.5))->addText($first ? ($liturgy['title'] ?? '') : '');
                $table->addCell(Converter::cmToTwip(1.73))->addText($first ? $service->date->format('d.m.Y') : '');
                $table->addCell(Converter::cmToTwip(1.58))->addText($service->timeText());
                $table->addCell(Converter::cmToTwip(2.11))->addText($service->locationText());
                $table->addCell(Converter::cmToTwip(2.32))->addText($service->descriptionText());
                $table->addCell(Converter::cmToTwip(3.52))->addText($service->participantsText('P', false, false));
                $table->addCell(Converter::cmToTwip(2.25))->addText(
                    isset($liturgy['perikope']) ? $liturgy['litTextsPerikope' . $liturgy['perikope']] : ''
                );
                $table->addCell(Converter::cmToTwip(2.5))->addText(
                    $service->offering_goal ? 'Opfer für ' . $service->offering_goal : ''
                );
                $first = false;
            }
        }

        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE,
                '.docx',
                static::FILE_SIGNATURE,
                Carbon::now())
        );

    }


    public function renderGaufeldenFormat($serviceList)
    {
        $this->wordDocument->setDefaultFontSize(10);
        $this->wordDocument->setDefaultFontName('Lora');
        $section = $this->commonDocumentSetup();
        Carbon::setLocale(config('app.locale'));

        /*
        $list = [];
        foreach ($serviceList as $date => $services) {
            foreach ($services as $service) {
                $list[substr($date, 0, 7)][$service->city_id][$date] = $services;
            }
        }
        */

        foreach ($serviceList as $date => $services) {
            $index = 0;

            $run = $section->addTextRun(['spaceAfter' => 0]);
            $dayDate = Carbon::parse($date)->startOfDay();
            if ($index == 0) {
                $liturgy = LiturgyService::getLiturgyInfoByDate($dayDate);

                $run->addText(
                    substr($dayDate->getTranslatedDayName(), 0, 2)
                    . '., '
                    . $dayDate->format('d. ')
                    . $dayDate->getTranslatedMonthName(),
                    ['name' => 'Lora', 'bold' => true, 'size' => 10]
                );
                if (count($liturgy)) {
                    $run->addText(' | '.$liturgy[0]->title, ['name' => 'Lora', 'bold' => true, 'size' => 8]);
                }
            }


            $index = 0;
            foreach ($services as $service) {
                $run = $section->addTextRun(            [
                                                            'tabs' => [
                                                                new Tab('left', Converter::cmToTwip(2)),
                                                                new Tab('left', Converter::cmToTwip(4.5)),
                                                                new Tab('left', Converter::cmToTwip(8.5)),
                                                            ],
                                                            'indentation' => [
                                                                'left' => Converter::cmToTwip(8.5),
                                                                'hanging' => Converter::cmToTwip(8.5),
                                                            ],
                                                            'spaceAfter' => 0,
                                                        ]
                );
                $run->addText($service->timeText()."\t", ['name' => 'Lora']);

                $location = $service->locationText();
                //if (!Str::contains($location, $service->city->name)) $location .= ' '.$service->city->name;
                $run->addText($service->city->name."\t", ['name' => 'Lora', 'bold' => true, 'size' => 10, 'color' => 'a1a1a1']);
                $run->addText($location."\t", ['name' => 'Lora', 'bold' => false, 'size' => 10]);

                if ($service->description && (substr($service->description,0,1)=='"')) {
                    $run->addText($service->description."\t", ['name' => 'Lora']);
                }

                $run->addText(
                    $service->titleText(false) . ' mit '
                    . $service->participantsText('P', true),
                    ['name' => 'Lora']
                );

            }

            $run->addText('<w:br/>');

        }

        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE,
                '.docx',
                static::FILE_SIGNATURE,
                $service->date)
        );
    }

}
