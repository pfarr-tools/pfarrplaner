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

namespace App\Http\Controllers\Extranet;

use App\Events\ServiceBeforeDelete;
use App\Events\ServiceBeforeUpdate;
use App\Events\ServiceRealtimeChanged;
use App\Events\ServiceUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Extranet\AssignExtranetServiceRequest;
use App\Http\Requests\Extranet\IndexExtranetServiceRequest;
use App\Http\Requests\Extranet\StoreExtranetServiceRequest;
use App\Http\Requests\Extranet\UpdateExtranetServiceRequest;
use App\Models\People\User;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Services\ExtranetTokenScope;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides personal Extranet service views and church-scoped service/event management APIs.
 */
class ServiceController extends Controller
{
    /** @var list<string> Standard relations loaded for Extranet service management responses. */
    private const MANAGEMENT_RELATIONS = [
        'location',
        'city',
        'participants',
        'tags',
        'serviceGroups',
        'occurences',
    ];

    /**
     * Return upcoming non-hidden services where the authenticated user participates as pastor.
     *
     * @return JsonResponse JSON payload containing user and upcoming services.
     */
    public function next(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $services = Service::startingFrom(Carbon::now())
            ->userParticipates($user, 'P')
            ->notHidden()
            ->ordered()
            ->get();

        return response()->json(compact('user', 'services'));
    }

    /**
     * Return past services where the authenticated user participated as pastor.
     *
     * @return JsonResponse JSON payload containing user and past services.
     */
    public function past(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $services = Service::endingAt(Carbon::now())
            ->userParticipates($user, 'P')
            ->orderedDesc()
            ->get();

        return response()->json(compact('user', 'services'));
    }

    /**
     * Return non-hidden services with linked sermons grouped by sermon ID for the current user.
     *
     * @return JsonResponse JSON payload containing user, grouped services, and query metadata.
     */
    public function lastWithSermon(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $query = Service::orderedDesc()
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->notHidden()
            ->whereHas('sermon');
        $services = $query->get()->groupBy('sermon_id');

        return response()->json(compact('user', 'services', 'query'));
    }

    /**
     * List services and/or general events within the church scope permitted for the request.
     *
     * @param  IndexExtranetServiceRequest  $request  Validated filter and pagination parameters.
     * @return JsonResponse JSON list or paginated payload of matching services and events.
     */
    public function index(IndexExtranetServiceRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $visibleCityIds = ExtranetTokenScope::effectiveVisibleCityIds($user, $user->currentAccessToken());

        $rawCityId = $validated['city_id'] ?? null;
        $cityIds = is_int($rawCityId) || (is_string($rawCityId) && is_numeric($rawCityId))
            ? [(int) $rawCityId]
            : $visibleCityIds->all();

        $query = Service::includeAllEventTypes()
            ->with(self::MANAGEMENT_RELATIONS)
            ->whereIn('city_id', $cityIds)
            ->whereDate('date', '!=', '1978-03-05');

        $rawEventClass = $validated['event_class'] ?? 'all';
        $eventClass = is_string($rawEventClass) ? $rawEventClass : 'all';
        if ($eventClass !== 'all') {
            $query->where('event_class', $eventClass);
        }

        if (! filter_var($validated['include_hidden'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->notHidden();
        }

        if (! empty($validated['from']) && is_string($validated['from'])) {
            $from = Carbon::parse($validated['from'])->startOfDay()->setTimezone('UTC');
            $query->where('end', '>=', $from);
        }

        if (! empty($validated['to']) && is_string($validated['to'])) {
            $to = Carbon::parse($validated['to'])->endOfDay()->setTimezone('UTC');
            $query->where('date', '<=', $to);
        }

        $query->orderBy('date');

        $rawPerPage = $validated['per_page'] ?? null;
        if (is_int($rawPerPage) || (is_string($rawPerPage) && is_numeric($rawPerPage))) {
            return response()->json($query->paginate((int) $rawPerPage));
        }

        return response()->json(['services' => $query->get()]);
    }

    /**
     * Return details of a single church service or general event within the caller's church scope.
     *
     * @param  Request  $request  Authenticated HTTP request.
     * @param  Service  $service  Resolved service or event model.
     * @return JsonResponse JSON representation of the requested service or event.
     */
    public function show(Request $request, Service $service): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);
        abort_if($service->isTemplate() || $service->city_id === null, Response::HTTP_NOT_FOUND);

        $visibleCityIds = ExtranetTokenScope::effectiveVisibleCityIds($user, $user->currentAccessToken());
        abort_unless($visibleCityIds->contains((int) $service->city_id), Response::HTTP_FORBIDDEN);

        $service->load(array_merge(self::MANAGEMENT_RELATIONS, ['baptisms', 'funerals', 'weddings']));

        return response()->json(['service' => $service]);
    }

    /**
     * Create a new church service or general event via the Extranet API.
     *
     * @param  StoreExtranetServiceRequest  $request  Validated service/event creation request.
     * @return JsonResponse HTTP 201 JSON payload containing the created service or event.
     */
    public function store(StoreExtranetServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->serviceAttributes());
        $service->setDefaultOfferingValues();
        $service->save();

        $this->syncRelationsFromRequest($request, $service);
        ServiceRealtimeChanged::dispatch('created', $service);

        $service->refresh();
        $service->load(self::MANAGEMENT_RELATIONS);

        return response()->json(['service' => $service], Response::HTTP_CREATED);
    }

    /**
     * Update an existing church service or general event and its optional relations.
     *
     * @param  UpdateExtranetServiceRequest  $request  Validated partial update request.
     * @param  Service  $service  Target service or event model.
     * @return JsonResponse JSON payload containing the updated service or event.
     */
    public function update(UpdateExtranetServiceRequest $request, Service $service): JsonResponse
    {
        $service->trackChanges();
        $originalParticipants = $service->participants;
        $attributes = $request->serviceAttributes($service);

        event(new ServiceBeforeUpdate($service, $attributes));
        $service->fill($attributes);
        $service->save();

        $relationsTouched = $this->syncRelationsFromRequest($request, $service);

        if ($service->isChanged()) {
            $service->storeDiff();
            event(new ServiceUpdated($service, $originalParticipants));
        } elseif ($relationsTouched) {
            ServiceRealtimeChanged::dispatch('updated', $service);
        }

        $service->refresh();
        $service->load(self::MANAGEMENT_RELATIONS);

        return response()->json(['service' => $service]);
    }

    /**
     * Soft-delete a church service or general event within the caller's writable church scope.
     *
     * @param  Request  $request  Authenticated HTTP request.
     * @param  Service  $service  Target service or event model.
     * @return JsonResponse Confirmation payload with deleted flag and service ID.
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);
        abort_unless(
            ExtranetTokenScope::canManageService($user, $user->currentAccessToken(), $service, 'delete'),
            Response::HTTP_FORBIDDEN
        );

        event(new ServiceBeforeDelete($service));
        $deletedId = $service->id;
        $service->delete();

        return response()->json(['deleted' => true, 'id' => $deletedId]);
    }

    /**
     * Assign or toggle users in one or more service/event ministry categories.
     *
     * @param  AssignExtranetServiceRequest  $request  Validated ministry assignment request.
     * @param  Service  $service  Target service or event model.
     * @return JsonResponse JSON payload containing the updated service or event.
     */
    public function assign(AssignExtranetServiceRequest $request, Service $service): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $participants = $service->getSyncableParticipantsArray();
        $rawMinistry = $data['ministry'] ?? null;
        $ministries = is_array($rawMinistry) ? $rawMinistry : [$rawMinistry];
        $users = is_array($data['users'] ?? null) ? $data['users'] : [];
        $exclusive = (bool) ($data['exclusive'] ?? false);
        $noToggle = (bool) ($data['no-toggle'] ?? false);

        foreach ($ministries as $ministry) {
            if (! is_string($ministry) || $ministry === '') {
                continue;
            }
            $existing = $participants[$ministry] ?? [];
            if ($exclusive) {
                $participants[$ministry] = [];
            }
            foreach ($users as $userId) {
                if (! is_int($userId) && ! (is_string($userId) && is_numeric($userId))) {
                    continue;
                }
                $id = (int) $userId;
                if (! $noToggle && isset($existing[$id])) {
                    unset($participants[$ministry][$id]);
                } else {
                    $participants[$ministry][$id]['category'] = $ministry;
                }
            }
        }

        $service->syncParticipantsFromArray($participants);
        $service->checkIfPredicantNeeded();
        ServiceRealtimeChanged::dispatch('updated', $service);

        $service->refresh();
        $service->load(self::MANAGEMENT_RELATIONS);

        return response()->json(['service' => $service]);
    }

    /**
     * Synchronize optional participants, tags, and service groups when present on the request.
     *
     * @param  FormRequest  $request  Current validated create or update request.
     * @param  Service  $service  Persisted service or event model.
     * @return bool True when at least one relation payload was processed.
     */
    private function syncRelationsFromRequest(FormRequest $request, Service $service): bool
    {
        $touched = false;

        if ($request->has('participants') || $request->has('ministries')) {
            $service->associateParticipants($request, $service);
            $service->checkIfPredicantNeeded();
            $touched = true;
        }

        if ($request->has('tags')) {
            $rawTags = $request->input('tags');
            $tagIds = [];
            if (is_array($rawTags)) {
                foreach ($rawTags as $tagId) {
                    if (is_int($tagId) || (is_string($tagId) && is_numeric($tagId))) {
                        $tagIds[] = (int) $tagId;
                    }
                }
            }
            $service->tags()->sync($tagIds);
            $touched = true;
        }

        if ($request->has('serviceGroups')) {
            $rawGroups = $request->input('serviceGroups');
            $groups = [];
            if (is_array($rawGroups)) {
                foreach ($rawGroups as $group) {
                    if (is_int($group) || is_string($group)) {
                        $groups[] = $group;
                    }
                }
            }
            $service->serviceGroups()->sync(ServiceGroup::createIfMissing($groups));
            $touched = true;
        }

        return $touched;
    }
}
