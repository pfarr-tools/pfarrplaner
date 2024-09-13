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
use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Calendar\Occurence;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
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
        $cities = Auth::user()->cities;
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
            ->notHidden()
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
                                    'mixOutlook' => (bool)$city->public_events_calendar_url,
                                    'mixOP' => (bool)$city->op_customer_token,
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
            $lastServiceDays[] = ['id' => $day, 'name' => Carbon::parse($day)->formatLocalized('%A, %d. %B %Y')];
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
                            ]);
    }

    public function renderReport($data)
    {
        $service = Service::findOrFail($data['service']);
        $city = City::findOrFail($data['city']);

        $lastService = $data['lastService'];
        $offerings = $data['offerings'];

        $lastWeek = Carbon::createFromTimeString($service->date->format('Y-m-d') . ' 0:00:00 last Sunday');
        $nextWeek = $lastWeek->copy()->addWeeks(2)->setTime(
            23,
            59,
            59
        );

        $funerals = Funeral::where('announcement', $service->date->format('Y-m-d'))
            ->whereHas(
                'service',
                function ($query) use ($service) {
                    $query->inCity($service->city);
                }
            )
            ->get();

        $weddings = Wedding::with('service')
            ->whereHas(
                'service',
                function ($query) use ($service, $nextWeek) {
                    $query->between($service->date, $nextWeek)
                        ->inCity($service->city)
                        ->notHidden()
                        ->ordered();
                }
            )->get();

        $baptisms = Baptism::with('service')
            ->whereHas(
                'service',
                function ($query) use ($service, $nextWeek) {
                    $query->between($service->date, $nextWeek)
                        ->inCity($service->city)
                        ->notHidden()
                        ->ordered();
                }
            )->get();

        $liturgicalInfo = $service->liturgicalInfo;

        $events = Occurence::with('event')
            ->between($service->date, $nextWeek)
            ->whereHas('service', function($query) use ($service) {
                $query->inCity($service->city)->notHidden();
            })
            ->orderBy('start')
            ->get();
        //dd($events);

        ////////////////////////////////////////////////////////////////////////////////////////////////


        $this->section = $this->wordDocument->addSection(
            [
                'orientation' => \PhpOffice\PhpWord\Style\Section::ORIENTATION_LANDSCAPE,
                'pageSizeH' => 11906,
                'pageSizeW' => 8419,
                'marginTop' => Converter::cmToTwip(1),
                'marginBottom' => Converter::cmToTwip(1),
                'marginLeft' => Converter::cmToTwip(1),
                'marginRight' => Converter::cmToTwip(1),
            ]
        );
        $this->wordDocument->getSettings()->setBookFoldPrinting(true);
        $this->wordDocument->setDefaultFontSize(12);

        $this->wordDocument->addParagraphStyle(
            self::INDENT,
            array(
                'indentation' => [
                    'left' => Converter::cmToTwip(3),
                    'hanging' => Converter::cmToTwip(3),
                ],
                'tabs' => [
                    new Tab('left', Converter::cmToTwip(3)),
                ],
                'spaceAfter' => 0,
            )
        );

        $this->wordDocument->addParagraphStyle(
            self::NO_INDENT,
            array(
                'tabs' => [
                    new Tab('left', Converter::cmToTwip(3)),
                ],
                'spaceAfter' => 0,
            )
        );

        $textRun = $this->section->addTextRun('Bekanntgaben');
        $textRun->addText(
            $service->date->formatLocalized('%d. %B %Y')
            . (($service->liturgicalInfo['title'] ?? false) ? ' - ' . $service->liturgicalInfo['title'] : ''),
            ['bold' => true]
        );

        $textRun = $this->section->addTextRun('Bekanntgaben');
        $textRun->addText($service->timeText() . ' ' . $service->locationText());

        $this->section->addTextBreak();

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
            $this->renderParagraph(self::INDENT, [
                ["Opfer:\t{$service->offering_goal}", []]
            ]);
        }

        $this->renderLiturgy($service);

        $this->renderReadings($service);

        $this->renderParagraph(self::NO_INDENT, [
            ['Abkündigungen', self::BOLD_UNDERLINE],
        ]);
        $this->section->addTextBreak();

        $this->renderThanks($service);

        $this->renderOfferings($service, $lastService, $offerings);

        $this->renderEvents($events);

        $textRun = $this->renderParagraph();

        // Baptisms
        if (count($baptisms)) {
            $this->renderParagraph(self::NO_INDENT, [['Taufen', self::BOLD_UNDERLINE]]);

            $baptismArray = [];
            foreach ($baptisms as $baptism) {
                $baptismArray[$baptism->service->trueDate()->format('YmdHis')][] = $baptism;
            }
            ksort($baptismArray);

            foreach ($baptismArray as $baptisms) {
                $baptism = $baptisms[array_key_first($baptisms)];
                if ($baptism->service->id != $service->id) {
                    $textRun = $this->renderParagraph();
                    if ($baptism->service->trueDate() == $service->trueDate()) {
                        $this->renderParagraph(
                            self::NO_INDENT,
                            [
                                [
                                    'Im Gottesdienst heute ' . $baptism->service->atText() . ' ' . (count(
                                        $baptisms
                                    ) > 1 ? 'werden' : 'wird') . ' getauft:',
                                    []
                                ]
                            ]
                        );
                    } else {
                        $this->renderParagraph(
                            self::NO_INDENT,
                            [
                                [
                                    'Im Gottesdienst am ' . $baptism->service->date->format(
                                        'd.m.Y'
                                    ) . ' ' . $baptism->service->atText() . ' ' . (count(
                                        $baptisms
                                    ) > 1 ? 'werden' : 'wird') . ' getauft:',
                                    []
                                ]
                            ]
                        );
                    }
                    foreach ($baptisms as $baptism) {
                        $this->renderParagraph(
                            self::NO_INDENT,
                            [
                                [$this->renderName($baptism->candidate_name) . ', ' . $baptism->candidate_address, []]
                            ]
                        );
                    }
                }
            }
            $this->renderParagraph();
            $this->renderLiteral(
                '*Christus hat der Kirche den Auftrag gegeben:
Gehet hin und machet zu Jüngern alle Völker
und taufet sie auf den Namen des Vaters und
des Sohnes und des Heiligen Geistes.'
            );
        }


        if (count($weddings)) {
            $this->renderParagraph(self::NO_INDENT, [['Trauungen', self::BOLD_UNDERLINE]]);

            $weddingArray = [];
            foreach ($weddings as $wedding) {
                $weddingArray[$wedding->service->trueDate()->format('YmdHis')][] = $wedding;
            }
            ksort($weddingArray);

            foreach ($weddingArray as $weddings) {
                $wedding = $weddings[array_key_first($weddings)];
                if ($wedding->service->id != $service->id) {
                    $textRun = $this->renderParagraph();
                    if ($wedding->service->trueDate() == $service->trueDate()) {
                        $this->renderParagraph(
                            self::NO_INDENT,
                            [
                                [
                                    'Im Gottesdienst heute ' . $wedding->service->atText(
                                    ) . ' werden kirchlich getraut:',
                                    []
                                ]
                            ]
                        );
                    } else {
                        $this->renderParagraph(
                            self::NO_INDENT,
                            [
                                [
                                    'Im Gottesdienst am ' . $wedding->service->date->format(
                                        'd.m.Y'
                                    ) . ' ' . $wedding->service->atText() . ' werden kirchlich getraut:',
                                    []
                                ]
                            ]
                        );
                    }
                    foreach ($weddings as $wedding) {
                        $this->renderParagraph(
                            self::NO_INDENT,
                            [
                                [
                                    $this->renderName($wedding->spouse1_name) . ' &amp; ' . $this->renderName(
                                        $wedding->spouse2_name
                                    ),
                                    []
                                ]
                            ]
                        );
                    }
                }
            }
            $this->renderParagraph();
            $textRun = $this->renderLiteral(
                '*Vater im Himmel,
wir bitten für dieses Hochzeitspaar.
Begleite sie auf ihrem gemeinsamen Weg.
Lass sie deine Liebe erfahren
und stärke ihre Liebe zueinander
in guten und in schweren Tagen.'
            );
        }

        if (count($funerals)) {
            $this->renderParagraph(self::NO_INDENT, [['Bestattungen', self::BOLD_UNDERLINE]]);

            $funeralArray = ['past' => [], 'future' => []];
            foreach ($funerals as $funeral) {
                $key = ($funeral->service->trueDate() < $service->trueDate()) ? 'past' : 'future';
                $funeralArray[$key][] = $funeral;
            }

            if (count($funeralArray['past'])) {
                ksort($funeralArray['past']);
                $this->renderParagraph(
                    self::NO_INDENT,
                    [
                        [
                            'Aus unserer Gemeinde ' . StringTool::pluralString(
                                count($funeralArray['past']),
                                'ist',
                                'sind'
                            ) . ' verstorben und '
                            . StringTool::pluralString(
                                count($funeralArray['past']),
                                'wurde',
                                'wurden'
                            ) . ' kirchlich bestattet:',
                            []
                        ]
                    ]
                );
                foreach ($funeralArray['past'] as $funeral) {
                    $this->renderParagraph(
                        self::NO_INDENT,
                        [
                            [
                                $this->renderName($funeral->buried_name) . ', ' . $funeral->buried_address
                                . ($funeral->age() ? ', ' . $funeral->age() . ' Jahre' : '') . '.',
                                []
                            ]
                        ]
                    );
                }
                if (count($funeralArray['future'])) {
                    $this->renderParagraph();
                }
            }

            if (count($funeralArray['future'])) {
                ksort($funeralArray['future']);
                $this->renderParagraph(
                    self::NO_INDENT,
                    [
                        [
                            'Aus unserer Gemeinde ' . StringTool::pluralString(
                                count($funeralArray['future']),
                                'ist',
                                'sind'
                            ) . ' verstorben:',
                            []
                        ]
                    ]
                );
                foreach ($funeralArray['future'] as $funeral) {
                    $mode = $funeral->type;
                    if ($mode == 'Erdbestattung') {
                        $mode = 'Bestattung';
                    }
                    $this->renderParagraph(
                        self::NO_INDENT,
                        [
                            [
                                $this->renderName($funeral->buried_name) . ', '
                                . $funeral->buried_address
                                . ($funeral->age() ? ', ' . $funeral->age() . ' Jahre' : '')
                                . '. Die ' . $mode . ' findet am ' . $funeral->service->date->formatLocalized(
                                    '%A, %d. %B'
                                )
                                . ' um ' . $funeral->service->timeText(true, '.')
                                . ' ' . $funeral->service->atText() . ' statt.',
                                []
                            ]
                        ]
                    );
                }
            }


            $this->renderParagraph();
            $textRun = $this->renderLiteral(
                'Wir nehmen teil an der Trauer der Angehörigen und befehlen die Toten, die Trauernden und uns der Güte Gottes an.'
            );
            $textRun = $this->renderLiteral('Unser keiner lebt sich selber, und keiner stirbt sich selber.');
            $textRun = $this->renderLiteral(
                'Leben wir, so leben wir dem Herrn;
sterben wir, so sterben wir dem Herrn.
Darum: Wir leben oder sterben, so sind wir des Herrn.'
            );
            $textRun = $this->renderLiteral(
                '*Denn dazu ist Christus gestorben und wieder lebendig geworden, dass er über Tote und Lebende Herr sei.
Amen.'
            );
        }

        if ($service->announcements) {
            $this->renderParagraph();
            $textRun = $this->renderLiteral($service->announcements);
        }

        $this->renderFinalSong($service);


        $filename = $service->date->format('Y_m_d') . ' Bekanntgaben';
        $this->sendToBrowser($filename);
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
                    'mix_op' => 'nullable|bool',
                    'mix_outlook' => 'nullable|bool',
                    'offering_text' => 'nullable|string',
                ]
            )
        );
    }

    /**
     * @param string $template
     * @param array $blocks
     * @param int $emptyParagraphsAfter
     * @param null $existingTextRun
     * @return TextRun|null
     */
    protected function renderParagraph(
        $template = '',
        array $blocks = [],
        $emptyParagraphsAfter = 0,
        $existingTextRun = null
    ) {
        $textRun = $existingTextRun ?: $this->section->addTextRun($template);
        foreach ($blocks as $block) {
            $textRun->addText($block[0], $block[1] ?? []);
        }
        for ($i = 0; $i < $emptyParagraphsAfter; $i++) {
            $textRun = $this->section->addTextRun($template);
        }
        return $textRun;
    }

    /**
     * @param $text
     */
    protected function renderLiteral($text)
    {
        if (!is_array($text)) {
            $text = [$text];
        }
        foreach ($text as $paragraph) {
            switch (substr($paragraph, 0, 1)) {
                case '*':
                    $format = self::BOLD;
                    $paragraph = substr($paragraph, 1);
                    break;
                case '_':
                    $format = self::UNDERLINE;
                    $paragraph = substr($paragraph, 1);
                    break;
                default:
                    $format = [];
            }
            $paragraph = trim(
                strtr(
                    $paragraph,
                    [
                        "\r" => '',
                        "\n" => '<w:br />'
                    ]
                )
            );
            $this->renderParagraph(self::NO_INDENT, [[$paragraph, $format]], 1);
        }
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
        $this->renderParagraph(self::INDENT, [
            [$ministry . ":\t" . $this->getNameListLine($people), []]
        ]);
    }

    protected function renderLiturgy(Service $service)
    {
        if (!count($service->liturgyBlocks)) {
            return;
        }
        $this->section->addTextBreak(1);
        foreach ($service->liturgyBlocks as $block) {
            $this->renderParagraph(self::NO_INDENT, [
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

                $this->renderParagraph(self::NO_INDENT, [
                    [$item->title . trim($title), []]
                ]);
            }
        }
    }

    protected function renderReadings(Service $service)
    {
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($item->data_type == 'reading') {

                    $this->renderParagraph(self::NO_INDENT, [
                        ['Schriftlesung aus ' . ($item->data['reference'] ?? ''), self::BOLD_UNDERLINE],
                    ]);
                    $this->section->addTextBreak();

                    $ref = ReferenceParser::getInstance()->parse($item->data['reference']);
                    $bibleText = (new BibleText())->get($ref);

                    $run = [];
                    foreach ($bibleText as $range) {
                        foreach ($range['text'] as $verse) {
                            $run[] = [$verse['verse'] . ' ', ['superScript' => true]];
                            $run[] = [$verse['text'] . "\n", []];
                        }
                    }

                    $this->renderParagraph(self::NO_INDENT, $run, 1);
                    $this->renderParagraph(self::NO_INDENT, [
                        ['Der Herr segne sein Wort an uns. Amen.', ['italic' => true]],
                    ],1 );
                }
            }
        }
    }

    protected function renderThanks(Service $service)
    {
        $music = $service->organists;
        foreach (['Musik', 'Band', 'Klavier', 'Schlagzeug', 'Cajon', 'Bass'] as $instrument) {
            if ($people = $service->participantsByCategory($instrument)) {
                $music->merge($people);
            }
        }
        $musicians = collect();
        foreach ($music as $musician) {
            $musicians->push(NameService::fromUser($musician)->format(NameService::FIRST_LAST));
        }

        $this->renderParagraph(self::NO_INDENT, [
            [
                'Herzlichen Dank an ' . $musicians->join(
                    ', ',
                    ' und '
                ) . ' für die schöne musikalische Begleitung des Gottesdiensts.'
            ]
        ]);
        $this->section->addTextBreak();
    }

    protected function renderOfferings(Service $service, $lastService, $offerings)
    {
        $lastService = Carbon::parse($lastService)->formatLocalized('%A');
        if ($offerings == "0,00\u{A0}€") $offerings = '';
        $this->renderParagraph(self::NO_INDENT, [
            ['Das Opfer vom letzten '.$lastService.' ergab '.($offerings ?: '______________').'.', []]
        ], );
        $this->renderParagraph(self::NO_INDENT, [
            ['Das Opfer heute erbitten wir für: '.$service->offering_goal, []]
        ]);

        if ($service->offering_text) {
            $this->renderParagraph();
            $this->renderParagraph(self::NO_INDENT, [
                [$service->offering_text, []]
            ], 1);

        }
        $this->renderParagraph(self::NO_INDENT, [
            ['Herzlichen Dank für alles, was Sie geben.', []]
        ], 1);
    }

    protected function renderEvents($events)
    {
        if (!count($events)) return;
        $this->renderParagraph(self::NO_INDENT, [['Zu folgenden Veranstaltungen laden wir Sie ein:', ['italic' => true]]], 1);
        $days = [];
        foreach ($events as $event) {
            $days[$event->start->format('Ymd')][$event->start->format('Hi')] = $event;
        }
        foreach ($days as $events) {
            $this->renderParagraph(self::NO_INDENT, [[array_values($events)[0]->start->formatLocalized('%A, %d. %B'), self::BOLD]]);
            foreach ($events as $event) {
                $this->renderParagraph(self::INDENT, [[
                    $event->event->timeText()."\t".$event->event->titleText(false)
                        .(count($event->event->pastors ?? []) ? ' mit '.$this->getNameListLine($event->event->pastors) : '')
                    .' ('.$event->event->locationText().')', []
                ]]);
            }
        }
    }

    protected function renderFinalSong(Service $service)
    {
        if (!count($service->liturgyBlocks)) {
            return;
        }
        $announcements = false;
        $this->section->addTextBreak(2);
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($announcements && ($item->data_type == 'song')) {
                    $this->renderParagraph(self::NO_INDENT, [['Wir singen gemeinsam:', []]]);
                    $helper = new SongItemHelper($item);
                    $this->renderParagraph(self::NO_INDENT, [[$helper->getTitleText() . (($item->data['verses'] ?? '') ? ', ' . $item->data['verses'] : ''), self::BOLD]]);
                }
                $announcements = in_array($item->title, ['Abkündigungen', 'Ankündigungen', 'Bekanntgaben', 'Bekanntmachungen']);
            }
        }

    }

}
