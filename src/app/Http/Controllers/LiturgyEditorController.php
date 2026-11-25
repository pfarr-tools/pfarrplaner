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

namespace App\Http\Controllers;

use App\Liturgy\LiturgySheets\AbstractLiturgySheet;
use App\Liturgy\LiturgySheets\LiturgySheets;
use App\Liturgy\Replacement\Replacement;
use App\Models\People\Participant;
use App\Models\People\User;
use App\Models\Sermon;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LiturgyEditorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['download']);
    }

    /**
     * Show the liturgy editor for a service.
     *
     * @param  Request  $request  Current request containing optional auto-focus hints.
     * @param  Service  $service  Service whose liturgy is edited.
     * @return Response Inertia response rendering the liturgy editor.
     */
    public function editor(Request $request, Service $service): Response
    {
        Gate::authorize('view', $service);
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

    /**
     * Persist the order of liturgy blocks and their items.
     *
     * @param  Request  $request  Request containing the reordered blocks and items.
     * @param  Service  $service  Service whose liturgy is saved.
     * @return RedirectResponse Redirect back to the previous page.
     */
    public function save(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);
        /** @var array<array{id: int|string, sortable: int, items: array<array{id: int|string, sortable: int}>}> $blocks */
        $blocks = $request->all();
        foreach ($blocks as $block) {
            $currentBlock = $service->liturgyBlocks()->whereKey($block['id'])->firstOrFail();
            $currentBlock->update(['sortable' => $block['sortable']]);
            foreach ($block['items'] as $item) {
                $currentBlock->items()->whereKey($item['id'])->firstOrFail()->update(
                    [
                        'sortable' => $item['sortable'],
                        'liturgy_block_id' => $currentBlock->id,
                    ]
                );
            }
        }

        return redirect()->back();
    }

    /**
     * Download a rendered LiturgySheet.
     *
     * @param  Request  $request  Current request used for signature checks and sheet configuration.
     * @param  Service  $service  Service whose liturgy is rendered.
     * @param  string  $key  Key identifying the liturgy sheet class.
     * @return mixed Download response produced by the sheet or a redirect to its configuration page.
     */
    public function download(Request $request, Service $service, string $key)
    {
        if (! Auth::check() && ! $request->hasValidSignature()) {
            abort(401);
        }

        $class = 'App\\Liturgy\\LiturgySheets\\'.$key.'LiturgySheet';
        if (! class_exists($class)) {
            abort(404);
        }

        /** @var AbstractLiturgySheet $sheet */
        $sheet = new $class;
        if (($sheet->getConfigurationPage() !== null) && (! $request->has('config'))) {
            return redirect()->route('liturgy.configure', ['service' => $service->slug, 'key' => $key]);
        }

        if ($sheet->getConfigurationPage() !== null) {
            $config = $request->get('config', []);
            abort_unless(is_array($config), 400);
            /** @var array<string, mixed> $config */
            $sheet->setConfiguration($config);
        }

        return $sheet->render($service);
    }

    /**
     * Show the configuration page for a LiturgySheet.
     *
     * @param  Request  $request  Current request.
     * @param  Service  $service  Service whose liturgy sheet is configured.
     * @param  string  $key  Key identifying the liturgy sheet class.
     * @return Response Inertia response rendering the configuration page.
     */
    public function configureLiturgySheet(Request $request, Service $service, string $key): Response
    {
        Gate::authorize('view', $service);
        $class = 'App\\Liturgy\\LiturgySheets\\'.$key.'LiturgySheet';
        if (! class_exists($class)) {
            abort(404);
        }
        /** @var AbstractLiturgySheet $sheet */
        $sheet = new $class;

        $sheetConfig = [
            'key' => $sheet->getKey(),
            'title' => $sheet->getTitle(),
            'fileTitle' => $sheet->getFileTitle(),
        ];

        $config = $sheet->getConfiguration();
        $configurationPage = $sheet->getConfigurationPage();
        if ($configurationPage === null) {
            abort(404);
        }

        return Inertia::render($configurationPage, compact('service', 'sheetConfig', 'config'));
    }

    /**
     * Return content data for a liturgy sheet dialog.
     *
     * @return JsonResponse
     */
    public function liturgySheetDialogData(Service $service, string $key)
    {
        Gate::authorize('view', $service);
        $class = 'App\\Liturgy\\LiturgySheets\\'.$key.'LiturgySheet';
        if (! class_exists($class)) {
            abort(404);
        }

        /** @var AbstractLiturgySheet $sheet */
        $sheet = new $class;

        return response()->json($sheet->getDialogData($service));
    }

    /**
     * Return services and agendas that can be used as liturgy sources.
     *
     * @param  Service  $service  Service the sources are requested for.
     * @return JsonResponse JSON response with available services and agendas.
     */
    public function sources(Service $service): JsonResponse
    {
        Gate::authorize('view', $service);
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $services1 = Service::with([])
            ->isNotAgenda()
            ->writable()
            ->whereHas('liturgyBlocks')
            ->limit(50)->get(['id', 'title']);
        $services2 = Service::with([])
            ->isNotAgenda()
            ->userParticipates($user, 'P')
            ->whereHas('liturgyBlocks')
            ->get();
        $services3 = $services1->merge($services2)->unique();
        $services = [];
        foreach ($services3 as $service) {
            $service->load(['day', 'location']);
            if ($service->day !== null && $service->date !== null) {
                $services[$service->dateTime->timestamp.$service->id] = [
                    'id' => $service->id,
                    'text' => trim(($service->day ? $service->date->format('d.m.Y') : '').' '.$service->timeText().' '.$service->titleText().' ('.$service->locationText().')'),
                ];
            }
        }
        krsort($services);
        $agendas1 = Service::with(['day'])->isTemplate()->get();
        $agendas = [];
        foreach ($agendas1 as $agenda) {
            $agendas[] = [
                'id' => $agenda->id,
                'text' => $agenda->title,
            ];
        }

        return response()->json(compact('services', 'agendas'));
    }

    /**
     * Return sermons used by services the current user participates in.
     *
     * @return JsonResponse JSON response with matching sermons.
     */
    public function sermons(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $sermonIds = Service::whereNotNull('sermon_id')
            ->userParticipates($user, 'P')
            ->orderedDesc()
            ->pluck('sermon_id')->unique();

        $sermons = Sermon::whereIn('id', $sermonIds)->get(['id', 'title', 'subtitle', 'reference']);

        return response()->json($sermons);
    }

    /**
     * Import the liturgy blocks of another service.
     *
     * @param  Service  $service  Service receiving the imported blocks.
     * @param  Service  $source  Service providing the blocks to import.
     * @return RedirectResponse Redirect to the liturgy editor of the target service.
     */
    public function import(Service $service, Service $source): RedirectResponse
    {
        Gate::authorize('update', $service);
        Gate::authorize('update', $source);
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

    /**
     * Return the recipients configured in the service's liturgy items.
     *
     * @param  Service  $service  Service whose recipients are requested.
     * @return JsonResponse JSON response with the sorted recipients.
     */
    public function recipients(Service $service): JsonResponse
    {
        Gate::authorize('view', $service);
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
     * Collect participant ministry categories except the standard categories.
     *
     * @return array<string, string> Ministry categories keyed by their identifier.
     */
    protected function getAvailableMinistries(): array
    {
        $ministries = [];
        foreach (Participant::query()->pluck('category')->unique() as $ministry) {
            if (! is_string($ministry)) {
                continue;
            }
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
