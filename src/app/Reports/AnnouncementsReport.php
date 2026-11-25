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

use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\ReadingItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Announcements;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\FileNameService;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;

/**
 * Class AnnouncementsReport
 */
class AnnouncementsReport extends AbstractWordDocumentReport
{
    protected const BOLD = ['bold' => true];

    protected const UNDERLINE = ['underline' => Font::UNDERLINE_SINGLE];

    protected const BOLD_UNDERLINE = ['bold' => true, 'underline' => Font::UNDERLINE_SINGLE];

    protected const INDENT = 'Bekanntgaben';

    protected const NO_INDENT = 'Bekanntgaben ohne Einrückung';

    public const FILE_TITLE = 'Bekanntgaben';

    public const FILE_SIGNATURE = '91.8';

    /** @var array<string, mixed> */
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

    /** @var bool */
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
     * @return Response
     */
    public function setup()
    {
        $user = $this->authenticatedUser();
        $cities = $user->cities->load('parent');

        return Inertia::render('Report/Announcements/Setup', compact('cities'));
    }

    /**
     * @return JsonResponse
     */
    public function services(Request $request)
    {
        $data = $request->validate(['city' => 'required|int']);
        /** @var array{city: int} $data */
        $city = City::findOrFail((int) $data['city']);
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
                'name' => $service->trueDate()?->format('d.m.Y').' '.$service->timeText().', '.$service->locationText(),
            ];
        }

        return response()->json([
            'services' => $services,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function lastServiceDays(Request $request)
    {
        $data = $request->validate([
            'city' => 'required|int|exists:cities,id',
            'service' => 'required|int|exists:services,id',
        ]);
        /** @var array{city: int, service: int} $data */
        $city = City::findOrFail((int) $data['city']);
        $service = Service::findOrFail((int) $data['service']);

        $date = $service->trueDate();
        abort_unless($date instanceof Carbon, 404);

        $days = Service::select(DB::raw('DISTINCT DATE(date) AS day'))
            ->endingAt($date->copy()->subDays(1))
            ->regularForCity($city)
            ->orderBy('day', 'desc')
            ->limit(10)
            ->get()
            ->pluck('day');

        $lastServiceDays = [];
        foreach ($days as $day) {
            if (! is_string($day)) {
                continue;
            }
            $lastServiceDays[] = ['id' => $day, 'name' => Carbon::parse($day)->isoFormat('dddd, DD. MMMM YYYY')];
        }

        return response()->json($lastServiceDays);
    }

    /**
     * @return JsonResponse
     */
    public function offerings(Request $request)
    {
        $data = $request->validate([
            'city' => 'required|int|exists:cities,id',
            'day' => 'required|date',
        ]);
        /** @var array{city: int, day: string} $data */
        $services = Service::whereDate('date', Carbon::parse($data['day']))
            ->where('city_id', $data['city'])
            ->get();

        $amount = 0;
        foreach ($services as $service) {
            $amount += (float) strtr((string) $service->offering_amount, [' ' => '', '€' => '']);
        }

        return response()->json($amount);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function auto(Request $request)
    {
        if (! $request->has('service')) {
            abort(404);
        }
        $serviceId = $request->get('service');
        if (! is_numeric($serviceId)) {
            abort(404);
        }
        $service = Service::findOrFail((int) $serviceId);
        $lastService = Service::where('offering_amount', '!=', '')
            ->endingAt($service->dateTime)
            ->orderedDesc()
            ->first();
        abort_unless($lastService instanceof Service, 404);

        return $this->renderReport([
            'lastService' => $lastService->date?->format('d.m.Y'),
            'offerings' => $lastService->offering_amount,
            'offering_text' => $service->offering_text,
            'service' => $service->id,
            'excludeRegularWeekly' => true,
            'city' => $service->city_id,
        ]);
    }

    /**
     * @param  mixed  $data  Report request data.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderReport($data)
    {
        /** @var array{service: int|null, city: int|null, lastService: string|null, offerings: string|int|float|null, excludeRegularWeekly?: bool|null} $data */
        $service = Service::findOrFail((int) $data['service']);
        $city = City::findOrFail((int) $data['city']);

        // is this part of an org?
        $localCity = $city;
        if ($city->parent_id) {
            $city = $city->parent;
        }
        abort_unless($city instanceof City, 404);

        $lastService = $data['lastService'];
        $offerings = $data['offerings'];

        $announcements = new Announcements($service, $city, $data['excludeRegularWeekly'] ?? false);
        $baptisms = $announcements->getBaptisms();
        $funerals = $announcements->getFunerals();
        $weddings = $announcements->getWeddings();
        $events = $announcements->getEvents();
        $featuredEvents = $announcements->getFeaturedEvents();

        // //////////////////////////////////////////////////////////////////////////////////////////////

        $this->doc->getPhpWord()?->getSettings()->setBookFoldPrinting(true);

        $liturgicalTitle = $service->liturgicalInfo['title'] ?? null;
        $textRun = $this->doc->getSection()?->addTextRun('Bekanntgaben');
        $textRun?->addText(
            $service->trueDate()?->isoFormat('DD. MMMM YYYY')
            .(is_string($liturgicalTitle) && $liturgicalTitle ? ' - '.$liturgicalTitle : ''),
            ['bold' => true]
        );

        $textRun = $this->doc->getSection()?->addTextRun('Bekanntgaben');
        $textRun?->addText($service->timeText().' '.$service->locationText());

        $this->doc->getSection()?->addTextBreak();

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

        if ($service->offeringGoal()) {
            $this->doc->renderParagraph(self::INDENT, [
                ["Opfer:\t{$service->offeringGoal()}", []],
            ]);
        }

        $this->renderLiturgy($service);

        $this->renderReadings($service);

        $this->doc->renderParagraph(self::NO_INDENT, [
            ['Abkündigungen', self::BOLD_UNDERLINE],
        ]);
        $this->doc->getSection()?->addTextBreak();

        // ANNOUNCEMENTS

        $announcements->setUseTabs(true);
        // dd($announcements->render($lastService, $offerings, null, "\n", ['last_offerings', 'final_song']));
        $announcements->render($lastService, $offerings, function ($key, $items) {
            /** @var list<string> $items */
            if ($key == 'events') {
                foreach ($items as $item) {
                    if (Str::contains($item, "\t")) {
                        $this->doc->renderParagraph(self::INDENT, [[$item, []]]);
                    } else {
                        $this->doc->renderParagraph(self::NO_INDENT, [[$item, ['bold' => true]]]);
                    }
                }
            } else {
                $this->renderParagraphArray($items, self::NO_INDENT);
            }
            $this->doc->getSection()?->addTextBreak();
        });

        if (! empty($service->konfiapp_event_qr)) {
            $this->renderKonfiAppQR($service);
        }

        return $this->sendToBrowser(
            FileNameService::make(
                self::FILE_TITLE,
                '.docx',
                static::FILE_SIGNATURE,
                $service->trueDate()
            )
        );
    }

    /**
     * @return string|void
     *
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
     * @return string
     */
    /**
     * @param  mixed  $s  Name to render.
     * @return mixed
     */
    protected function renderName($s)
    {
        if (! is_string($s)) {
            return $s;
        }
        if (strpos($s, ',') !== false) {
            $t = explode(',', $s);
            $s = trim($t[1]).' '.trim($t[0]);
        }

        return $s;
    }

    /**
     * @param  array<int, mixed>|Collection<int, mixed>  $people  People to list.
     * @param  string  $and  Conjunction for the last item.
     * @return string
     */
    protected function getNameListLine($people, $and = ', ')
    {
        $names = collect();
        foreach ($people as $person) {
            if (! $person instanceof User) {
                continue;
            }
            $names->push(NameService::fromUser($person)->format(NameService::TITLE_FIRST_LAST));
        }

        /** @var string $line */
        $line = $names->join(', ', $and);

        return $line;
    }

    /**
     * @param  mixed  $ministry  Ministry name.
     * @param  mixed  $people  People in the ministry.
     * @return void
     */
    protected function renderMinistryLine($ministry, $people)
    {
        /** @var string $ministry */
        /** @var array<int, mixed>|Collection<int, mixed> $people */
        $this->doc->renderParagraph(self::INDENT, [
            [$ministry.":\t".$this->getNameListLine($people), []],
        ]);
    }

    /**
     * @return mixed
     */
    protected function renderLiturgy(Service $service)
    {
        if (! count($service->liturgyBlocks)) {
            return;
        }
        $this->doc->getSection()?->addTextBreak(1);
        foreach ($service->liturgyBlocks as $block) {
            $this->doc->renderParagraph(self::NO_INDENT, [
                [$block->title, ['bold' => true]],
            ]);
            foreach ($block->items as $item) {
                $title = '';
                $verses = $item->data['verses'] ?? '';
                $reference = $item->data['reference'] ?? '';
                if ($item->data_type == 'song') {
                    $helper = new SongItemHelper($item);
                    $title = ': '.$helper->getTitleText(
                    ).(is_string($verses) && $verses ? ', '.$verses : '');
                }
                if ($item->data_type == 'psalm') {
                    $helper = new PsalmItemHelper($item);
                    $title = ': '.$helper->getTitleText();
                }
                if ($item->data_type == 'reading') {
                    $title = ': '.(is_string($reference) ? $reference : '');
                }

                $this->doc->renderParagraph(self::NO_INDENT, [
                    [Str::replace('&', '&amp;', $item->title.trim($title)), []],
                ]);
            }
        }
    }

    /**
     * @return void
     */
    protected function renderReadings(Service $service)
    {
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($item->data_type == 'reading') {
                    $reference = $item->data['reference'] ?? null;
                    if (is_string($reference) && $reference) {
                        $title = 'Schriftlesung aus '.$reference;

                        /** @var ReadingItemHelper $helper */
                        $helper = $item->getHelper();
                        $helper->renderToWordDocument($this->doc, true, true, $title, 1);

                        $this->doc->renderParagraph(self::NO_INDENT, [], 1);
                        $this->doc->renderParagraph(self::NO_INDENT, [
                            ['Der Herr segne sein Wort an uns. Amen.', ['italic' => true]],
                        ], 1);
                    }
                }
            }
        }
    }

    /**
     * @param  array<int, mixed>|Collection<int, mixed>  $paragraphs  Paragraphs to render.
     * @param  string  $pStyle  Paragraph style.
     * @return void
     */
    protected function renderParagraphArray($paragraphs, $pStyle = self::NO_INDENT)
    {
        foreach ($paragraphs as $paragraph) {
            if ($paragraph == '') {
                $this->doc->getSection()?->addTextBreak();
            } else {
                $this->doc->renderParagraph($pStyle, [[$paragraph, [], true]]);
            }
        }
    }

    /**
     * @return void
     */
    public function renderKonfiAppQR(Service $service)
    {
        $this->doc->getSection()?->addTextBreak(1);
        $this->doc->getSection()?->addTitle('QR-Code für die KonfiApp', 1);
        $city = $service->city;
        if (! $city instanceof City) {
            return;
        }
        $types = KonfiAppIntegration::get($city)->listEventTypes();
        $text = '';
        foreach ($types as $type) {
            if (! is_object($type)) {
                continue;
            }
            if (($type->id ?? null) == $service->konfiapp_event_type) {
                $points = $type->punktzahl ?? '';
                $name = $type->name ?? '';
                $text = (is_scalar($points) ? (string) $points : '').' '
                    .($points == 1 ? 'Punkt' : 'Punkte').' in der Kategorie "'
                    .(is_scalar($name) ? (string) $name : '').'". ';
            }
        }
        $this->doc->renderNormalText(
            $text.'Gültig nur am '.$service->trueDate()?->isoFormat('dddd, DD. MMMM YYYY')
            .' von '.$service->trueDate()?->setTimezone('Europe/Berlin')->format('H:i')
            .' bis '.$service->trueDate()?->setTimezone('Europe/Berlin')->copy()->addHours(3)->format('H:i').' Uhr.',
            ['size' => 8],
            true
        );
        $this->doc->getSection()?->addImage(
            route('qrcode', $service->konfiapp_event_qr),
            ['width' => Converter::cmToPoint(4)]
        );
    }
}
