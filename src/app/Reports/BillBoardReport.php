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

use App\Helpers\StoragePath;
use App\Liturgy\Bible\ReferenceParser;
use App\Models\Calendar\Occurence;
use App\Models\Leave\Absence;
use App\Models\Location;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\LiturgyService;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Class BillBoardReport
 */
class BillBoardReport extends AbstractWordDocumentReport
{
    protected const BOLD = ['bold' => true];

    protected const UNDERLINE = ['underline' => Font::UNDERLINE_SINGLE];

    protected const BOLD_UNDERLINE = ['bold' => true, 'underline' => Font::UNDERLINE_SINGLE];

    protected const string DEFAULT = 'Kirchzettel';

    protected const string INDENT = 'Kirchzettel eingerückt';

    protected const string HEADING1 = 'Kirchzettel Überschrift 1';

    protected const string HEADING2 = 'Kirchzettel Überschrift 2';

    public const string FILE_SIGNATURE = '91.8';

    public const string FILE_TITLE = 'Kirchliche Nachrichten';

    /** @var bool */
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
     * @return Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->cities;

        $parishes = [];
        foreach ($cities as $city) {
            $parishes[$city->id] = Parish::with('users')->where('city_id', $city->id)->get();
        }

        return Inertia::render('Report/BillBoard/Setup', compact('cities', 'parishes'));
    }

    /**
     * @return string|void
     *
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

        /** @var array<string, mixed> $data */
        $presets = $data;
        unset($presets['start']);
        $this->authenticatedUser()->setSetting('reports_billboard_presets', $presets);

        /** @var string $startDate */
        $startDate = $data['start'];
        $start = Carbon::parse($startDate, 'UTC')->setTimeZone('Europe/Berlin')->startOfDay();
        $end = $start->copy()->addDays(7)->endOfDay();
        /** @var array<int, int> $cityIds */
        $cityIds = $data['cities'];
        $cities = City::whereIn('id', $cityIds)->get();
        /** @var array<int, int> $parishIds */
        $parishIds = $data['parishes'] ?? [];
        $parishes = (count($parishIds)) ? Parish::with('users')->whereIn('id', $parishIds)->get() : collect();

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

        /** @var array<int, int> $pastorIds */
        $pastorIds = $data['pastors'] ?? [];
        $absences = (count($pastorIds)) ? Absence::whereIn('user_id', $pastorIds)->byPeriod($start, $end)->get() : collect();

        $this->section = $this->wordDocument->addSection(
            [
                'orientation' => 'portrait',
                'marginTop' => (int) Converter::cmToTwip(2),
                'marginBottom' => (int) Converter::cmToTwip(2),
                'marginLeft' => (int) Converter::cmToTwip(2),
                'marginRight' => (int) Converter::cmToTwip(2),
            ]
        );

        $this->wordDocument->addParagraphStyle(
            static::DEFAULT,
            [
                'spaceAfter' => 0,
                'tabs' => [
                    new Tab('right', (int) Converter::cmToTwip(13.5)),
                ],
            ]
        );

        $this->wordDocument->addParagraphStyle(
            static::INDENT,
            [
                'indentation' => [
                    'left' => (int) Converter::cmToTwip(3),
                    'hanging' => (int) Converter::cmToTwip(3),
                ],
                'tabs' => [
                    new Tab('left', (int) Converter::cmToTwip(3)),
                    new Tab('right', (int) Converter::cmToTwip(18)),
                ],
                'spaceAfter' => 0,
            ]
        );

        $this->wordDocument->addParagraphStyle(
            static::HEADING1,
            [
                'align' => 'center',
                'spaceAfter' => 0,
            ]
        );

        $this->wordDocument->addParagraphStyle(
            static::HEADING2,
            [
                'indentation' => [
                    'left' => (int) Converter::cmToTwip(7.5),
                ],
                'spaceAfter' => 0,
            ]
        );

        $this->wordDocument->setDefaultFontName('Arial');
        $this->wordDocument->setDefaultFontSize(12);

        /** @var string|null $altCity */
        $altCity = $data['altCity'] ?? null;
        $cityTitle = self::stringify($altCity ?? $cities->pluck('name')->join(', ', ' und '));
        $this->doc->renderParagraph(static::HEADING1, [['Kirchliche Nachrichten '.$cityTitle, ['size' => 27]]]);
        $this->renderBibleText($start);
        $this->section->addTextBreak(2);

        if ($firstService?->announcements) {
            foreach (explode("\n", str_replace("\r", '', (string) $firstService->announcements)) as $announcement) {
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
     * @return void
     */
    protected function renderBibleText(Carbon $start)
    {
        $liturgyDay = $start->dayOfWeek == 0 ? $start->copy() : $start->copy()->parse('next sunday');
        /** @var array<int, array<string, mixed>> $liturgyDays */
        $liturgyDays = LiturgyService::getLiturgyInfoByDate($liturgyDay);
        if ($liturgyDays && isset($liturgyDays[0])) {
            /** @var array<string, mixed> $liturgy */
            $liturgy = $liturgyDays[0];
            /** @var array<string, mixed> $verse */
            $verse = $liturgy['Wochenspruch'] ?? [];
            $this->doc->renderParagraph(static::HEADING1, [['Wochenspruch:', ['size' => 18, 'color' => '#0070c0']]]);
            $this->doc->renderParagraph(static::HEADING1, [[self::stringify($verse['Text'] ?? ''), ['size' => 16, 'color' => '#0070c0']]]);
            $this->doc->renderParagraph(static::HEADING1, [[ReferenceParser::getInstance()->beautify(self::stringify($verse['Bibelstelle'] ?? '')), ['size' => 12, 'color' => '#0070c0']]]);
        }
    }

    /**
     * @param  string  $text  Paragraph text.
     * @param  mixed  $textFormat  Text style.
     * @param  string|null  $image  Image path.
     * @param  array<string, mixed>  $imageOptions  Image style options.
     * @return void
     */
    protected function renderParagraphWithImage($text, $textFormat, $image, $imageOptions = [])
    {
        $run = $this->section->addTextRun(static::DEFAULT);
        $run->addText($text."\t", $textFormat);
        if ($image && Storage::exists('app/'.$image)) {
            $run->addImage(StoragePath::for($image), $imageOptions);
        }
    }

    /**
     * @param  mixed  $cities  Cities to render.
     * @param  mixed  $parishes  Parishes by city.
     * @return void
     */
    protected function renderInfoHeader($cities, $parishes)
    {
        /** @var Collection<int, City> $cities */
        /** @var Collection<int, Parish> $parishes */
        $rendered = [];
        foreach ($cities as $city) {

            // headings
            $title = self::stringify($city->getAttribute('official_title') ?: 'Evangelische Kirchengemeinde '.(string) $city->name);
            if (in_array($title, $rendered)) {
                continue;
            }
            $rendered[] = $title;

            $this->doc->renderParagraph(static::DEFAULT, [[$title, ['size' => 22]]]);

            $pastors = collect();
            foreach ($parishes as $parish) {
                /** @var Collection<int, User> $users */
                $users = $parish->users;
                if (count($users)) {
                    $pastors = (string) $users->map(function (User $item, int $key) {
                        return NameService::fromUser($item)->format(NameService::TITLE_FIRST_LAST);
                    })->join(', ', ' und ');
                    $logo = $city->logo;
                    $this->renderParagraphWithImage($pastors, static::BOLD, is_string($logo) ? $logo : null, [
                        'width' => (int) Converter::cmToPoint(3.5),
                        'positioning' => 'relative',
                        'wrappingStyle' => 'tight',
                    ]);
                    /** @var User|null $firstPastor */
                    $firstPastor = $users->first();
                    $address = self::stringify($firstPastor->address ?? '');
                    $this->doc->renderParagraph(static::DEFAULT, [[trim(explode("\r\n", $address)[0]), []]]);
                    $this->doc->renderParagraph(static::DEFAULT, [['Telefon '.self::stringify($firstPastor->phone ?? ''), []]]);
                }

                $emails = [];
                foreach ($users as $pastor) {
                    if ($pastor->email) {
                        $emails[] = (string) $pastor->email;
                    }
                }
                if ($parish->email) {
                    $emails[] = (string) $parish->email;
                }
                $this->doc->renderParagraph(static::INDENT, [["E-Mail:\t".implode('<w:br/>', $emails), []]]);
                $homepage = (string) parse_url(self::stringify($city->homepage ?? ''), PHP_URL_HOST);
                $this->doc->renderParagraph(static::INDENT, [["Homepage:\t".$homepage, []]]);
                if ($parish->opening_hours) {
                    $assistant = self::stringify($parish->assistant ?? '');
                    $this->doc->renderParagraph(static::DEFAULT, [['Sprechzeiten im Pfarrbüro'.($assistant ? ', '.$assistant : ''), []]]);
                    $this->doc->renderParagraph(static::DEFAULT, [[$parish->opening_hours, []]], 1);
                }
            }

        }

    }

    /**
     * @param  mixed  $events  Events to render.
     * @param  mixed  $cities  Cities to render.
     * @return void
     */
    protected function renderEvents($events, $cities)
    {
        /** @var Collection<int, City> $cities */
        /** @var Collection<array-key, Collection<int, Occurence>> $events */
        if (! count($events)) {
            return;
        }

        $cityCount = count($cities);
        /** @var City|null $firstCity */
        $firstCity = $cities->first();
        $children = $firstCity?->children;
        $childCount = $children instanceof Collection ? $children->count() : 0;
        $cityCount = max(
            $cityCount == 1 ? ($firstCity?->parent_id !== null ? 2 : 1) : $cityCount,
            $childCount
        );

        $this->doc->renderParagraph(static::DEFAULT, [['Termine', static::BOLD]], 2);
        foreach ($events as $dayEvents) {
            /** @var Occurence|null $firstOccurence */
            $firstOccurence = $dayEvents->first();
            /** @var Carbon|null $dayStart */
            $dayStart = $firstOccurence?->start;
            $title = ($dayStart?->setTimeZone('Europe/Berlin')->isoFormat('dddd, DD. MMMM') ?? '').' ';
            /** @var Service|null $firstEvent */
            $firstEvent = $firstOccurence?->event;
            /** @var array<string, mixed> $liturgicalInfo */
            $liturgicalInfo = $firstEvent->liturgicalInfo ?? [];
            if ($liturgicalInfo['title'] ?? false) {
                $title .= ' - '.self::stringify($liturgicalInfo['title']).' - ';
            }
            $this->doc->renderParagraph(static::DEFAULT, [[trim($title), static::BOLD]]);
            foreach ($dayEvents as $event) {
                /** @var Service $service */
                $service = $event->event;
                $pastors = $service->pastors;
                $pastors = $pastors instanceof Collection ? $pastors : collect();
                $line = [
                    htmlspecialchars((string) $service->titleText(false, false)).(count(
                        $pastors
                    ) ? ' mit '.$this->getNameListLine($pastors) : ''),
                ];
                if ($service->descriptionText() != '') {
                    $line[] = (string) $service->descriptionText();
                }

                // figure out whether city name must be added to location string
                /** @var Location|null $location */
                $location = $event->getAttribute('location');
                if (($cityCount == 1) && (($location === null) || ($location->city_id == $firstCity?->id))) {
                    $line[] = self::stringify($service->locationText);
                } else {
                    $line[] = self::stringify($service->locationTextWithCity);
                }

                if ($service->event_class == 'service') {
                    $organists = $service->organists;
                    $organists = $organists instanceof Collection ? $organists : collect();
                    if ($s = $this->getNameListLine($organists)) {
                        $line[] = 'Musik: '.(string) $s;
                    }
                    if ($service->offeringGoal()) {
                        $line[] = 'Opfer: '.$service->offeringGoal();
                    }
                    $line[] = '';
                }
                $this->doc->renderParagraph(
                    static::INDENT,
                    [[(string) $service->timeText()."\t".implode("\n", $line), []]]
                );
            }
            $this->section->addTextBreak();
        }
    }

    /**
     * @param  mixed  $absences  Absences to render.
     * @return void
     */
    protected function renderAbsences($absences)
    {
        /** @var Collection<int, Absence> $absences */
        if (! count($absences)) {
            return;
        }
        /** @var Absence $absence */
        foreach ($absences as $absence) {
            $this->doc->renderParagraph(static::DEFAULT, [[$absence->descriptiveText, []]], 1);
        }
    }

    /**
     * @return string
     */
    /**
     * @param  mixed  $people  People to list.
     * @param  string  $and  Conjunction.
     * @return string
     */
    protected function getNameListLine($people, $and = ', ')
    {
        $names = collect();
        if (! is_iterable($people)) {
            return '';
        }
        foreach ($people as $person) {
            if (! $person instanceof User) {
                continue;
            }
            $names->push(NameService::fromUser($person)->format(NameService::TITLE_FIRST_LAST));
        }

        return self::stringify($names->join(', ', $and));
    }

    /**
     * Convert a mixed value to a string.
     *
     * @param  mixed  $value  Value to convert.
     * @return string String representation.
     */
    private static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '';
    }
}
