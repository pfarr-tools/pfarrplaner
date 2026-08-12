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
 */

namespace App\Http\Controllers;

use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
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

    /**
     * @return \Inertia\Response
     */
    public function index(Request $request): Response
    {
        abort_unless(self::hasAdminModuleAccess($request->user()), 403);

        $records = $this->trashedRecordsForUser($request->user());
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

    /**
     * @param string $type
     * @param int $id
     * @return RedirectResponse
     */
    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->loadTrashedModel($type, $id);
        Gate::forUser($request->user())->authorize('restore', $model);

        DB::transaction(function () use ($request, $type, $model) {
            if (in_array($type, ['baptism', 'funeral', 'wedding'], true)) {
                $service = $model->service;
                if ($service && method_exists($service, 'trashed') && $service->trashed()) {
                    Gate::forUser($request->user())->authorize('restore', $service);
                    $service->restore();
                }
            }

            if (method_exists($model, 'trashed') && $model->trashed()) {
                $model->restore();
            }
        });

        return redirect()->route('admin.trash.index')->with('success', $this->restoreMessage($type));
    }

    /**
     * @param string $type
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->loadTrashedModel($type, $id);
        Gate::forUser($request->user())->authorize('forceDelete', $model);
        $model->forceDelete();

        return redirect()->route('admin.trash.index')->with('success', $this->forceDeleteMessage($type));
    }

    /**
     * @return array|bool
     */
    public static function getAdminModuleConfig()
    {
        $user = auth()->user();
        if (!$user || !self::hasAdminModuleAccess($user)) {
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

    /**
     * @param User $user
     * @return bool
     */
    public static function hasAdminModuleAccess(User $user): bool
    {
        return $user->writableCities->count() > 0;
    }

    /**
     * @param User $user
     * @return Collection<int, array<string, mixed>>
     */
    protected function trashedRecordsForUser(User $user): Collection
    {
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
     * @param string $type
     * @param int $id
     * @return Model
     */
    protected function loadTrashedModel(string $type, int $id): Model
    {
        return self::baseQueryForType($type)->whereKey($id)->firstOrFail();
    }

    /**
     * @param string $type
     * @param Model $model
     * @param User $user
     * @return array<string, mixed>
     */
    protected function mapRecord(string $type, Model $model, User $user): array
    {
        $cityName = match ($type) {
            'service' => $model->city?->name,
            'absence' => $model->user?->homeCities?->pluck('name')->implode(', '),
            'baptism' => $model->service?->city?->name,
            'funeral', 'wedding' => $model->service?->city?->name,
            default => null,
        };

        $label = match ($type) {
            'service' => trim($model->titleText(false) . ' (' . $model->date?->setTimezone('Europe/Berlin')->format('d.m.Y') . ')'),
            'absence' => trim(($model->user?->fullName() ?: 'Abwesenheit') . ' (' . $model->durationText() . ')'),
            default => $model->label,
        };

        $warning = null;
        if (in_array($type, ['baptism', 'funeral', 'wedding'], true) && $model->service && $model->service->trashed()) {
            $warning = 'Dabei wird auch der zugehörige Gottesdienst wiederhergestellt.';
        }

        return [
            'id' => $model->id,
            'type' => $type,
            'typeLabel' => self::SUPPORTED_TYPES[$type]['label'],
            'label' => $label,
            'cityName' => $cityName,
            'deletedAt' => optional($model->deleted_at)->toISOString(),
            'deletedAtText' => $model->deleted_at?->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') . ' Uhr',
            'deletedByName' => $model->deletedBy?->fullName(),
            'canRestore' => Gate::forUser($user)->allows('restore', $model),
            'canForceDelete' => Gate::forUser($user)->allows('forceDelete', $model),
            'restoreRoute' => self::restoreUrl($type, $model->id),
            'deleteRoute' => self::destroyUrl($type, $model->id),
            'warning' => $warning,
        ];
    }

    /**
     * @param string $type
     * @return Builder
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
            'wedding' => Wedding::onlyTrashed()->with([
                'deletedBy',
                'service' => fn ($query) => $query->withTrashed()->with('city'),
            ]),
            default => throw new \InvalidArgumentException('Unsupported trash type: ' . $type),
        };
    }

    /**
     * @param User $user
     * @param Model $model
     * @return bool
     */
    protected static function canAccessRecord(User $user, Model $model): bool
    {
        return Gate::forUser($user)->allows('restore', $model)
            || Gate::forUser($user)->allows('forceDelete', $model)
            || Gate::forUser($user)->allows('delete', $model)
            || Gate::forUser($user)->allows('update', $model)
            || Gate::forUser($user)->allows('view', $model);
    }


    /**
     * @param string $type
     * @return string
     */
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

    /**
     * @param string $type
     * @return string
     */
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

    /**
     * @return string
     */
    protected static function indexUrl(): string
    {
        return Route::has('admin.trash.index') ? route('admin.trash.index') : URL::to('/admin/papierkorb');
    }

    /**
     * @param string $type
     * @param int $id
     * @return string
     */
    protected static function restoreUrl(string $type, int $id): string
    {
        return Route::has('admin.trash.restore')
            ? route('admin.trash.restore', ['type' => $type, 'id' => $id])
            : URL::to('/admin/papierkorb/' . $type . '/' . $id . '/restore');
    }

    /**
     * @param string $type
     * @param int $id
     * @return string
     */
    protected static function destroyUrl(string $type, int $id): string
    {
        return Route::has('admin.trash.destroy')
            ? route('admin.trash.destroy', ['type' => $type, 'id' => $id])
            : URL::to('/admin/papierkorb/' . $type . '/' . $id);
    }
}
