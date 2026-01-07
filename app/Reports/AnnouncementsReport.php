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

use App\Imports\EventCalendarImport;
use App\Imports\OPEventsImport;
use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\ReadingItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Announcements;
use App\Models\Calendar\Occurence;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\NameService;
use App\Tools\StringTool;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Class AnnouncementsReport
 * @package App\Reports
 */
class AnnouncementsReport extends AbstractWordDocumentReport
{
    /**
     *
     */
    protected const BOLD = ['bold' => true];
    /**
     *
     */
    protected const UNDERLINE = ['underline' => Font::UNDERLINE_SINGLE];
    /**
     *
     */
    protected const BOLD_UNDERLINE = ['bold' => true, 'underline' => Font::UNDERLINE_SINGLE];
    /**
     *
     */
    protected const INDENT = 'Bekanntgaben';
    /**
     *
     */
    protected const NO_INDENT = 'Bekanntgaben ohne Einrückung';

    public const FILE_TITLE = 'Bekanntgaben';
    public const FILE_SIGNATURE = '91.8';
    protected $config = [
        'layout' => [
            'orientation' => 'landscape',
            'marginTop' => 566.92913385827, // 1 cm
            'marginBottom' => 566.92913385827,
            'marginLeft' => 566.92913385827,
            'marginRight' => 566.92913385827,
            'pageSizeH' => 11906,
            'pageSizeW' => 8419,
        ],
        'styles' => [
            'paragraphs' => [
                'default' => [
                    'spaceAfter' => 0,
                ],
                'custom' => [
                    'Bekanntgaben' => [
                        'alignment' => 'start',
                        'indentation' => [
                            'left' => 1440, // 1.27cm
                            'right' => 0,
                            'firstLine' => 0,
                            'hanging' => 1440, // 1.27cm
                        ],
                        'spaceBefore' => 0,
                        'spaceAfter' => 0, // 8pt
                    ],
                ],
            ],
            'fonts' => [
                'titles' => [
                    1 => [
                        'size' => 11,
                        'underline' => 'single',
                    ],
                ],
            ],
        ],
    ];

    protected $useDefaultDocument = true;

    /**
     * @var string
     */
    public $title = 'Bekanntgaben';
    /**
     * @var string
     */
    public $group = 'Veröffentlichungen';
    /**
     * @var string
     */
    public $description = 'Bekanntgaben für einen Gottesdienst';

    /** @var Section */
    protected $section;

    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities->load('parent');
        return Inertia::render('Report/Announcements/Setup', compact('cities'));
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function services(Request $request)
    {
        $data = $request->validate(['city' => 'required|int']);
        $city = City::findOrFail($data['city']);
        $serviceList = Service::with(['location'])
            ->regularForCity($city)
            ->displayable()
            ->startingFrom(Carbon::now()->subHours(8))
            ->ordered()
            ->get();

        $services = [];
        foreach ($serviceList as $service) {
            $services[] = [
                'id' => $service->id,
                'name' => $service->date->format('d.m.Y') . ' ' . $service->timeText() . ', ' . $service->locationText
            ];
        }

        return response()->json([
                                    'services' => $services,
                                ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lastServiceDays(Request $request)
    {
        $data = $request->validate([
                                       'city' => 'required|int|exists:cities,id',
                                       'service' => 'required|int|exists:services,id'
                                   ]);

        $city = City::findOrFail($data['city']);
        $service = Service::findOrFail($data['service']);

        $days = Service::select(DB::raw('DISTINCT DATE(date) AS day'))
            ->endingAt($service->date->copy()->subDays(1))
            ->regularForCity($city)
            ->orderBy('day', 'DESC')
            ->limit(10)
            ->get()
            ->pluck('day');

        $lastServiceDays = [];
        foreach ($days as $day) {
            $lastServiceDays[] = ['id' => $day, 'name' => Carbon::parse($day)->isoFormat('dddd, DD. MMMM YYYY')];
        }

        return response()->json($lastServiceDays);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function offerings(Request $request)
    {
        $data = $request->validate([
                                       'city' => 'required|int|exists:cities,id',
                                       'day' => 'required|date'
                                   ]);
        $services = Service::whereDate('date', Carbon::parse($data['day']))
            ->where('city_id', $data['city'])
            ->get();

        $amount = 0;
        foreach ($services as $service) {
            $amount += (float)strtr($service->offering_amount, [' ' => '', '€' => '']);
        }

        return response()->json($amount);
    }


    public function auto(Request $request)
    {
        if (!$request->has('service')) {
            abort(404);
        }
        $service = Service::findOrFail($request->get('service'));
        $lastService = Service::where('offering_amount', '!=', '')
            ->endingAt($service->dateTime)
            ->orderedDesc()
            ->first();
        $this->renderReport([
                                'lastService' => $lastService->date->format('d.m.Y'),
                                'offerings' => $lastService->offering_amount,
                                'offering_text' => $service->offering_text,
                                'service' => $service,
                                'excludeRegularWeekly' => true,
                            ]);
    }

    public function renderReport($data)
    {
        $service = Service::findOrFail($data['service']);
        $city = City::findOrFail($data['city']);

        // is this part of an org?
        $localCity = $city;
        if ($city->parent_id) {
            $city = $city->parent;
        }

        $lastService = $data['lastService'];
        $offerings = $data['offerings'];

        $announcements = new Announcements($service, $city, $data['excludeRegularWeekly'] ?? false);
        $baptisms = $announcements->getBaptisms();
        $funerals = $announcements->getFunerals();
        $weddings = $announcements->getWeddings();
        $events = $announcements->getEvents();
        $featuredEvents = $announcements->getFeaturedEvents();

        ////////////////////////////////////////////////////////////////////////////////////////////////

        $this->doc->getPhpWord()->getSettings()->setBookFoldPrinting(true);

        $textRun = $this->doc->getSection()->addTextRun('Bekanntgaben');
        $textRun->addText(
            $service->date->isoFormat('DD. MMMM YYYY')
            . (($service->liturgicalInfo['title'] ?? false) ? ' - ' . $service->liturgicalInfo['title'] : ''),
            ['bold' => true]
        );

        $textRun = $this->doc->getSection()->addTextRun('Bekanntgaben');
        $textRun->addText($service->timeText() . ' ' . $service->locationText());

        $this->doc->getSection()->addTextBreak();

        foreach (
            [
                'Liturgie' => $service->pastors,
                'Orgel' => $service->organists,
                'Mesnerdienst' => $service->sacristans,
            ] as $ministry => $people
        ) {
            $this->renderMinistryLine($ministry, $people);
        }
        foreach ($service->ministries() as $ministry => $people) {
            $this->renderMinistryLine($ministry, $people);
        }

        if ($service->offering_goal) {
            $this->doc->renderParagraph(self::INDENT, [
                ["Opfer:\t{$service->offering_goal}", []]
            ]);
        }

        $this->renderLiturgy($service);

        $this->renderReadings($service);

        $this->doc->renderParagraph(self::NO_INDENT, [
            ['Abkündigungen', self::BOLD_UNDERLINE],
        ]);
        $this->doc->getSection()->addTextBreak();

        // ANNOUNCEMENTS

        $announcements->setUseTabs(true);
        //dd($announcements->render($lastService, $offerings, null, "\n", ['last_offerings', 'final_song']));
        $announcements->render($lastService, $offerings, function ($key, $items) {
            if ($key == 'events') {
                foreach ($items as $item) {
                    if (Str::contains($item, "\t")) {
                        $this->doc->renderParagraph(self::INDENT, [[ $item, [] ]]);
                    } else {
                        $this->doc->renderParagraph(self::NO_INDENT, [[ $item, ['bold' => true] ]]);
                    }
                }
            } else {
                $this->renderParagraphArray($items, self::NO_INDENT);
            }
            $this->doc->getSection()->addTextBreak();
        });

        if (!empty($service->konfiapp_event_qr)) {
            $this->renderKonfiAppQR($service);
        }


        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE,
                '.docx',
                static::FILE_SIGNATURE,
                $service->date
            )
        );
    }

    /**
     * @param Request $request
     * @return string|void
     * @throws Exception
     */
    public function render(Request $request)
    {
        return $this->renderReport(
            $request->validate(
                [
                    'city' => 'required|int|exists:cities,id',
                    'service' => 'required|int|exists:services,id',
                    'offerings' => 'required|string',
                    'lastService' => 'required|date',
                    'offering_text' => 'nullable|string',
                    'excludeRegularWeekly' => 'nullable|bool',
                ]
            )
        );
    }

    /**
     * @param $s
     * @return string
     */
    protected function renderName($s)
    {
        if (false !== strpos($s, ',')) {
            $t = explode(',', $s);
            $s = trim($t[1]) . ' ' . trim($t[0]);
        }
        return $s;
    }

    protected function getNameListLine($people, $and = ', ')
    {
        $names = collect();
        foreach ($people as $person) {
            $names->push(NameService::fromUser($person)->format(NameService::TITLE_FIRST_LAST));
        }
        return $names->join(', ', $and);
    }

    protected function renderMinistryLine($ministry, $people)
    {
        $this->doc->renderParagraph(self::INDENT, [
            [$ministry . ":\t" . $this->getNameListLine($people), []]
        ]);
    }

    protected function renderLiturgy(Service $service)
    {
        if (!count($service->liturgyBlocks)) {
            return;
        }
        $this->doc->getSection()->addTextBreak(1);
        foreach ($service->liturgyBlocks as $block) {
            $this->doc->renderParagraph(self::NO_INDENT, [
                [$block->title, ['bold' => true]],
            ]);
            foreach ($block->items as $item) {
                $title = '';
                if ($item->data_type == 'song') {
                    $helper = new SongItemHelper($item);
                    $title = ': ' . $helper->getTitleText(
                        ) . (($item->data['verses'] ?? '') ? ', ' . $item->data['verses'] : '');
                }
                if ($item->data_type == 'psalm') {
                    $helper = new PsalmItemHelper($item);
                    $title = ': ' . $helper->getTitleText();
                }
                if ($item->data_type == 'reading') {
                    $title = ': ' . $item->data['reference'] ?? '';
                }

                $this->doc->renderParagraph(self::NO_INDENT, [
                    [Str::replace('&', '&amp;', $item->title . trim($title)), []]
                ]);
            }
        }
    }

    protected function renderReadings(Service $service)
    {
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($item->data_type == 'reading') {
                    $title = 'Schriftlesung aus ' . $item->data['reference'];

                    /** @var ReadingItemHelper $helper */
                    $helper = $item->getHelper();
                    $helper->renderToWordDocument($this->doc, true, true, $title, 1);

                    $this->doc->renderParagraph(self::NO_INDENT, [], 1);
                    $this->doc->renderParagraph(self::NO_INDENT, [
                        ['Der Herr segne sein Wort an uns. Amen.', ['italic' => true]],
                    ],                          1);
                }
            }
        }
    }

    protected function renderParagraphArray($paragraphs, $pStyle = self::NO_INDENT)
    {
        foreach ($paragraphs as $paragraph) {
            if ($paragraph == "") {
                $this->doc->getSection()->addTextBreak();
            } else {
                $this->doc->renderParagraph($pStyle, [[$paragraph, [], true]]);
            }
        }
    }



    public function renderKonfiAppQR(Service $service)
    {
        $this->doc->getSection()->addTextBreak(1);
        $this->doc->getSection()->addTitle('QR-Code für die KonfiApp', 1);
        $types = KonfiAppIntegration::get($service->city)->listEventTypes();
        $text = '';
        foreach ($types as $type) {
            if ($type->id == $service->konfiapp_event_type) {
                $text = $type->punktzahl . ' ' . ($type->punktzahl == 1 ? 'Punkt' : 'Punkte') . ' in der Kategorie "' . $type->name . '". ';
            }
        }
        $this->doc->renderNormalText(
            $text . 'Gültig nur am ' . $service->date->isoFormat('dddd, DD. MMMM YYYY')
            . ' von ' . $service->date->setTimezone('Europe/Berlin')->format('H:i')
            . ' bis ' . $service->date->setTimezone('Europe/Berlin')->copy()->addHours(3)->format('H:i') . ' Uhr.',
            ['size' => 8],
            true
        );
        $this->doc->getSection()->addImage(
            route('qrcode', $service->konfiapp_event_qr),
            ['width' => Converter::cmToPoint(4)]
        );
    }

}
