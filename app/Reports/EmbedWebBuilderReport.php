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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 06.08.2019
 * Time: 09:01
 */

namespace App\Reports;


use App\Models\Calendar\Occurence;
use App\Models\Location;
use App\Models\Service;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Inertia\Inertia;
use Illuminate\Support\Facades\URL;

/**
 * Class EmbedServiceTableReport
 * @package App\Reports
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

    protected $validationRules = [
        'cities.*' => 'required|int|exists:cities,id',
        'locations.*' => 'nullable|int|exists:cities,id',
        'tags.*' => 'nullable|string|exists:tags,code',
        'eventClass' => 'required|string',
        'maxDays' => 'nullable|int',
        'limit' => 'nullable|int',
        'cors-origin' => 'required|url',
        'template' => 'required|string',
        'maxBaptisms' => 'nullable|int',
    ];

    protected function getAvailableLayouts(): array {
        $allConfiguredLayouts = config('webbuilder.layouts');

        $layouts = [];
        foreach ($allConfiguredLayouts as $configuredLayout) {
            $templates = 0;
            $existingTemplates = [];
            foreach ($configuredLayout['templates'] ?? [] as $key => $template) {
                if (ViewFacade::exists('embed.webbuilder.'.$configuredLayout['id'].'.'.$key)) {
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
     * @return \Inertia\Response
     */
    public function setup()
    {
        $cities = Auth::user()->cities;
        $locations = Location::whereIn('city_id', $cities->pluck('id'))->get();
        $layouts = $this->getAvailableLayouts();

        $tags = Tag::all();
        return Inertia::render('Report/EmbedWebBuilder/Setup', compact('layouts', 'cities', 'locations', 'tags'));
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|string
     */
    public function render(Request $request)
    {
        $data = $request->validate($this->validationRules);

        $data['report'] = 'WebBuilder';
        $corsOrigin = $data['cors-origin'];
        unset($data['cors-origin']);

        $url = URL::signedRoute('embed.report', $data).'&cors-origin='.urlencode($corsOrigin);
        $randomId = uniqid();

        $html = ViewFacade::make('reports.embedservicetable.render', compact('url', 'randomId'))->render();
        $title= 'HTML-Code für Veranstaltungswerbung erstellen';
        return Inertia::render('Report/EmbedServiceTable/Render', compact('html', 'title'));
    }


    public function embed(Request $request)
    {
        if (! $request->hasValidSignatureWhileIgnoring(['cors-origin'])) {
            abort(401);
        }

        $validator = Validator::make($request->all(), $this->validationRules);
        if ($validator->fails()) abort(404);
        $data = $validator->validated();

        $data['maxDays'] ??= 730;

        $start = Carbon::now();
        $end = $start->copy()->addDays((int)$data['maxDays']);
        $randomId = uniqid();


        $eventQuery = Occurence::with('event')
            ->between($start, $end)
            ->whereHas('service', function ($query) use ($data) {
                $query->whereIn('city_id', $data['cities']);
                switch($data['eventClass']) {
                    case 'service':
                    case 'event':
                        $query->where('event_class', $data['eventClass']);
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
                       $query->whereIn('tag_id', $data['tags']);
                    });
                }
            })->orderBy('start');

        if ($data['limit'] ?? null) $eventQuery->limit($data['limit']);

        return view('embed.webbuilder.'.$data['template'], ['options' => $data, 'occurences' => $eventQuery->get(), 'randomId' => $randomId]);
    }

}
