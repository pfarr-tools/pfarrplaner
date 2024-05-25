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
use App\Liturgy\Bible\ReferenceParser;
use App\Models\Calendar\Day;
use App\Models\Calendar\Occurence;
use App\Models\Leave\Absence;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\LiturgyService;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Class BillBoardReport
 * @package App\Reports
 */
class BillBoardReport extends AbstractWordDocumentReport
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
    protected const DEFAULT = 'Kirchzettel';
    protected const INDENT = 'Kirchzettel eingerückt';
    /**
     *
     */
    protected const HEADING1 = 'Kirchzettel Überschrift 1';
    /**
     *
     */
    protected const HEADING2 = 'Kirchzettel Überschrift 2';

    /**
     * @var string
     */
    public $title = 'Kirchliche Nachrichten';
    /**
     * @var string
     */
    public $group = 'Veröffentlichungen';
    /**
     * @var string
     */
    public $description = 'Kirchliche Nachrichten zum Aushang im Schaukasten';

    /** @var Section */
    protected $section;

    protected $inertia = true;

    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;

        $parishes = [];
        foreach ($cities as $city) {
            $parishes[$city->id] = Parish::with('users')->where('city_id', $city->id)->get();
        }

        return Inertia::render('Report/BillBoard/Setup', compact('cities', 'parishes'));
    }

    /**
     * @param Request $request
     * @return string|void
     * @throws Exception
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'city' => 'required|int',
                'altCity' => 'nullable|string',
                'start' => 'required|date',
                'parishes.*' => 'nullable|int|exists:parishes,id',
                'pastors.*' => 'nullable|int|exists:users,id',
            ]
        );

        $start = Carbon::parse($data['start'])->startOfDay();
        $end = $start->copy()->addDays(7)->endOfDay();
        $city = City::findOrFail($data['city']);
        $parishes = Parish::with('users')->whereIn('id', $data['parishes'])->get();

        $events = Occurence::with('event')
            ->between($start, $end)
            ->whereHas('service', function ($query) use ($city) {
                $query->inCity($city)->notHidden();
            })
            ->orderBy('start')
            ->get()
            ->groupBy(function (Occurence $item, int $key) {
                return $item->start->format('Ymd');
            });

        $firstService = Service::inCity($city)->between($start, $end)->ordered()->first();

        $absences = Absence::whereIn('user_id', $data['pastors'])->byPeriod($start, $end)->get();

        $this->section = $this->wordDocument->addSection(
            [
                'orientation' => 'portrait',
                'marginTop' => Converter::cmToTwip(2),
                'marginBottom' => Converter::cmToTwip(2),
                'marginLeft' => Converter::cmToTwip(2),
                'marginRight' => Converter::cmToTwip(2),
            ]
        );

        $this->wordDocument->addParagraphStyle(
            static::DEFAULT,
            [
                'spaceAfter' => 0,
                'tabs' => [
                    new Tab('right', Converter::cmToTwip(13.5)),
                ],
            ]
        );

        $this->wordDocument->addParagraphStyle(
            static::INDENT,
            array(
                'indentation' => [
                    'left' => Converter::cmToTwip(3),
                    'hanging' => Converter::cmToTwip(3),
                ],
                'tabs' => [
                    new Tab('left', Converter::cmToTwip(3)),
                    new Tab('right', Converter::cmToTwip(18)),
                ],
                'spaceAfter' => 0,
            )
        );

        $this->wordDocument->addParagraphStyle(
            static::HEADING1,
            array(
                'align' => 'center',
                'spaceAfter' => 0,
            )
        );

        $this->wordDocument->addParagraphStyle(
            static::HEADING2,
            array(
                'indentation' => [
                    'left' => Converter::cmToTwip(7.5),
                ],
                'spaceAfter' => 0,
            )
        );

        $this->wordDocument->setDefaultFontName('Arial');
        $this->wordDocument->setDefaultFontSize(12);


        $this->renderParagraph(static::HEADING1, [['Kirchliche Nachrichten '.($data['altCity'] ?: $city->name), ['size' => 27]]]);
        $this->renderBibleText($start);
        $this->section->addTextBreak(2);

        if ($firstService->announcements) {
            foreach (explode("\n", str_replace("\r", '', $firstService->announcements)) as $announcement) {
                $this->renderParagraph(static::DEFAULT, [[$announcement, []]]);
            }
            $this->section->addTextBreak(2);
        }

        $this->renderInfoHeader($city, $parishes);
        $this->section->addTextBreak(2);
        $this->renderEvents($events);
        $this->section->addTextBreak(3);
        $this->renderAbsences($absences);


        $filename = '91.8_' . $start->format('Ymd') . ' Kirchliche Nachrichten ' . $city->name;
        $this->sendToBrowser($filename);
    }

    protected function renderBibleText($start)
    {
        if($liturgy = LiturgyService::getDayInfo($start)) {
            $this->renderParagraph(static::HEADING1, [['Wochenspruch:', ['size' => 18, 'color' => '#0070c0']]]);
            $this->renderParagraph(static::HEADING1, [[$liturgy['litTextsWeeklyQuoteText'], ['size' => 16, 'color' => '#0070c0']]]);
            $this->renderParagraph(static::HEADING1, [[ReferenceParser::getInstance()->beautify($liturgy['litTextsWeeklyQuote']), ['size' => 12, 'color' => '#0070c0']]]);
        }
    }

    protected function renderParagraphWithImage($text, $textFormat, $image, $imageOptions = []) {
        $run = $this->section->addTextRun(static::DEFAULT);
        $run->addText($text."\t", $textFormat);
        if ($image) {
            $run->addImage(storage_path('app/'.$image), $imageOptions);
        }
    }

    protected function renderInfoHeader($city, $parishes)
    {

        // headings
        $title = $city->official_title ?: 'Evangelische Kirchengemeinde ' . $city->name;
        $this->renderParagraph(static::DEFAULT, [[$title, ['size' => 22]]]);

        $pastors = collect();
        foreach ($parishes as $parish) {
            if (count($parish->users)) {
                $pastors = $parish->users->map(function (User $item, int $key) {
                    return NameService::fromUser($item)->format(NameService::TITLE_FIRST_LAST);
                })->join(', ', ' und ');
                $this->renderParagraphWithImage($pastors, static::BOLD, $city->logo, [
                    'width' => Converter::cmToPoint(3.5),
                    'positioning' => 'relative',
                    'wrappingStyle' => 'tight',
                ]);
                $firstPastor = $parish->users->first();
                $this->renderParagraph(static::DEFAULT, [[trim(explode("\r\n", $firstPastor->address)[0]), []]]);
                $this->renderParagraph(static::DEFAULT, [['Telefon ' . $firstPastor->phone, []]]);
            }

            $emails = [];
            foreach ($parish->users as $pastor) {
                if ($pastor->email) {
                    $emails[] = $pastor->email;
                }
            }
            if ($parish->email) {
                $emails[] = $parish->email;
            }
            $this->renderParagraph(static::INDENT, [["E-Mail:\t" . join('<w:br/>', $emails), []]]);
            $this->renderParagraph(static::INDENT, [["Homepage:\t" . parse_url($city->homepage, PHP_URL_HOST), []]]);
            if ($parish->opening_hours) {
                $this->renderParagraph(static::DEFAULT, [['Sprechzeiten im Pfarrbüro'.($parish->assistant ? ', '.$parish->assistant : ''), []]]);
                $this->renderParagraph(static::DEFAULT, [[$parish->opening_hours, []]], 1);
            }
        }
    }

    protected function renderEvents($events)
    {
        if (!count($events)) {
            return;
        }
        $this->renderParagraph(static::DEFAULT, [['Termine', static::BOLD]], 2);
        foreach ($events as $dayEvents) {
            $title = $dayEvents->first()->start->formatLocalized('%A, %d. %B') . ' ';
            if ($dayEvents->first()->event->liturgicalInfo['title'] ?? false) {
                $title .= ' - ' . $dayEvents->first()->event->liturgicalInfo['title'] . ' - ';
            }
            $this->renderParagraph(static::DEFAULT, [[trim($title), static::BOLD]]);
            foreach ($dayEvents as $event) {
                $line = [
                    $event->event->titleText(false) . (count(
                        $event->event->pastors ?? []
                    ) ? ' mit ' . $this->getNameListLine($event->event->pastors) : '')
                ];
                if ($event->event->event_class == 'service') {
                    $line[] = 'Musik: ' . $this->getNameListLine($event->event->organists);
                    $line[] = 'Opfer: ' . $event->event->offering_goal;
                    $line[] = '';
                }
                $this->renderParagraph(
                    static::INDENT,
                    [[$event->event->timeText() . "\t" . join('<w:br />', $line), []]]
                );
            }
            $this->section->addTextBreak();
        }
    }

    protected function renderAbsences(Collection $absences)
    {
        if (!count($absences)) {
            return;
        }
        /** @var Absence $absence */
        foreach ($absences as $absence) {
            $this->renderParagraph(static::DEFAULT, [[$absence->descriptiveText, []]], 1);
        }
    }

    /**
     * @param $people
     * @param $and
     * @return string
     */
    protected function getNameListLine($people, $and = ', ')
    {
        $names = collect();
        foreach ($people as $person) {
            $names->push(NameService::fromUser($person)->format(NameService::TITLE_FIRST_LAST));
        }
        return $names->join(', ', $and);
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
                    $format = static::BOLD;
                    $paragraph = substr($paragraph, 1);
                    break;
                case '_':
                    $format = static::UNDERLINE;
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
            $this->renderParagraph(static::NO_INDENT, [[$paragraph, $format]], 1);
        }
    }
}
