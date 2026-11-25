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

use App\FileFormats\IDML;
use App\Models\Calendar\Day;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Services\LiturgyService;
use App\Tools\StringTool;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use PhpOffice\PhpWord\Exception\Exception;
use Throwable;

/**
 * Class BulletinBLReport
 */
class BulletinBLReport extends AbstractPDFDocumentReport
{
    /**
     * @var string
     */
    public $icon = 'fa fa-file';

    /**
     * @var string
     */
    public $title = 'Gemeindebrief (BL)';

    /**
     * @var string
     */
    public $group = 'Listen';

    /**
     * @var string
     */
    public $description = 'Gottesdiensttabelle für den Gemeindebrief';

    /**
     * @var string[]
     */
    public $formats = ['Balingen'];

    /**
     * @var array<int, list<float>>
     */
    protected $cols = [
        [21.6102362202077, -489.32677165354335, 7948.082677164984, -350.9017990755136, 7978.082677164984],
        [203.94291338581854, -522.6036220472524, 7948.082677164984, -384.17842519685036, 7978.082677164984],
        [352.9980314960625, -522.6036220472524, 7948.082677164984, -384.17842519685036, 7978.082677164984],
        [520.3214917499763, -489.32677165354335, 7948.082677164984, -350.9017990755136, 7978.082677164984],
        [697.137161041411, -518.8541317049825, 7948.082677164984, -384.1784251968842, 7978.082677164984],
    ];

    /**
     * @var array<string, float>
     */
    protected $tagWidth = [
        'abendmahl' => 2.399,
        'fahrdienst' => 4.276,
        'familien-gd' => 6.894,
        'infos-im-gemeindebrief' => 3.5,
        'kiki' => 10.991,
        'kirchenkaffee' => 6,
        'mit-posaunenchor' => 6.8,
        'musik-im-gd' => 3.014,
        'taufe' => 3.315,
    ];

    /**
     * @var array<string, array{float, float}>
     */
    protected $tagBaseCoords = [
        'abendmahl' => [-488.45233093682400, -596.13678866970800],
        'fahrdienst' => [-308.42721887539200, -45.45599600745520],
        'familien-gd' => [-188.20131811815900, -202.74274028199700],
        'infos-im-gemeindebrief' => [-535.5472370081530, -568.67671715498000],
        'kiki' => [-328.1407371703400, -74.60820972459380],
        'kirchenkaffee' => [-542.5998114214260, 202.65573939264900],
        'mit-posaunenchor' => [-425.5479722136610, -129.02780354377400],
        'musik-im-gd' => [-188.5429086087690, -205.03833543686800],
        'taufe' => [-353.4474062907450, -359.5165395946640],
    ];

    /** @var IDML */
    protected $idml;

    /**
     * Returns true, if active user has write rights for "Balingen" and thus activates the report
     */
    public function isActive(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->writableCities->pluck('name')->contains('Balingen');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function setup()
    {
        $maxDate = Day::orderBy('date', 'desc')->limit(1)->first();
        $user = $this->authenticatedUser();
        $cities = $user->cities;

        return $this->renderView('setup', ['maxDate' => $maxDate, 'cities' => $cities, 'formats' => $this->formats]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function configure(Request $request)
    {
        $request->validate(
            [
                'includeCities' => 'required',
                'start' => 'required|date|date_format:d.m.Y',
                'end' => 'required|date|date_format:d.m.Y',
            ]
        );

        $includeCities = $request->get('includeCities');
        $start = $request->get('start');
        $end = $request->get('end');

        $locations = Location::with('alternateLocation')->inCities($includeCities)->get();
        $user = $this->authenticatedUser();
        $presetsSetting = $user->getSetting('report.bulletinbl.locationpresets', '-1,-1,-1,-1');
        $presets = explode(',', is_string($presetsSetting) ? $presetsSetting : '-1,-1,-1,-1');

        return $this->renderView('configure', compact('start', 'end', 'includeCities', 'locations', 'presets'));
    }

    /**
     * @return mixed
     */
    public function empty(Request $request)
    {
        $locationIds = $request->get('locations');
        $locationIds = is_array($locationIds) ? $locationIds : [];
        $user = $this->authenticatedUser();
        $user->setSetting(
            'report.bulletinbl.locationpresets',
            implode(',', array_map(static fn (mixed $id): string => is_scalar($id) ? (string) $id : '', $locationIds))
        );
        $locations = Location::whereIn('id', $locationIds)->get();

        $start = $request->get('start');
        $end = $request->get('end');
        $start = is_string($start) ? $start : '';
        $end = is_string($end) ? $end : '';
        $includeCities = $request->get('includeCities');

        $empty = [];

        $dates = Service::select(DB::raw('DISTINCT DATE(date) AS day'))
            ->between(
                $this->requireParsedDate(Carbon::createFromFormat('d.m.Y H:i:s', $start.' 0:00:00')),
                $this->requireParsedDate(Carbon::createFromFormat('d.m.Y H:i:s', $end.' 23:59:59'))
            )->displayable()
            ->whereDoesntHave('funerals')
            ->orderBy('day', 'asc')
            ->get()
            ->pluck('day');

        foreach ($dates as $day) {
            $day = is_string($day) ? $day : '';
            foreach ($locations as $location) {
                $service = Service::where('location_id', $location->id)
                    ->displayable()
                    ->whereDate('date', $day)
                    ->get();
                if (! count($service)) {
                    // check if there is a replacement
                    $replacement = '';
                    if ($location->alternateLocation !== null) {
                        $service = Service::where('location_id', $location->alternateLocation->id)
                            ->displayable()
                            ->whereDate('date', $day)
                            ->get();
                        if (count($service)) {
                            $serviceInstance = $service->firstOrFail();
                            $replacement = 'Gottesdienst '.$serviceInstance->timeText(
                                true,
                                '.',
                                true
                            )."\r\n".$location->alternateLocation->general_location_name;
                        }
                    }

                    $empty[$day][$location->id] = $replacement;
                }
            }
        }

        $empty = $empty;
        $dayList = $dates->reject(function ($item) use ($empty) {
            if (! is_string($item)) {
                return false;
            }
            if (! isset($empty[$item])) {
                return false;
            }
            if (count($empty[$item]) != 4) {
                return false;
            }
            unset($empty[$item]);

            return true;
        });

        return $this->renderView(
            'empty',
            compact(
                'start',
                'end',
                'includeCities',
                'locations',
                'empty',
                'dayList',
                'locationIds'
            )
        );
    }

    /**
     * @return string|void
     *
     * @throws Exception
     * @throws Throwable
     */
    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'includeCities' => 'required',
                'start' => 'required|date|date_format:d.m.Y',
                'end' => 'required|date|date_format:d.m.Y',
                'locations' => 'required|array',
                'locations.*' => 'required|integer|exists:locations,id',
                'dayList' => 'required',
            ]
        );
        if (! is_array($data)) {
            $data = [];
        }

        $start = $request->get('start');
        $end = $request->get('end');
        $start = is_string($start) ? $start : '';
        $end = is_string($end) ? $end : '';
        $empty = $request->get('empty');

        $dayListInput = $data['dayList'];
        $dayList = is_array($dayListInput) ? $dayListInput : [];
        $locationsInput = $data['locations'];
        $locationIds = is_array($locationsInput)
            ? array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $locationsInput)
            : [];
        $serviceList = Service::whereIn(DB::raw('DATE(date)'), $dayList)
            ->whereIn('location_id', $locationIds)
            ->displayable()
            ->whereDoesntHave('funerals')
            ->whereDoesntHave('serviceGroups')
            ->ordered()
            ->get()
            ->groupBy(['location_id', 'key_date']);

        $includeCities = $request->get('includeCities');
        $dayIds = $days = $dayList;

        $locationOrder = array_flip($locationIds);
        $locations = Location::whereIn('id', $locationIds)
            ->get()
            ->sortBy(static fn (Location $location): int => $locationOrder[$location->id] ?? PHP_INT_MAX)
            ->values();

        $specialDays = Day::with('cities')
            ->whereHas(
                'cities',
                function ($query) use ($includeCities) {
                    $query->whereIn('city_id', $includeCities);
                }
            )
            ->where('date', '>=', $this->requireParsedDate(Carbon::createFromFormat('d.m.Y H:i:s', $start.' 0:00:00')))
            ->where('date', '<=', $this->requireParsedDate(Carbon::createFromFormat('d.m.Y H:i:s', $end.' 23:59:59')))
            ->orderBy('date', 'asc')
            ->get();

        $specialServices = [];
        foreach (ServiceGroup::all() as $group) {
            $svc = Service::between(
                $this->requireParsedDate(Carbon::createFromFormat('d.m.Y', $start)),
                $this->requireParsedDate(Carbon::createFromFormat('d.m.Y', $end)),
            )
                ->inCities($data['includeCities'])
                ->displayable()
                ->whereDoesntHave('funerals')
                ->whereHas('serviceGroups')
                ->ordered()
                ->get();
            if (count($svc)) {
                $specialServices[$group->name]['services'] = $svc;
            }
        }

        // check for same times as locations
        foreach ($specialServices as $group => $data) {
            $services = $data['services'];
            $firstService = $services->firstOrFail();
            $time = $firstService->timeText(true, '.', true);
            $sameTime = true;
            $location = $firstService->locationText();
            $sameLocation = true;

            /** @var Service $service */
            foreach ($services as $service) {
                $sameTime = $sameTime && ($time == $service->timeText(true, '.', true));
                $sameLocation = $sameLocation && ($location == $service->locationText());
            }

            $specialServices[$group]['options'] = compact('sameLocation', 'sameTime', 'location', 'time', 'group');
        }

        $emptyInput = $request->get('empty');
        $empty = is_array($emptyInput) ? $emptyInput : [];

        $this->idml = new IDML(base_path('assets/idml/ev3.idml'), 'ev3');
        $spreadCode = '';

        // render rectangles for table header
        $y = -8235.27722395432;
        for ($col = 0; $col < 5; $col++) {
            $spreadCode .= view(
                'reports.bulletinbl.idml.rectangle',
                [
                    'col' => $this->cols[$col],
                    'y' => $y,
                    'uid' => $this->idml->getUID('th_rect_'.$col.'_'),
                    'fillColor' => 'Color/lila',
                ]
            );
        }

        $spreadCode .= view('reports.bulletinbl.idml.tableheader')->render();

        $headerStories = [
            'u3a5d0',
            'u3a4a2',
            'u3a5b8',
            'u3ab22',
            'u3a98a',
            'u3a9a2',
        ];

        $locationsTemp = clone $locations;
        $ctr = 0;
        foreach ($headerStories as $uid) {
            if ($ctr == 0) {
                $text = 'Datum';
            } elseif ($ctr == 5) {
                $text = 'Besondere Gottesdienste';
            } else {
                $location = $locationsTemp->splice(0, 1)->first();
                $text = $location->name ?? '';
                if ($location?->default_time) {
                    $text .= (strlen($text) < 28) ? $this->idml->BR() : ' - ';
                    $text .= StringTool::timeString($location->default_time, true, '.');
                }
            }
            $this->idml->renderToFile(
                'Stories/Story_'.$uid.'.xml',
                view('reports.bulletinbl.idml.tableheaderstory', compact('text', 'uid'))
            );
            $ctr++;
        }

        $y = -8193.8727735606200;

        $stories = [];
        $rowCtr = 0;
        foreach ($days as $day) {
            $rowCtr++;
            $day = is_string($day) ? $day : '';
            $liturgy = LiturgyService::getLiturgyInfoByDate($day);
            $liturgyName = '';
            if (is_array($liturgy) && isset($liturgy['Bezeichnung']) && is_string($liturgy['Bezeichnung'])) {
                $liturgyName = $liturgy['Bezeichnung'];
            }

            if ($rowCtr % 2 == 0) {
                // even rows: render rectangles
                for ($col = 0; $col < 5; $col++) {
                    $spreadCode .= view(
                        'reports.bulletinbl.idml.rectangle',
                        [
                            'col' => $this->cols[$col],
                            'y' => $y,
                            'uid' => $this->idml->getUID('rect'),
                            'fillColor' => 'Tint/lila 11%25',
                        ]
                    );
                }
            }

            // build the story for the day title
            $this->storyFrame(
                $stories,
                $storyId,
                $spreadCode,
                $y,
                0,
                Carbon::parse($day)->format('d.m.Y').$this->idml->BR().$liturgyName
            );

            $col = 0;
            foreach ($locations as $location) {
                $col++;
                $text = '';
                $service = null;
                $dayServices = $serviceList[$location->id][$day] ?? null;
                if ($dayServices instanceof Collection && $dayServices->count() > 0) {
                    $candidate = $dayServices->first();
                    if ($candidate instanceof Service) {
                        $service = $candidate;
                        $defaultTime = StringTool::timeString($location->default_time ?? '', true, '.');
                        $text = $service->participantsText('P', false, false, '|')
                            .((is_object($service->location)) && ($defaultTime != $service->timeText(true, '.')) ? ' '.$defaultTime : '')
                            .$defaultTime
                            .$this->idml->BR()
                            .$service->description;
                    }
                } else {
                    $emptyForLocation = $empty[$location->id] ?? null;
                    if (is_array($emptyForLocation) && isset($emptyForLocation[$day]) && is_string($emptyForLocation[$day])) {
                        $text = strtr($emptyForLocation[$day], ["\n" => $this->idml->BR(), "\r" => '']);
                    }
                }
                $this->storyFrame($stories, $storyId, $spreadCode, $y, $col, $text);

                if ($service instanceof Service) {
                    $tags = [];
                    $serviceTags = $service->tags;
                    if ($serviceTags instanceof Collection) {
                        foreach ($serviceTags->pluck('code')->toArray() as $code) {
                            if (is_string($code)) {
                                $tags[] = $code;
                            }
                        }
                    }
                    if ($service->eucharist) {
                        $tags[] = 'abendmahl';
                    }
                    $baptisms = $service->baptisms;
                    if ($service->baptism || (is_countable($baptisms) && count($baptisms) > 0)) {
                        $tags[] = 'taufe';
                    }
                    if ($service->cc) {
                        $tags[] = 'kiki';
                    }
                    $spreadCode .= $this->setTags($col, $rowCtr, $tags);
                }
            }

            $y += 34.4685039370106;

            if ($rowCtr > 1) {
                for ($col = 0; $col < 5; $col++) {
                    $spreadCode .= view(
                        'reports.bulletinbl.idml.line',
                        [
                            'col' => $this->cols[$col],
                            'y' => $y - 5.807207480315,
                            'uid' => $this->idml->getUID('line'),
                        ]
                    );
                }
            }
        }

        // include symbols table
        $spreadCode .= view('reports.bulletinbl.idml.symbolstable')->render();

        // render spread file
        $this->idml->renderToFile(
            'Spreads/Spread_u35ee1.xml',
            view('reports.bulletinbl.idml.spread', ['spreadObjects' => $spreadCode])->render()
        );
        // $this->idml->forceDownload('Spreads/Spread_u35ee1.xml', 'text/xml');

        $this->idml->renderToFile(
            'Stories/Story_u3a9b9.xml',
            view('reports.bulletinbl.idml.specialstory', compact('specialServices'))
        );

        // render designmap
        $this->idml->renderToFile('designmap.xml', view('reports.bulletinbl.idml.designmap', compact('stories')));

        $this->idml->build();
    }

    /**
     * Render one story frame into the IDML document.
     *
     * @param  list<string>  $stories  Story identifiers collected so far.
     * @param  string  $storyId  Identifier of the new story, assigned by this method.
     * @param  string  $spreadCode  Spread XML code, extended by this method.
     * @param  float  $y  Vertical coordinate.
     * @param  int  $col  Column index.
     * @param  string  $text  Story text.
     * @param  string  $view  View used to render the story.
     * @return string The assigned story identifier.
     *
     * @throws Throwable
     */
    protected function storyFrame(
        &$stories,
        &$storyId,
        &$spreadCode,
        $y,
        $col,
        $text,
        $view = 'reports.bulletinbl.idml.story'
    ) {
        $storyId = $this->idml->getUID('story');
        $stories[] = $storyId;
        $storyCode = ViewFacade::file(ViewFacade::getFinder()->find($view), ['uid' => $storyId, 'text' => $text])->render();
        $this->idml->renderToFile('Stories/Story_'.$storyId.'.xml', $storyCode);

        // build a textframe for this story
        $spreadCode .= view(
            'reports.bulletinbl.idml.textframe',
            [
                'col' => $this->cols[$col],
                'y' => $y,
                'uid' => $this->idml->getUID('textframe_'.$col),
                'parentStory' => $storyId,
            ]
        )->render();

        return $storyId;
    }

    /**
     * Render the tag graphics for one service cell into the IDML spread.
     *
     * @param  int  $col  Column index.
     * @param  int  $row  Row index.
     * @param  list<string>  $tags  Tag names to render.
     * @return string Generated IDML code.
     */
    protected function setTags(int $col, int $row, array $tags)
    {
        $offset = 0;
        $code = '';

        // calculate column offset
        $colOffset = $this->cols[$col][0] - $this->cols[1][0];
        if ($col == 3) {
            $colOffset += 33.12469843;
        }

        foreach ($tags as $tag) {
            $coords = $this->tagBaseCoords[$tag];
            $x = str_replace(
                ',',
                '.',
                (string) ($coords[0] - IDML::mmToPoint($this->tagWidth[$tag]) - $offset + $colOffset)
            );
            $y = str_replace(',', '.', (string) ($coords[1] + (($row - 1) * 34.4685039370106)));
            $uid = $this->idml->getUID('tag_'.$tag);
            $tagView = 'reports.bulletinbl.idml.tags.'.$tag;
            $code .= ViewFacade::file(ViewFacade::getFinder()->find($tagView), compact('x', 'y', 'uid'))->render();

            $offset += IDML::mmToPoint($this->tagWidth[$tag] + 1);
        }

        return $code;
    }
}
