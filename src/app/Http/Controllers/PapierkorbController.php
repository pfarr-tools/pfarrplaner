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
 */

namespace App\Http\Controllers;

use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class PapierkorbController extends Controller
{
    public const RETENTION_DAYS = 30;

    /**
     * @var array<string, array{class: class-string<Model>, label: string}>
     */
    protected const SUPPORTED_TYPES = [
        'service' => ['class' => Service::class, 'label' => 'Gottesdienste'],
        'absence' => ['class' => Absence::class, 'label' => 'Abwesenheiten'],
        'baptism' => ['class' => Baptism::class, 'label' => 'Taufen'],
        'funeral' => ['class' => Funeral::class, 'label' => 'Bestattungen'],
        'wedding' => ['class' => Wedding::class, 'label' => 'Trauungen'],
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(self::hasAdminModuleAccess($user), 403);

        $records = $this->trashedRecordsForUser($user);
        $types = collect(self::SUPPORTED_TYPES)
            ->map(fn (array $config, string $type) => ['value' => $type, 'label' => $config['label']])
            ->values();
        $cities = $records->pluck('cityName')->filter()->unique()->sort()->values();

        return Inertia::render('Admin/Papierkorb/Index', [
            'records' => $records->values(),
            'types' => $types,
            'cities' => $cities,
            'retentionDays' => self::RETENTION_DAYS,
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->loadTrashedModel($type, $id);
        Gate::forUser($request->user())->authorize('restore', $model);

        DB::transaction(function () use ($request, $type, $model) {
            if (in_array($type, ['baptism', 'funeral', 'wedding'], true)) {
                $service = $model->getAttribute('service');
                if ($service instanceof Model && method_exists($service, 'trashed') && method_exists($service, 'restore') && $service->trashed()) {
                    Gate::forUser($request->user())->authorize('restore', $service);
                    $service->restore();
                }
            }

            if ($model->trashed()) {
                $model->restore();
            }
        });

        return redirect()->route('admin.trash.index')->with('success', $this->restoreMessage($type));
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->loadTrashedModel($type, $id);
        Gate::forUser($request->user())->authorize('forceDelete', $model);
        $model->forceDelete();

        return redirect()->route('admin.trash.index')->with('success', $this->forceDeleteMessage($type));
    }

    /**
     * @return array<string, mixed>|false
     */
    public static function getAdminModuleConfig()
    {
        $user = auth()->user();
        if (! $user || ! self::hasAdminModuleAccess($user)) {
            return false;
        }

        return [
            'text' => 'Papierkorb',
            'group' => 'Administration',
            'icon' => 'mdi mdi-delete-restore',
            'url' => self::indexUrl(),
            'active' => Route::currentRouteName() === 'admin.trash.index',
            'inertia' => true,
        ];
    }

    public static function hasAdminModuleAccess(User $user): bool
    {
        return $user->writableCities->count() > 0;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function trashedRecordsForUser(User $user): Collection
    {
        /** @var Collection<int, array<string, mixed>> $records */
        $records = collect();

        foreach (array_keys(self::SUPPORTED_TYPES) as $type) {
            $records = $records->merge(
                self::baseQueryForType($type)->get()
                    ->filter(fn (Model $model) => self::canAccessRecord($user, $model))
                    ->map(fn (Model $model) => $this->mapRecord($type, $model, $user))
            );
        }

        return $records->sortByDesc('deletedAt')->values();
    }

    /**
     * Load a single soft-deleted model of the requested type.
     *
     * @param  string  $type  Supported trash type identifying the model class.
     * @param  int  $id  Primary key of the trashed record.
     * @return Service|Absence|Baptism|Funeral|Wedding Trashed model from one of the supported trash types.
     */
    protected function loadTrashedModel(string $type, int $id): Model
    {
        return self::baseQueryForType($type)->whereKey($id)->firstOrFail();
    }

    /**
     * Map a trashed model to the compact record shape used by the frontend.
     *
     * @param  string  $type  Supported trash type identifying the model class.
     * @param  Model  $model  Trashed model from one of the supported types.
     * @param  User  $user  User requesting the trash overview.
     * @return array<string, mixed> Compacted trash record.
     */
    protected function mapRecord(string $type, Model $model, User $user): array
    {
        $cityName = null;
        if ($type === 'service') {
            /** @var City|null $city */
            $city = $model->getAttribute('city');
            $cityName = $city?->name;
        } elseif ($type === 'absence') {
            /** @var User|null $absenceUser */
            $absenceUser = $model->getAttribute('user');
            /** @var Collection<int, City>|null $homeCities */
            $homeCities = $absenceUser?->getAttribute('homeCities');
            $cityName = $homeCities?->pluck('name')->implode(', ');
        } elseif (in_array($type, ['baptism', 'funeral', 'wedding'], true)) {
            /** @var Service|null $service */
            $service = $model->getAttribute('service');
            /** @var City|null $city */
            $city = $service?->getAttribute('city');
            $cityName = $city?->name;
        }

        if ($type === 'service' && $model instanceof Service) {
            /** @var Carbon|null $date */
            $date = $model->getAttribute('date');
            $label = trim($model->titleText(false).' ('.$date?->setTimezone('Europe/Berlin')->format('d.m.Y').')');
        } elseif ($type === 'absence' && $model instanceof Absence) {
            /** @var User|null $absenceUser */
            $absenceUser = $model->getAttribute('user');
            $label = trim(($absenceUser?->fullName() ?: 'Abwesenheit').' ('.$model->durationText().')');
        } else {
            $label = $model->getAttribute('label');
        }

        $warning = null;
        if (in_array($type, ['baptism', 'funeral', 'wedding'], true)) {
            /** @var Service|null $service */
            $service = $model->getAttribute('service');
            if ($service?->trashed()) {
                $warning = 'Dabei wird auch der zugehörige Gottesdienst wiederhergestellt.';
            }
        }

        /** @var Carbon|null $deletedAt */
        $deletedAt = $model->getAttribute('deleted_at');
        /** @var User|null $deletedBy */
        $deletedBy = $model->getAttribute('deletedBy');
        /** @var int $id */
        $id = $model->getAttribute('id');

        return [
            'id' => $id,
            'type' => $type,
            'typeLabel' => self::SUPPORTED_TYPES[$type]['label'],
            'label' => $label,
            'cityName' => $cityName,
            'deletedAt' => $deletedAt?->toISOString(),
            'deletedAtText' => $deletedAt?->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr',
            'deletedByName' => $deletedBy?->fullName(),
            'canRestore' => Gate::forUser($user)->allows('restore', $model),
            'canForceDelete' => Gate::forUser($user)->allows('forceDelete', $model),
            'restoreRoute' => self::restoreUrl($type, $id),
            'deleteRoute' => self::destroyUrl($type, $id),
            'warning' => $warning,
        ];
    }

    /**
     * @return Builder<Service>|Builder<Absence>|Builder<Baptism>|Builder<Funeral>|Builder<Wedding>
     */
    protected static function baseQueryForType(string $type): Builder
    {
        abort_unless(isset(self::SUPPORTED_TYPES[$type]), 404);

        return match ($type) {
            'service' => Service::onlyTrashed()->with(['city', 'deletedBy']),
            'absence' => Absence::onlyTrashed()->with(['user.homeCities', 'deletedBy']),
            'baptism' => Baptism::onlyTrashed()->with([
                'deletedBy',
                'service' => fn ($query) => $query->withTrashed()->with('city'),
            ]),
            'funeral' => Funeral::onlyTrashed()->with([
                'deletedBy',
                'service' => fn ($query) => $query->withTrashed()->with('city'),
            ]),
            default => Wedding::onlyTrashed()->with([
                'deletedBy',
                'service' => fn ($query) => $query->withTrashed()->with('city'),
            ]),
        };
    }

    protected static function canAccessRecord(User $user, Model $model): bool
    {
        return Gate::forUser($user)->allows('restore', $model)
            || Gate::forUser($user)->allows('forceDelete', $model)
            || Gate::forUser($user)->allows('delete', $model)
            || Gate::forUser($user)->allows('update', $model)
            || Gate::forUser($user)->allows('view', $model);
    }

    protected function restoreMessage(string $type): string
    {
        return match ($type) {
            'service' => 'Der Gottesdienst wurde wiederhergestellt.',
            'absence' => 'Die Abwesenheit wurde wiederhergestellt.',
            'baptism' => 'Die Taufe wurde wiederhergestellt.',
            'funeral' => 'Die Bestattung wurde wiederhergestellt.',
            'wedding' => 'Die Trauung wurde wiederhergestellt.',
            default => 'Der Eintrag wurde wiederhergestellt.',
        };
    }

    protected function forceDeleteMessage(string $type): string
    {
        return match ($type) {
            'service' => 'Der Gottesdienst wurde endgültig gelöscht.',
            'absence' => 'Die Abwesenheit wurde endgültig gelöscht.',
            'baptism' => 'Die Taufe wurde endgültig gelöscht.',
            'funeral' => 'Die Bestattung wurde endgültig gelöscht.',
            'wedding' => 'Die Trauung wurde endgültig gelöscht.',
            default => 'Der Eintrag wurde endgültig gelöscht.',
        };
    }

    protected static function indexUrl(): string
    {
        return Route::has('admin.trash.index') ? route('admin.trash.index') : URL::to('/admin/papierkorb');
    }

    protected static function restoreUrl(string $type, int $id): string
    {
        return Route::has('admin.trash.restore')
            ? route('admin.trash.restore', ['type' => $type, 'id' => $id])
            : URL::to('/admin/papierkorb/'.$type.'/'.$id.'/restore');
    }

    protected static function destroyUrl(string $type, int $id): string
    {
        return Route::has('admin.trash.destroy')
            ? route('admin.trash.destroy', ['type' => $type, 'id' => $id])
            : URL::to('/admin/papierkorb/'.$type.'/'.$id);
    }
}
