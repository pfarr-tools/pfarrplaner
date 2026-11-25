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

use App\Imports\EventCalendarImport;
use App\Imports\OPEventsImport;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Class EventListReport
 */
class EventListReport extends AbstractWordDocumentReport
{
    protected const BOLD = ['bold' => true];

    protected const UNDERLINE = ['underline' => Font::UNDERLINE_SINGLE];

    protected const BOLD_UNDERLINE = ['bold' => true, 'underline' => Font::UNDERLINE_SINGLE];

    protected const DEFAULT = 'Gottesdienste';

    protected const LIST2 = 'Termine';

    protected const NO_INDENT = 'Bekanntgaben ohne Einrückung';

    public const FILE_SIGNATURE = '91.8';

    public const FILE_TITLE = 'Terminliste';

    /**
     * @var string
     */
    public $title = 'Terminliste';

    /**
     * @var string
     */
    public $group = 'Veröffentlichungen';

    /**
     * @var string
     */
    public $description = 'Terminliste als Worddatei';

    /** @var Section */
    protected $section;

    protected $inertia = true;

    /**
     * @return Response
     */
    public function setup()
    {
        $user = $this->authenticatedUser();
        $cities = $user->cities;

        return Inertia::render('Report/EventList/Setup', compact('cities'));
    }

    /**
     * @return string|void
     */
    public function render(Request $request)
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate(
            [
                'city' => 'required|int',
                'start' => 'required|date',
                'end' => 'required|date',
                'mixOutlook' => 'nullable|bool',
                'mixOP' => 'nullable|bool',
            ]
        );

        /** @var string $startDate */
        $startDate = $data['start'];
        /** @var string $endDate */
        $endDate = $data['end'];
        /** @var int|string $cityId */
        $cityId = $data['city'];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $city = City::findOrFail((int) $cityId);

        $services = Service::with(['location'])
            ->regularForCity($city)
            ->displayable()
            ->between($start, $end)
            ->ordered()
            ->get();

        $events = [];

        if ($data['mixOutlook'] ?? false) {
            $calendar = new EventCalendarImport($city->public_events_calendar_url);
            $events = $calendar->mix($events, $start, $end->copy()->subDay(), true);
        }

        $events = Service::mix($events, $services, $start, $end);

        if ($data['mixOP'] ?? false) {
            $op = new OPEventsImport($city);
            $events = $op->mix($events, $start, $end);
        }

        $this->wordDocument->setDefaultFontName('Arial Narrow');
        $this->wordDocument->setDefaultFontSize(11);

        $this->section = $this->wordDocument->addSection(
            [
                'orientation' => 'portrait',
                'marginTop' => Converter::cmToTwip(0.63),
                'marginBottom' => Converter::cmToTwip(0.5),
                'marginLeft' => Converter::cmToTwip(0.59),
                'marginRight' => Converter::cmToTwip(0.61),
            ]
        );

        $this->wordDocument->addParagraphStyle(
            self::DEFAULT,
            [
                'indentation' => [
                    'left' => Converter::cmToTwip(1.3),
                    'hanging' => Converter::cmToTwip(0),
                ],
                'tabs' => [
                    new Tab('left', (int) Converter::cmToTwip(1.3)),
                    new Tab('right', (int) Converter::cmToTwip(4.76)),
                    new Tab('left', (int) Converter::cmToTwip(5.5)),
                    new Tab('left', (int) Converter::cmToTwip(15.0)),
                ],
                'spaceAfter' => 0,
            ]
        );

        $this->wordDocument->addParagraphStyle(
            self::LIST2,
            [
                'indentation' => [
                    'left' => Converter::cmToTwip(1.3),
                    'hanging' => Converter::cmToTwip(0),
                ],
                'tabs' => [
                    new Tab('left', (int) Converter::cmToTwip(1.3)),
                    new Tab('left', (int) Converter::cmToTwip(2)),
                    new Tab('right', (int) Converter::cmToTwip(4.44)),
                    new Tab('left', (int) Converter::cmToTwip(5.4)),
                ],
                'spaceAfter' => 0,
            ]
        );

        $this->renderParagraph(
            self::DEFAULT,
            [
                ['Gottesdienste', self::BOLD],
            ],
            1
        );

        // render services
        foreach ($events as $theseEvents) {
            if (! is_array($theseEvents)) {
                continue;
            }
            foreach ($theseEvents as $event) {
                if (is_object(($event))) {
                    /** @var Service $event */
                    // only services this time

                    /** @var array<int, array<string, mixed>> $liturgyDays */
                    $liturgyDays = LiturgyService::getLiturgyInfoByDate($event->trueDate());
                    /** @var array<string, mixed> $liturgy */
                    $liturgy = $liturgyDays[0] ?? [];
                    $pericope = '';
                    if (isset($liturgy['perikope']) && is_string($liturgy['perikope'])) {
                        $pericopeText = $liturgy['litTextsPerikope'.$liturgy['perikope']] ?? '';
                        $pericope = is_string($pericopeText) ? $pericopeText : '';
                    }
                    $liturgyName = $liturgy['Bezeichnung'] ?? null;
                    $liturgyName = is_string($liturgyName) ? $liturgyName : null;

                    // remove optional verses
                    if ($pericope) {
                        $pericope = (string) preg_replace('/\([\d–]*\)/', '+', $pericope);
                        if (substr($pericope, -1) == '+') {
                            $pericope = substr($pericope, 0, -1);
                        }
                        $pericope = str_replace(',+', ',', $pericope);
                    }

                    $run = $this->renderParagraph(
                        self::DEFAULT,
                        [
                            [$event->trueDate()->format('d.m.Y')."\t", []],
                            [$event->timeText(true)."\t", []],
                        ],
                        0
                    );

                    if ($liturgyName !== null) {
                        $this->renderParagraph(
                            self::DEFAULT,
                            [
                                [$liturgyName.' – ', []],
                            ],
                            0,
                            $run
                        );
                    }

                    $title = $event->title ?: 'Gottesdienst';
                    if ($event->eucharist) {
                        $title = 'Abendmahlsgottesdienst';
                    }
                    if ($event->baptism) {
                        $title = 'Taufgottesdienst';
                    }

                    if ($p = $event->participantsText('P', true)) {
                        $this->renderParagraph(
                            self::DEFAULT,
                            [
                                [$title.' mit '.$p, []],
                            ],
                            0,
                            $run
                        );
                    }

                    $this->renderParagraph(
                        self::DEFAULT,
                        [
                            ["\t".$pericope, []],
                        ],
                        0,
                        $run
                    );

                    if ($description = $event->description) {
                        if (strlen($description) > 25) {
                            $description = str_replace('; ', ';<w:br />', $description);
                        }
                        // take care of ampersands
                        $description = trim((string) preg_replace('/&(?![A-Za-z0-9#]{1,7};)/', '&amp;', $description));
                        $this->renderParagraph(
                            self::DEFAULT,
                            [
                                ["<w:br />\t\t".$description, []],
                            ],
                            0,
                            $run
                        );
                    } elseif ($liturgyName !== null) {
                        $this->renderParagraph(
                            self::DEFAULT,
                            [
                                ["<w:br />\t\t".$liturgyName, []],
                            ],
                            0,
                            $run
                        );
                    }

                    if ($event->offeringGoal()) {
                        $this->renderParagraph(
                            self::DEFAULT,
                            [
                                ["<w:br />\t\tOpfer: ".$event->offeringGoal(), []],
                            ],
                            0,
                            $run
                        );
                    }

                    if ($event->cc) {
                        $ccLocation = $event->ccLocationText();
                        $ccLocation = is_string($ccLocation) ? $ccLocation : '';
                        $this->renderParagraph(
                            self::DEFAULT,
                            [
                                [
                                    "<w:br />\t\tKinderkirche: ".$event->ccTimeText(
                                        false
                                    ).', '.$ccLocation,
                                    [],
                                ],
                            ],
                            0,
                            $run
                        );
                    }

                    $this->renderParagraph(
                        self::DEFAULT,
                        [
                        ],
                        1,
                        $run
                    );
                }
            }
        }

        $this->renderParagraph();
        $this->renderParagraph();
        $this->renderParagraph();
        $this->renderParagraph(
            self::DEFAULT,
            [
                ['Termine', self::BOLD],
            ],
            1
        );

        // render other events
        foreach ($events as $theseEvents) {
            if (! is_array($theseEvents)) {
                continue;
            }
            foreach ($theseEvents as $event) {
                if (! is_object(($event)) && is_array($event)) {
                    $eventStart = $event['start'] ?? null;
                    $eventTitle = $event['title'] ?? '';
                    if (! $eventStart instanceof Carbon) {
                        continue;
                    }
                    $eventTitle = is_string($eventTitle) ? $eventTitle : '';
                    $run = $this->renderParagraph(
                        self::LIST2,
                        [
                            [$eventStart->isoFormat('dd.'), []],
                            ["\t".$eventStart->format('d.m.'), []],
                            ["\t".$eventStart->format('H:i').' Uhr', []],
                            ["\t".$eventTitle, []],
                        ]
                    );
                }
            }
        }

        $cityName = $city->name;
        $cityName = is_string($cityName) ? $cityName : '';

        return $this->sendToBrowser(
            FileNameService::make(
                self::FILE_TITLE.' '.$cityName,
                '.docx',
                static::FILE_SIGNATURE,
                Carbon::now())
        );
    }

    /**
     * @param  string  $template  Name des zu verwendenden Absatzstils.
     * @param  array<int, array{0: string, 1: array<string, mixed>}>  $blocks  Textblöcke mit ihren Formatierungsoptionen.
     * @param  int  $emptyParagraphsAfter  Anzahl der anzuhängenden leeren Absätze.
     * @param  TextRun|null  $existingTextRun  Optionaler bestehender Textlauf, der ergänzt wird.
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
            $textRun->addText($block[0], $block[1]);
        }
        for ($i = 0; $i < $emptyParagraphsAfter; $i++) {
            $textRun = $this->section->addTextRun($template);
        }

        return $textRun;
    }

    /**
     * @param  string|array<int, string>  $text  Zu rendernder Text oder Absätze.
     * @return void
     */
    protected function renderLiteral($text)
    {
        if (! is_array($text)) {
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
                        "\n" => '<w:br />',
                    ]
                )
            );
            $this->renderParagraph(self::NO_INDENT, [[$paragraph, $format]], 1);
        }
    }
}
