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

use App\Liturgy\Bible\ReferenceParser;
use App\Models\Calendar\Occurence;
use App\Models\Leave\Absence;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\LiturgyService;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public const FILE_SIGNATURE = '91.8';
    public const FILE_TITLE = 'Kirchliche Nachrichten';


    protected $useDefaultDocument = true;


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
                'cities' => 'required',
                'cities.*' => 'int|exists:cities,id',
                'altCity' => 'nullable|string',
                'printHeaders' => 'nullable|boolean',
                'start' => 'required|date',
                'parishes.*' => 'nullable|int|exists:parishes,id',
                'pastors.*' => 'nullable|int|exists:users,id',
            ]
        );

        $presets = $data;
        unset($presets['start']);
        Auth::user()->setSetting('reports_billboard_presets', $presets);

        $start = Carbon::parse($data['start'], 'UTC')->setTimeZone('Europe/Berlin')->startOfDay();
        $end = $start->copy()->addDays(7)->endOfDay();
        $cities = City::whereIn('id', $data['cities'])->get();
        $parishes = (count($data['parishes'] ?? [])) ? Parish::with('users')->whereIn('id', $data['parishes'])->get() : collect();

        $events = Occurence::with('event')
            ->between($start, $end)
            ->whereHas('service', function ($query) use ($cities, $start) {
                $query->inCities($cities)->displayable($start);
            })
            ->orderBy('start')
            ->get()
            ->groupBy(function (Occurence $item, int $key) {
                return $item->start->format('Ymd');
            });

        $firstService = Service::inCities($cities)->between($start, $end)->ordered()->first();

        $absences = (count($data['pastors'] ?? [])) ? Absence::whereIn('user_id', $data['pastors'])->byPeriod($start, $end)->get() : collect();

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


        $cityTitle = $data['altCity'] ?? $cities->pluck('name')->join(', ', ' und ');
        $this->doc->renderParagraph(static::HEADING1, [['Kirchliche Nachrichten '.$cityTitle, ['size' => 27]]]);
        $this->renderBibleText($start);
        $this->section->addTextBreak(2);

        if ($firstService?->announcements) {
            foreach (explode("\n", str_replace("\r", '', $firstService->announcements)) as $announcement) {
                $this->doc->renderParagraph(static::DEFAULT, [[$announcement, []]]);
            }
            $this->section->addTextBreak(2);
        }

        if ($data['printHeaders']) {
            $this->renderInfoHeader($cities, $parishes);
        }
        $this->doc->renderParagraph();
        $this->renderEvents($events, $cities);
        $this->doc->renderParagraph();
        $this->renderAbsences($absences);


        return $this->sendToBrowser(
            FileNameService::make(
                static::FILE_TITLE.' '.$cityTitle,
                '.docx',
                static::FILE_SIGNATURE,
                Carbon::now())
        );
    }

    /**
     * @param Carbon $start
     * @return void
     */
    protected function renderBibleText(Carbon $start)
    {
        $liturgyDay = $start->dayOfWeek == 0 ? $start->copy() : $start->copy()->parse('next sunday');
        $liturgy = LiturgyService::getLiturgyInfoByDate($liturgyDay);
        if($liturgy && (isset($liturgy[0]))) {
            $liturgy = $liturgy[0];
            $this->doc->renderParagraph(static::HEADING1, [['Wochenspruch:', ['size' => 18, 'color' => '#0070c0']]]);
            $this->doc->renderParagraph(static::HEADING1, [[$liturgy['Wochenspruch']['Text'], ['size' => 16, 'color' => '#0070c0']]]);
            $this->doc->renderParagraph(static::HEADING1, [[ReferenceParser::getInstance()->beautify($liturgy['Wochenspruch']['Bibelstelle']), ['size' => 12, 'color' => '#0070c0']]]);
        }
    }

    protected function renderParagraphWithImage($text, $textFormat, $image, $imageOptions = []) {
        $run = $this->section->addTextRun(static::DEFAULT);
        $run->addText($text."\t", $textFormat);
        if ($image && Storage::exists('app/'.$image)) {
            $run->addImage(storage_path('app/'.$image), $imageOptions);
        }
    }

    protected function renderInfoHeader($cities, $parishes)
    {
        $rendered = [];
        foreach ($cities as $city) {

            // headings
            $title = $city->official_title ?: 'Evangelische Kirchengemeinde ' . $city->name;
            if (in_array($title, $rendered)) continue;
            $rendered[] = $title;

            $this->doc->renderParagraph(static::DEFAULT, [[$title, ['size' => 22]]]);

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
                    $this->doc->renderParagraph(static::DEFAULT, [[trim(explode("\r\n", $firstPastor->address)[0]), []]]);
                    $this->doc->renderParagraph(static::DEFAULT, [['Telefon ' . $firstPastor->phone, []]]);
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
                $this->doc->renderParagraph(static::INDENT, [["E-Mail:\t" . join('<w:br/>', $emails), []]]);
                $this->doc->renderParagraph(static::INDENT, [["Homepage:\t" . parse_url($city->homepage, PHP_URL_HOST), []]]);
                if ($parish->opening_hours) {
                    $this->doc->renderParagraph(static::DEFAULT, [['Sprechzeiten im Pfarrbüro'.($parish->assistant ? ', '.$parish->assistant : ''), []]]);
                    $this->doc->renderParagraph(static::DEFAULT, [[$parish->opening_hours, []]], 1);
                }
            }

        }

    }

    protected function renderEvents($events, $cities)
    {
        if (!count($events)) {
            return;
        }

        $cityCount = count($cities);
        $cityCount = max($cityCount == 1 ? (null !== $cities[0]->parent_id ? 2 : 1) : $cityCount, $cities[0]->children->count());

        $this->doc->renderParagraph(static::DEFAULT, [['Termine', static::BOLD]], 2);
        foreach ($events as $dayEvents) {
            $title = $dayEvents->first()->start->setTimeZone('Europe/Berlin')->isoFormat('dddd, DD. MMMM') . ' ';
            if ($dayEvents->first()->event->liturgicalInfo['title'] ?? false) {
                $title .= ' - ' . $dayEvents->first()->event->liturgicalInfo['title'] . ' - ';
            }
            $this->doc->renderParagraph(static::DEFAULT, [[trim($title), static::BOLD]]);
            foreach ($dayEvents as $event) {
                $line = [
                    htmlspecialchars($event->event->titleText(false, false)) . (count(
                        $event->event->pastors ?? []
                    ) ? ' mit ' . $this->getNameListLine($event->event->pastors) : '')
                ];
                if ($event->event->description) {
                    $line[] = $event->event->description;
                }

                // figure out whether city name must be added to location string
                if (($cityCount == 1) && ((null === $event->location) || ($event->location->city_id == $cities[0]->id))) {
                    $line[] = $event->event->locationText;
                } else {
                    $line[] = $event->event->locationTextWithCity;
                }

                if ($event->event->event_class == 'service') {
                    if ($s = $this->getNameListLine($event->event->organists))$line[] = 'Musik: ' . $s;
                    if ($event->event->offeringGoal()) $line[] = 'Opfer: ' . $event->event->offeringGoal();
                    $line[] = '';
                }
                $this->doc->renderParagraph(
                    static::INDENT,
                    [[$event->event->timeText() . "\t" . join("\n", $line), []]]
                );
            }
            $this->section->addTextBreak();
        }
    }

    protected function renderAbsences($absences)
    {
        if (!count($absences)) {
            return;
        }
        /** @var Absence $absence */
        foreach ($absences as $absence) {
            $this->doc->renderParagraph(static::DEFAULT, [[$absence->descriptiveText, []]], 1);
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


}
