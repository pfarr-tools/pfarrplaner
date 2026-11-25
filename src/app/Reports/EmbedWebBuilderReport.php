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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 06.08.2019
 * Time: 09:01
 */

namespace App\Reports;

use App\Models\Calendar\Occurence;
use App\Models\Location;
use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View as ViewFacade;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class EmbedServiceTableReport
 */
class EmbedWebBuilderReport extends AbstractEmbedReport
{
    /**
     * @var string
     */
    public $title = 'Veranstaltungswerbung';

    /**
     * @var string
     */
    public $group = 'Website (Gemeindebaukasten)';

    /**
     * @var string
     */
    public $description = 'Erzeugt HTML-Code für Veranstaltungswerbung auf der  Website der Gemeinde';

    /**
     * @var string
     */
    public $icon = 'fa fa-file-code';

    protected $inertia = true;

    /**
     * @var array<string, string>
     */
    protected $validationRules = [
        'cities.*' => 'required|int|exists:cities,id',
        'locations.*' => 'nullable|int|exists:cities,id',
        'tags.*' => 'nullable|int|exists:tags,id',
        'eventClass' => 'required|string',
        'maxDays' => 'nullable|int',
        'limit' => 'nullable|int',
        'cors-origin' => 'required|url',
        'template' => 'required|string',
        'adChannelCode' => 'nullable|string',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getAvailableLayouts(): array
    {
        /** @var array<int, array<string, mixed>> $allConfiguredLayouts */
        $allConfiguredLayouts = config('webbuilder.layouts');

        $layouts = [];
        foreach ($allConfiguredLayouts as $configuredLayout) {
            $templates = 0;
            $existingTemplates = [];
            $layoutId = $configuredLayout['id'] ?? null;
            if (! is_string($layoutId) && ! is_int($layoutId)) {
                continue;
            }
            /** @var array<int|string, mixed> $configuredTemplates */
            $configuredTemplates = is_array($configuredLayout['templates'] ?? null) ? $configuredLayout['templates'] : [];
            foreach ($configuredTemplates as $key => $template) {
                if (ViewFacade::exists('embed.webbuilder.'.$layoutId.'.'.$key)) {
                    $existingTemplates[$key] = $template;
                    $templates++;
                }
            }
            if ($templates > 0) {
                $configuredLayout['templates'] = $existingTemplates;
                $layouts[] = $configuredLayout;
            }
        }

        return $layouts;
    }

    /**
     * @return Response
     */
    public function setup()
    {
        $cities = $this->authenticatedUser()->cities;
        $locations = Location::inCities($cities->pluck('id'))->get();
        $layouts = $this->getAvailableLayouts();

        $tags = Tag::all();

        return Inertia::render('Report/EmbedWebBuilder/Setup', compact('layouts', 'cities', 'locations', 'tags'));
    }

    /**
     * @return View|string
     */
    public function render(Request $request)
    {
        $data = $request->validate($this->getFullValidationRules($request, true));
        /** @var array{cities: array<int, int>, locations?: array<int, int>|null, tags?: array<int, int>|null, eventClass: string, maxDays?: int|null, limit?: int|null, 'cors-origin': string, template: string, adChannelCode?: string|null, options?: array<string, mixed>, report?: string} $data */
        $data['report'] = 'WebBuilder';
        $corsOrigin = $data['cors-origin'];
        unset($data['cors-origin']);
        if (isset($data['options'])) {
            $data = array_merge($data, $data['options']);
            unset($data['options']);
        }

        $url = URL::signedRoute('embed.report', $data).'&cors-origin='.urlencode($corsOrigin);
        $randomId = uniqid();

        $html = ViewFacade::make('reports.embedservicetable.render', compact('url', 'randomId'))->render();
        $title = 'HTML-Code für Veranstaltungswerbung erstellen';
        // return Inertia::render('Report/EmbedServiceTable/Render', compact('html', 'title'));

        return response()->json(['html' => $html, 'url' => $url, 'randomId' => $randomId]);
    }

    /**
     * @return array<string, string>
     */
    protected function getFullValidationRules(Request $request, bool $addOptions = false): array
    {
        $validationRules = $this->validationRules;
        if ($request->has('template')) {
            $template = $request->get('template');
            if (! is_string($template)) {
                return $validationRules;
            }
            $layouts = collect($this->getAvailableLayouts())->keyBy('id')->toArray();
            $templateParts = explode('.', $template);
            $thisLayout = $templateParts[0];
            $thisTemplate = $templateParts[1] ?? '';
            $fields = Arr::get($layouts, $thisLayout.'.templates.'.$thisTemplate.'.fields', []);
            foreach (is_array($fields) ? $fields : [] as $fieldKey => $field) {
                if (! is_string($fieldKey) || ! is_array($field)) {
                    continue;
                }
                $rules = $field['rules'] ?? false;
                if (is_string($rules) && $rules) {
                    $validationRules[($addOptions ? 'options.' : '').$fieldKey] = $rules;
                }
            }
        }

        return $validationRules;
    }

    /**
     * @return View
     */
    public function embed(Request $request)
    {
        if (! $request->hasValidSignatureWhileIgnoring(['cors-origin'])) {
            abort(401);
        }

        $validator = Validator::make($request->all(), $this->getFullValidationRules($request));
        if ($validator->fails()) {
            abort(404);
        }
        $data = $validator->validated();
        /** @var array{cities: array<int, int>, locations?: array<int, int>|null, tags?: array<int, int>|null, eventClass: string, maxDays?: int|null, limit?: int|null, template: string, adChannelCode?: string|null} $data */
        $data['maxDays'] ??= 730;

        $start = Carbon::now();
        $end = $start->copy()->addDays((int) $data['maxDays']);
        $randomId = uniqid();

        $eventQuery = Occurence::with('event')
            ->between($start, $end)
            ->whereHas('service', function ($query) use ($data) {
                $query
                    ->notHidden()
                    ->inCities($data['cities']);
                switch ($data['eventClass']) {
                    case '*':
                        $query->withoutGlobalScope(ServicesOnlyScope::class);
                        break;
                    case 'service':
                        break;
                    case 'event':
                        $query
                            ->withoutGlobalScope(ServicesOnlyScope::class)
                            ->where('event_class', $data['eventClass']);
                        break;
                    case 'baptismalService':
                        $query->where('baptism', true);
                        break;
                    case 'eucharist':
                        $query->where('eucharist', true);
                        break;
                    case 'baptism':
                        $query->whereHas('baptisms');
                        break;
                    case 'funeral':
                        $query->whereHas('funerals');
                        break;
                    case 'wedding':
                        $query->whereHas('weddings');
                        break;
                }

                if (count($data['locations'] ??= []) > 0) {
                    $query->whereHas('location', function ($query) use ($data) {
                        $query->whereIn('location_id', $data['locations']);
                    });
                }
                if (count($data['tags'] ??= []) > 0) {
                    $query->whereHas('tags', function ($query) use ($data) {
                        $query->whereIn('tags.id', $data['tags']);
                    });
                }
            })->orderBy('start');

        if ($data['limit'] ?? null) {
            $eventQuery->limit($data['limit']);
        }
        if ($data['adChannelCode'] ?? false) {
            $eventQuery->adRunningAt($data['adChannelCode'], $start);
        }

        $viewPath = ViewFacade::getFinder()->find('embed.webbuilder.'.$data['template']);

        return ViewFacade::file($viewPath, ['options' => $data, 'occurences' => $eventQuery->get(), 'randomId' => $randomId]);
    }
}
