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

        $lastWeek = Carbon::createFromTimeString($service->date->format('Y-m-d') . ' 0:00:00 last Sunday');
        $nextWeek = $lastWeek->copy()->addWeeks(2)->setTime(
            23,
            59,
            59
        );

        $funerals = Funeral::where('announcement', $service->date->format('Y-m-d'))
            ->whereHas(
                'service',
                function ($query) use ($service, $city) {
                    $query->inCity($city)
                        ->displayable($service->date);
                }
            )
            ->get();

        $weddings = Wedding::with('service')
            ->whereHas(
                'service',
                function ($query) use ($service, $nextWeek, $city) {
                    $query->between($service->date, $nextWeek)
                        ->inCity($city)
                        ->displayable($service->date)
                        ->ordered();
                }
            )->get();

        $baptisms = Baptism::with('service')
            ->whereHas(
                'service',
                function ($query) use ($service, $nextWeek, $city) {
                    $query->between($service->date, $nextWeek)
                        ->inCity($city)
                        ->displayable($service->date)
                        ->ordered();
                }
            )->get();

        $liturgicalInfo = $service->liturgicalInfo;

        $events = Occurence::with('event')
            ->between($service->date->copy()->addHour(1), $nextWeek)
            ->whereHas('service', function ($query) use ($service, $city, $data) {
                $query->withoutGlobalScope(ServicesOnlyScope::class);
                $query->inCity($city)->displayable($service->date);
                if ($data['excludeRegularWeekly'] ?? false) {
                    // do not include events that are (1) not services and (2) repeat every week
                    $query->where(function ($q2) {
                        $q2->where('event_class', 'service')
                            ->orWhere('rrule', 'not like', '%FREQ=WEEKLY;INTERVAL=1%');
                    });
                }
            })
            ->orderBy('start')
            ->get();

        $featuredEvents = Occurence::with('event')
            ->whereHas('service', function ($query) use ($service, $city) {
                $query->inCity($city)->displayable($service->date);
            })
            ->adRunningAt('bekanntgaben', $service->date)
            ->orderBy('start')
            ->get();

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

        $this->renderThanks($service);

        $this->renderOfferings($service, $lastService, $offerings);

        $this->renderEvents($events);

        $this->renderFeaturedEvents($featuredEvents);

        $this->doc->renderParagraph(self::NO_INDENT, [
            [
                'Alle weiteren Veranstaltungen finden Sie in den Aushängen'
                . ($service->city->communiapp_token ? ', auf unserer Homepage oder in unserer App.' : ' oder auf unserer Homepage.'),
                [], true
            ]
        ]);

        $textRun = $this->doc->renderParagraph();

        // Baptisms
        if (count($baptisms)) {
            $this->doc->renderParagraph(self::NO_INDENT, [['Taufen', self::BOLD_UNDERLINE]]);

            $baptismArray = [];
            foreach ($baptisms as $baptism) {
                $baptismArray[$baptism->service->trueDate()->format('YmdHis')][] = $baptism;
            }
            ksort($baptismArray);

            foreach ($baptismArray as $baptisms) {
                $baptism = $baptisms[array_key_first($baptisms)];
                if ($baptism->service->id != $service->id) {
                    $textRun = $this->doc->renderParagraph();
                    if ($baptism->service->trueDate() == $service->trueDate()) {
                        $this->doc->renderParagraph(
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
                        $this->doc->renderParagraph(
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
                        $this->doc->renderParagraph(
                            self::NO_INDENT,
                            [
                                [$this->renderName($baptism->candidate_name) . ', ' . $baptism->candidate_address, []]
                            ]
                        );
                    }
                }
            }
            $this->doc->renderParagraph();
            $this->doc->renderNormalText(
                '*Christus hat der Kirche den Auftrag gegeben:
Gehet hin und machet zu Jüngern alle Völker
und taufet sie auf den Namen des Vaters und
des Sohnes und des Heiligen Geistes.'
            );
        }


        if (count($weddings)) {
            $this->doc->renderParagraph(self::NO_INDENT, [['Trauungen', self::BOLD_UNDERLINE]]);

            $weddingArray = [];
            foreach ($weddings as $wedding) {
                $weddingArray[$wedding->service->trueDate()->format('YmdHis')][] = $wedding;
            }
            ksort($weddingArray);

            foreach ($weddingArray as $weddings) {
                $wedding = $weddings[array_key_first($weddings)];
                if ($wedding->service->id != $service->id) {
                    $textRun = $this->doc->renderParagraph();
                    if ($wedding->service->trueDate() == $service->trueDate()) {
                        $this->doc->renderParagraph(
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
                        $this->doc->renderParagraph(
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
                        $this->doc->renderParagraph(
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
            $this->doc->renderParagraph();
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
            $this->doc->renderParagraph(self::NO_INDENT, [['Bestattungen', self::BOLD_UNDERLINE]]);

            $funeralArray = ['past' => [], 'future' => []];
            foreach ($funerals as $funeral) {
                $key = ($funeral->service->trueDate() < $service->trueDate()) ? 'past' : 'future';
                $funeralArray[$key][] = $funeral;
            }

            if (count($funeralArray['past'])) {
                ksort($funeralArray['past']);
                $this->doc->renderParagraph(
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
                    $this->doc->renderParagraph(
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
                    $this->doc->renderParagraph();
                }
            }

            if (count($funeralArray['future'])) {
                ksort($funeralArray['future']);
                $this->doc->renderParagraph(
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
                    $this->doc->renderParagraph(
                        self::NO_INDENT,
                        [
                            [
                                $this->renderName($funeral->buried_name) . ', '
                                . $funeral->buried_address
                                . ($funeral->age() ? ', ' . $funeral->age() . ' Jahre' : '')
                                . '. Die ' . $mode . ' findet am ' . $funeral->service->date->isoFormat(
                                    'dddd, DD. MMMM'
                                )
                                . ' um ' . $funeral->service->timeText(true, '.')
                                . ' ' . $funeral->service->atText() . ' statt.',
                                []
                            ]
                        ]
                    );
                }
            }


            $this->doc->renderParagraph();
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
            $this->doc->renderParagraph();
            $textRun = $this->renderLiteral($service->announcements);
        }

        $this->renderFinalSong($service);

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
            $this->doc->renderParagraph(self::NO_INDENT, [[$paragraph, $format]], 1);
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

        $this->doc->renderParagraph(self::NO_INDENT, [
            [
                'Herzlichen Dank an ' . $musicians->join(
                    ', ',
                    ' und '
                ) . ' für die schöne musikalische Begleitung des Gottesdiensts.',
                []
            ]
        ]);
        $this->doc->getSection()->addTextBreak();
    }

    protected function renderOfferings(Service $service, $lastService, $offerings)
    {
        $lastService = Carbon::parse($lastService)->isoFormat('dddd');
        if ($offerings == "0,00\u{A0}€") {
            $offerings = '';
        }
        $this->doc->renderParagraph(self::NO_INDENT, [
            ['Das Opfer vom letzten ' . $lastService . ' ergab ' . ($offerings ?: '______________') . '.', []]
        ],);
        if (!empty($service->offering_goal)) {
            $this->doc->renderParagraph(self::NO_INDENT, [
                ['Das Opfer heute erbitten wir für: ' . $service->offering_goal, []]
            ]);
        } else {
            $this->doc->renderParagraph(self::NO_INDENT, [
                ['Das Opfer heute erbitten wir für die vielfältigen Aufgaben in unserer Kirchengemeinde.', []]
            ]);
        }

        if ($service->offering_text) {
            $this->doc->renderParagraph();
            $this->doc->renderParagraph(self::NO_INDENT, [
                [$service->offering_text, []]
            ],                          1);
        }
        $this->doc->renderParagraph(self::NO_INDENT, [
            ['Herzlichen Dank für alles, was Sie geben.', []]
        ],                          1);
    }

    protected function renderEvents($events)
    {
        if (!count($events)) {
            return;
        }
        $this->doc->renderParagraph(
            self::NO_INDENT,
            [
                [
                    (count(
                        $events
                    ) == 1 ? 'Zu folgender Veranstaltung' : 'Zu folgenden Veranstaltungen') . ' laden wir Sie ein:',
                    ['italic' => true]
                ]
            ],
            1
        );
        $days = [];
        foreach ($events as $event) {
            $days[$event->start->format('Ymd')][$event->start->format('Hi')] = $event;
        }
        foreach ($days as $events) {
            $this->doc->renderParagraph(
                self::NO_INDENT,
                [[array_values($events)[0]->start->isoFormat('dddd, DD. MMMM'), self::BOLD]]
            );
            foreach ($events as $event) {
                $this->doc->renderParagraph(self::INDENT, [
                    [
                        $event->event->timeText() . "\t" . Str::replace(
                            '&',
                            '&amp;',
                            $event->event->titleText(false, false)
                        )
                        . (count($event->event->pastors ?? []) ? ' mit ' . $this->getNameListLine(
                                $event->event->pastors
                            ) : '')
                        . ' (' . $event->event->locationTextWithCity . ')'
                        . ($event->event->description ? "\n" . $event->event->description : '')
                        ,
                        []
                    ]
                ]);
            }
        }
    }

    protected function renderFeaturedEvents($events)
    {
        if (!count($events)) {
            return;
        }
        $this->doc->renderParagraph();
        $this->doc->renderParagraph(
            self::NO_INDENT,
            [
                [
                    'Ganz besonders weisen wir auf folgende ' . (count(
                        $events
                    ) == 1 ? 'Veranstaltung' : 'Veranstaltungen') . ' hin:',
                    ['italic' => true]
                ]
            ],
            1
        );
        $days = [];
        foreach ($events as $event) {
            $this->doc->renderParagraph(
                self::NO_INDENT,
                [
                    [
                        $event->start->isoFormat('dddd, DD. MMMM') . ', ' . $event->service->timeText(
                        ) . ', ' . $event->service->locationTextWithCity,
                        self::BOLD
                    ]
                ]
            );
            $this->doc->renderParagraph(
                self::NO_INDENT,
                [[$event->service->titleText(false), self::BOLD]]
            );
            $this->doc->renderParagraph(self::NO_INDENT, [
                [$event->getAdText('newsletter'), []]
            ]);
        }
    }

    protected function renderFinalSong(Service $service)
    {
        if (!count($service->liturgyBlocks)) {
            return;
        }
        $announcements = false;
        $this->doc->getSection()->addTextBreak(2);
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($announcements && ($item->data_type == 'song')) {
                    $this->doc->renderParagraph(self::NO_INDENT, [['Wir singen gemeinsam:', []]]);
                    $helper = new SongItemHelper($item);
                    $this->doc->renderParagraph(
                        self::NO_INDENT,
                        [
                            [
                                $helper->getTitleText(
                                ) . (($item->data['verses'] ?? '') ? ', ' . $item->data['verses'] : ''),
                                self::BOLD
                            ]
                        ]
                    );
                }
                $announcements = in_array(
                    $item->title,
                    ['Abkündigungen', 'Ankündigungen', 'Bekanntgaben', 'Bekanntmachungen']
                );
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
