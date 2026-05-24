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

namespace App\Http\Controllers;

use App\Liturgy\LiturgySheets\AbstractLiturgySheet;
use App\Liturgy\LiturgySheets\LiturgySheets;
use App\Liturgy\Replacement\Replacement;
use App\Liturgy\Resources\BlockResourceCollection;
use App\Models\Liturgy\Block;
use App\Models\Liturgy\Item;
use App\Models\LiturgyInfo;
use App\Models\People\Participant;
use App\Models\Sermon;
use App\Models\Service;
use App\Services\LiturgyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LiturgyEditorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['download']);
    }

    public function editor(Request $request, Service $service)
    {
        $service->load('liturgyBlocks', 'sermon');
        $liturgySheets = LiturgySheets::all();
        $services = [];
        $autoFocusBlock = $request->get('autoFocusBlock', null);
        $autoFocusItem = $request->get('autoFocusItem', null);
        $ministries = $this->getAvailableMinistries();
        $markers = Replacement::getList();
        return Inertia::render(
            'liturgyEditor',
            compact('service', 'liturgySheets', 'autoFocusBlock', 'autoFocusItem', 'ministries', 'markers')
        );
    }

    public function save(Request $request, Service $service)
    {
        foreach ($request->all() as $block) {
            Block::find($block['id'])->update(['sortable' => $block['sortable']]);
            foreach ($block['items'] as $item) {
                Item::find($item['id'])->update(
                    [
                        'sortable' => $item['sortable'],
                        'liturgy_block_id' => $block['id'],
                    ]
                );
            }
        }
        return redirect()->back();
    }

    /**
     * Download a rendered LiturgySheet
     * @param Request $request
     * @param Service $service
     * @param $key
     * @return \Illuminate\Http\RedirectResponse
     */
    public function download(Request $request, Service $service, $key)
    {
        $class = 'App\\Liturgy\\LiturgySheets\\' . $key . 'LiturgySheet';
        if (!class_exists($class)) {
            abort(404);
        }

        /** @var AbstractLiturgySheet $sheet */
        $sheet = new $class();
        if ((null !== $sheet->getConfigurationPage()) && (!$request->has('config'))) {
            return redirect()->route('liturgy.configure', ['service' => $service->slug, 'key' => $key]);
        }

        if (null !== $sheet->getConfigurationPage()) {
            $sheet->setConfiguration($request->get('config', []));
        }
        return $sheet->render($service);
    }

    /**
     * Show the configuration page for a LiturgySheet
     * @param Request $request
     * @param Service $service
     * @param $key
     * @return \Inertia\Response
     */
    public function configureLiturgySheet(Request $request, Service $service, $key)
    {
        $class = 'App\\Liturgy\\LiturgySheets\\' . $key . 'LiturgySheet';
        if (!class_exists($class)) {
            abort(404);
        }
        /** @var AbstractLiturgySheet $sheet */
        $sheet = new $class();

        $sheetConfig = [
            'key' => $sheet->getKey(),
            'title' => $sheet->getTitle(),
            'fileTitle' => $sheet->getFileTitle(),
        ];

        $config = $sheet->getConfiguration();

        return Inertia::render($sheet->getConfigurationPage(), compact('service', 'sheetConfig', 'config'));
    }

    /**
     * Return content data for a liturgy sheet dialog.
     *
     * @param Service $service
     * @param string $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function liturgySheetDialogData(Service $service, string $key)
    {
        $class = 'App\\Liturgy\\LiturgySheets\\' . $key . 'LiturgySheet';
        if (!class_exists($class)) {
            abort(404);
        }

        /** @var AbstractLiturgySheet $sheet */
        $sheet = new $class();

        return response()->json($sheet->getDialogData($service));
    }

    public function sources(Service $service)
    {
        $services1 = Service::with([])
            ->isNotAgenda()
            ->writable()
            ->whereHas('liturgyBlocks')
            ->limit(50)->get(['id', 'title']);
        $services2 = Service::with([])
            ->isNotAgenda()
            ->userParticipates(Auth::user(), 'P')
            ->whereHas('liturgyBlocks')
            ->get();
        $services3 = $services1->merge($services2)->unique();
        $services = [];
        foreach ($services3 as $service) {
            $service->load(['day', 'location']);
            if (!$service->day == null)
            $services[$service->dateTime->timestamp . $service->id] = [
                'id' => $service->id,
                'text' => trim(($service->day ? $service->date->format('d.m.Y') : '').' '.$service->timeText().' '.$service->titleText().' ('.$service->locationText.')'),
            ];
        }
        krsort($services);
        $agendas1 = Service::with(['day'])->isTemplate()->get();
        $agendas = [];
        foreach ($agendas1 as $agenda) {
            $agendas[] = [
                'id' => $agenda->id,
                'text' => $agenda->title.($agenda->source ? ' ('.$agenda->source.')' : '')
            ];
        }
        return response()->json(compact('services', 'agendas'));
    }

    public function sermons()
    {
        $sermonIds = Service::whereNotNull('sermon_id')
            ->userParticipates(Auth::user(), 'P')
            ->orderedDesc()
            ->get(['sermon_id'])->pluck('sermon_id')->unique();

        $sermons = Sermon::whereIn('id', $sermonIds)->get(['id', 'title', 'subtitle', 'reference']);

        return response()->json($sermons);
    }

    public function import(Service $service, Service $source)
    {
        $ct = count($service->liturgyBlocks);
        foreach ($source->liturgyBlocks as $sourceBlock) {
            $ct++;
            unset($sourceBlock->id);
            $newBlock = $sourceBlock->replicate();
            $newBlock->sortable = $ct;
            $newBlock->service_id = $service->id;
            $newBlock->save();
            $itemCtr = 0;
            foreach ($sourceBlock->items as $sourceItem) {
                $itemCtr++;
                $newItem = $sourceItem->replicate();
                $newItem->liturgy_block_id = $newBlock->id;
                $newItem->sortable = $itemCtr;
                $newItem->save();
            }
        }
        return redirect()->route('liturgy.editor', $service->slug);
    }

    public function recipients(Service $service)
    {
        $recipients = [];
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                foreach ($item->recipients() as $recipient) {
                    $recipients[$recipient] = ['id' => $recipient, 'name' => $recipient];
                }
            }
        }
        ksort($recipients);
        $recipients = array_values($recipients);
        return response()->json(compact('recipients'));
    }

    /**
     * @param $reqMinistries
     * @return array
     */
    protected function getAvailableMinistries()
    {
        $ministries = [];
        foreach (Participant::all()->pluck('category')->unique() as $ministry) {
            switch ($ministry) {
                case 'P':
                case 'O':
                case 'M':
                case 'A':
                    break;
                default:
                    $ministries[$ministry] = $ministry;
            }
        }
        return $ministries;
    }




}
