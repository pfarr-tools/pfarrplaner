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

use App\Actions\Security\CreateManualLoginLink;
use App\Actions\Security\ManageUserAccount;
use App\Actions\Security\RecoverUserTwoFactor;
use App\Facades\Settings;
use App\HomeScreen\Tabs\HomeScreenTabFactory;
use App\Http\Requests\RecoverTwoFactorRequest;
use App\Http\Requests\UserRequest;
use App\Mail\User\AccountData;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\Location;
use App\Models\Parish;
use App\Models\People\TwoFactorRecoveryAudit;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Subscription;
use App\Providers\AuthServiceProvider;
use App\Rules\CreatedInLocalAdminDomainRule;
use App\Services\MinistryService;
use App\Traits\HandlesAttachedImageTrait;
use App\Traits\HandlesAttachmentsTrait;
use App\UI\Modules\Modules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Passkeys\Passkey;
use RoundlyConsulting\TwoFactor\DataTransferObjects\TwoFactorSetup;
use RoundlyConsulting\TwoFactor\Exceptions\InvalidTwoFactorCodeException;
use RoundlyConsulting\TwoFactor\Exceptions\TwoFactorAlreadyEnabledException;
use RoundlyConsulting\TwoFactor\Exceptions\TwoFactorNotPendingException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Class UserController
 */
class UserController extends Controller
{
    use HandlesAttachedImageTrait;
    use HandlesAttachmentsTrait;

    /** @var class-string<User> Model class used by the shared attachment traits. */
    protected string $model = User::class;

    /**
     * Create the controller and require authentication.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        Gate::authorize('index', User::class);
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        $userQuery = User::select(['id', 'first_name', 'last_name', 'title', 'email', 'is_official_user'])
            ->with([
                'homeCities:id,name',
                'cities:id,name',
                'writableCities:id,name',
                'adminCities:id,name',
                'roles:id,name',
                'roles.permissions:id,name',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('email');

        if (! $authUser->isAdmin) {
            if ($authUser->adminCities->count()) {
                // Local admin: can see anyone from his/her admin'd cities and people without homeCities
                $userQuery->where(function ($q) use ($authUser) {
                    $q->whereHas('homeCities', function ($q2) use ($authUser) {
                        $q2->whereIn('cities.id', $authUser->homeCities->pluck('id'));
                    });
                })->orWhere(function ($q) {
                    $q->whereDoesntHave('homeCities');
                });
            } elseif ($authUser->can('benutzer-bearbeiten')) {
                // Clerk: Can only see users
                $userQuery->where('is_official_user', true);
            } elseif ($authUser->can('benutzerliste-lokal-sehen')) {
                // Local clerk: Can only see users from own cities
                $cityIds = $authUser->writableCities->pluck('id');
                $userQuery->whereHas('cities', function ($q) use ($cityIds) {
                    $q->whereIn('cities.id', $cityIds);
                });
            } else {
                abort(403);
            }
        }

        $canCreate = $authUser->can('create', User::class);

        $allCities = City::query()->select(['id', 'name'])->get();

        return Inertia::render('Admin/User/Index', [
            'users' => $userQuery->get()->map(fn (User $user) => $this->compactIndexUser($user, $allCities)),
            'canCreate' => $canCreate,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        Gate::authorize('create', User::class);
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        $user = (new User)->load([
            'homeCities',
            'parishes',
            'roles',
            'cities',
            'writableCities',
            'adminCities',
            'vacationAdmins',
            'vacationApprovers',
        ]);

        $cities = City::query()->select(['id', 'name'])->orderBy('name')->get();

        $adminCities = [];
        foreach ($cities as $city) {
            if ($city->administeredBy($authUser)) {
                $adminCities[] = $city;
            }
        }

        return Inertia::render(
            'Admin/User/UserEditor',
            [
                'user' => $this->compactEditorUser($user),
                'cities' => $this->compactOptions($cities),
                'adminCities' => $this->compactOptions($adminCities),
                'roles' => $this->compactRoles($this->getRoles()),
                'parishes' => $this->compactOptions(Parish::query()->select(['id', 'name'])->get()),
                'users' => $this->compactPeople(),
                'activeTab' => 'home',
                'subscriptions' => [],
                'settings' => [],
                'availableTabs' => HomeScreenTabFactory::available(),
                'locations' => $this->compactOptions($this->locationsForUser()),
                'ministries' => MinistryService::all(),
                'modules' => Modules::tree(),
            ]
        );
    }

    /**
     * @return Collection|Role[]
     */
    protected function getRoles()
    {
        /** @var array<string, mixed> $rolesConfig */
        $rolesConfig = config('roles.roles');
        $rolesQuery = Role::query()->whereIn('name', array_keys($rolesConfig));

        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        if ($authUser->hasRole('Super-Administrator:in')) {
            $roles = $rolesQuery->get();
        } else {
            $roles = $rolesQuery->where('name', '!=', 'Super-Administrator:in')->get();
        }

        return $roles->sortBy('name');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(UserRequest $request)
    {
        Gate::authorize('create', User::class);
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        $data = $request->validated();
        $user = User::create($data);
        $this->updateUserDataFromRequest($request, $user);
        if ($user->isOfficialUser && $user->email) {
            Mail::to($user->email)->queue(new AccountData($user, $authUser));
        }

        return redirect()->route('users.index')->with('success', 'Der neue Benutzer wurde angelegt.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Inertia\Response
     */
    public function edit(User $user, Request $request)
    {
        Gate::authorize('update', $user);
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);
        $user->load([
            'homeCities',
            'parishes',
            'roles',
            'cities',
            'writableCities',
            'adminCities',
            'vacationAdmins',
            'vacationApprovers',
        ]);
        $cities = City::query()->select(['id', 'name'])->orderBy('name')->get();
        $adminCities = [];
        $adminCityIds = [];
        foreach ($cities as $city) {
            if ($city->administeredBy($actor)) {
                $adminCities[] = $city;
                $adminCityIds[] = $city->id;
            }
        }

        $cities = $user->homeCities->merge(collect($adminCities));

        $roles = $this->getRoles();

        $parishes = Parish::inCities($actor->adminCities->pluck('id'))->get();
        $homescreen = $user->getSetting('homeScreen', 'route:calendar');
        $users = $this->compactPeople();
        $subscriptions = Subscription::where('user_id', $user->id)
            ->inCities($adminCityIds)
            ->get();

        $homeScreenTabsConfig = $user->getSetting('homeScreenTabsConfig') ?? [];
        $settings = Settings::all($user);
        $availableTabs = HomeScreenTabFactory::available($user);
        $locations = $this->compactOptions($this->locationsForUser());
        $ministries = MinistryService::all();
        $modules = Modules::tree();

        $recoveryTargetHasFactor = $user->hasTwoFactorEnabled() || $user->hasPendingTwoFactor();
        $user = $this->compactEditorUser($user);
        $cities = $this->compactOptions($cities);
        $adminCities = $this->compactOptions($adminCities);
        $roles = $this->compactRoles($roles);
        $parishes = $this->compactOptions($parishes);

        $activeTab = $request->get('tab', 'home');
        $canViewRecoveryHistory = $actor->hasRole(AuthServiceProvider::SUPER)
            && Auth::id() !== $user['id'];
        $sessionConnection = config('session.connection');
        $sessionRevocationSupported = config('session.driver') === 'database'
            && (! $sessionConnection || $sessionConnection === config('database.default'));
        $actorHasTwoFactor = $actor->hasTwoFactorEnabled();
        $twoFactorRecovery = [
            'canRecover' => $canViewRecoveryHistory
                && $actorHasTwoFactor
                && $recoveryTargetHasFactor
                && $sessionRevocationSupported,
            'unavailableReason' => $canViewRecoveryHistory && $recoveryTargetHasFactor && ! $actorHasTwoFactor
                ? 'Richte zuerst selbst eine Zwei-Faktor-Anmeldung ein, damit du die Wiederherstellung sicher bestätigen kannst.'
                : ($canViewRecoveryHistory && $recoveryTargetHasFactor && ! $sessionRevocationSupported
                    ? 'Die Wiederherstellung erfordert Datenbank-Sitzungen mit derselben Datenbankverbindung wie die Anwendung.'
                    : null),
            'history' => [],
        ];
        if ($canViewRecoveryHistory) {
            $twoFactorRecovery['history'] = TwoFactorRecoveryAudit::query()
                ->with('actor:id,first_name,last_name,title')
                ->where('target_user_id', $user['id'])
                ->latest()
                ->get()
                ->map(function (TwoFactorRecoveryAudit $audit): array {
                    $actor = $audit->getRelation('actor');

                    return [
                        'actor' => [
                            'id' => $audit->actor_user_id,
                            'name' => $actor instanceof User ? $actor->name : 'Gelöschtes Konto',
                        ],
                        'createdAt' => $audit->created_at?->toIso8601String(),
                        'supportReference' => $audit->support_reference,
                    ];
                })
                ->all();
        }

        return Inertia::render(
            'Admin/User/UserEditor',
            compact(
                'user',
                'cities',
                'adminCities',
                'homescreen',
                'roles',
                'parishes',
                'users',
                'activeTab',
                'subscriptions',
                'settings',
                'availableTabs',
                'locations',
                'ministries',
                'modules',
                'twoFactorRecovery',
            )
        );
    }

    /**
     * Build the small representation needed by the user overview.
     *
     * @param  Collection<int, City>  $allCities  Cities available for administration.
     * @return array<string, mixed>
     */
    protected function compactIndexUser(User $user, Collection $allCities): array
    {
        /** @var Collection<int, City> $homeCities */
        $homeCities = $user->getRelation('homeCities');
        /** @var Collection<int, City> $userCities */
        $userCities = $user->getRelation('cities');
        /** @var Collection<int, Role> $roles */
        $roles = $user->getRelation('roles');
        /** @var Collection<int, City> $adminCities */
        $adminCities = $user->hasRole(AuthServiceProvider::SUPER)
            ? $allCities
            : $user->getRelation('adminCities');
        /** @var Collection<int, City> $writableCities */
        $writableCities = $user->hasRole(AuthServiceProvider::SUPER)
            ? $allCities
            : $user->getRelation('writableCities');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'sortName' => $user->sortName,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'isOfficialUser' => $user->isOfficialUser,
            'isAdmin' => $user->isAdmin,
            'home_cities' => $this->compactOptions($homeCities),
            'cities' => $this->compactOptions($userCities),
            'writable_cities' => $this->compactOptions($writableCities),
            'admin_cities' => $this->compactOptions($adminCities),
            'roles' => $this->compactRoles($roles),
        ];
    }

    /**
     * Build the representation needed by the user editor without serializing the model.
     *
     * @return array<string, mixed>
     */
    protected function compactEditorUser(User $user): array
    {
        /** @var Collection<int, City> $homeCities */
        $homeCities = $user->getRelation('homeCities');
        /** @var Collection<int, Parish> $parishes */
        $parishes = $user->getRelation('parishes');
        /** @var Collection<int, Role> $roles */
        $roles = $user->getRelation('roles');
        /** @var Collection<int, City> $cities */
        $cities = $user->getRelation('cities');
        /** @var Collection<int, City> $writableCities */
        $writableCities = $user->getRelation('writableCities');
        /** @var Collection<int, City> $adminCities */
        $adminCities = $user->getRelation('adminCities');
        /** @var Collection<int, User> $vacationAdmins */
        $vacationAdmins = $user->getRelation('vacationAdmins');
        /** @var Collection<int, User> $vacationApprovers */
        $vacationApprovers = $user->getRelation('vacationApprovers');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'title' => $user->title,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'office' => $user->office,
            'address' => $user->address,
            'phone' => $user->phone,
            'email' => $user->email,
            'image' => $user->image,
            'manage_absences' => $user->manage_absences,
            'show_vacations_with_services' => $user->show_vacations_with_services,
            'needs_replacement' => $user->needs_replacement,
            'isOfficialUser' => $user->isOfficialUser,
            'isLocked' => $user->isLocked,
            'isSuperAdmin' => $user->hasRole(AuthServiceProvider::SUPER),
            'home_cities' => $this->compactIds($homeCities),
            'parishes' => $this->compactIds($parishes),
            'roles' => $this->compactIds($roles),
            'cities' => $this->compactIds($cities),
            'writable_cities' => $this->compactIds($writableCities),
            'admin_cities' => $this->compactIds($adminCities),
            'vacation_admins' => $this->compactIds($vacationAdmins),
            'vacation_approvers' => $this->compactIds($vacationApprovers),
        ];
    }

    /**
     * Return only the fields needed by people selectors.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function compactPeople(?int $exceptId = null): array
    {
        $query = User::query()
            ->select(['id', 'title', 'first_name', 'last_name', 'email', 'image'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->get()
            ->map(fn (User $user) => $this->compactPerson($user))
            ->values()
            ->all();
    }

    /**
     * Convert a person to the fields used by person selectors.
     *
     * @return array<string, mixed>
     */
    protected function compactPerson(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'title' => $user->title,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'image' => $user->image,
        ];
    }

    /**
     * Convert option models to the fields used by form select components.
     *
     * @param  iterable<int, City|Parish|Location>  $options  Option models with an id and a name.
     * @return array<int, array<string, mixed>>
     */
    protected function compactOptions(iterable $options): array
    {
        return collect($options)
            ->map(fn ($option) => ['id' => $option->id, 'name' => $option->name])
            ->values()
            ->all();
    }

    /**
     * Convert relation models to their IDs.
     *
     * @param  iterable<int, Model>  $records  Related models to reduce to their IDs.
     * @return array<int, int|string>
     */
    protected function compactIds(iterable $records): array
    {
        /** @var array<int, int|string> $ids */
        $ids = collect($records)->pluck('id')->values()->all();

        return $ids;
    }

    /**
     * Convert roles and permissions to the fields used by the user interface.
     *
     * @param  iterable<int, Role>  $roles  Roles with their permissions loaded.
     * @return array<int, array<string, mixed>>
     */
    protected function compactRoles(iterable $roles): array
    {
        return collect($roles)
            ->map(function ($role) {
                /** @var Collection<int, Permission> $permissions */
                $permissions = $role->permissions;

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $permissions
                        ->map(fn ($permission) => ['id' => $permission->id, 'name' => $permission->name])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Load only the location fields needed for home-screen configuration.
     *
     * @return Collection<int, Location>
     */
    protected function locationsForUser(): Collection
    {
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);

        return Location::query()
            ->without(['seatingSections', 'city'])
            ->select(['id', 'name'])
            ->inCities($authUser->cities->pluck('id'))
            ->get();
    }

    /**
     * @return \Inertia\Response
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $allCities = $user->visibleCities;
        $cities = $user->cities;

        $subscriptions = [];
        foreach ($cities as $city) {
            $subscriptions[$city->id] = $user->getSubscriptionType($city);
        }

        $sortedCities = $user->getSortedCities();

        // homeScreenTabs
        $homeScreenTabsConfig = $user->getSetting('homeScreenTabsConfig') ?? [];
        $settings = Settings::all($user);
        $availableTabs = HomeScreenTabFactory::available();

        $calendarConnections = CalendarConnection::where('user_id', $user->id)->get();
        $locations = Location::inCities($user->cities->pluck('id'))->get();
        $ministries = MinistryService::all();
        $twoFactorEnabled = $user->hasTwoFactorEnabled();
        $twoFactorPending = $user->hasPendingTwoFactor();
        $passkeys = $user->passkeys()->latest()->get()->map(static fn (Passkey $passkey): array => [
            'id' => $passkey->getKey(),
            'name' => $passkey->name,
            'authenticator' => $passkey->authenticator,
            'createdAt' => $passkey->created_at?->toIso8601String(),
            'lastUsedAt' => $passkey->last_used_at?->toIso8601String(),
        ])->all();

        $tab = $request->get('tab', '');

        return Inertia::render(
            'Profile/ProfileEditor',
            compact(
                'user',
                'cities',
                'tab',
                'homeScreenTabsConfig',
                'availableTabs',
                'calendarConnections',
                'subscriptions',
                'locations',
                'ministries',
                'settings',
                'twoFactorEnabled',
                'twoFactorPending',
                'passkeys',
            )
        );
    }

    /**
     * @return RedirectResponse
     */
    public function profileSave(Request $request)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('update', $user);
        $data = $this->validateRequest($request, $user);

        $user->update($data);

        // set subscriptions
        /** @var array<int|string, mixed> $subscriptions */
        $subscriptions = $request->get('subscriptions') ?: [];
        $user->setSubscriptionsFromArray($subscriptions);

        // settings
        if ($request->has('settings')) {
            /** @var array<string, mixed> $settings */
            $settings = $request->get('settings', []);
            foreach ($settings as $key => $setting) {
                $user->setSetting($key, $setting);
            }
        }

        if ($request->has('homeScreenTabsConfig')) {
            $user->setSetting('homeScreenTabsConfig', $request->get('homeScreenTabsConfig'));
        }

        return redirect()->route('home')->with('success', 'Die Änderungen wurden gespeichert.');
    }

    /**
     * Start TOTP enrollment and return one-time setup data for the authenticated user.
     *
     * @return JsonResponse One-time provisioning data.
     */
    public function startTwoFactor(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        Gate::authorize('update', $user);
        try {
            $setup = DB::transaction(function () use ($user): TwoFactorSetup {
                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

                if ($lockedUser->hasTwoFactorEnabled()) {
                    throw TwoFactorAlreadyEnabledException::make();
                }

                return $lockedUser->startTwoFactorEnrolment();
            });
        } catch (TwoFactorAlreadyEnabledException) {
            throw ValidationException::withMessages([
                'two_factor' => 'Die Zwei-Faktor-Einstellungen konnten nicht geändert werden.',
            ]);
        }

        return response()->json([
            'provisioning_uri' => $setup->provisioningUri,
            'recovery_codes' => $setup->recoveryCodes,
        ]);
    }

    /**
     * Confirm TOTP enrollment with the first authenticator code.
     *
     * @param  Request  $request  Current HTTP request containing the TOTP code.
     * @return JsonResponse Confirmation result.
     */
    public function confirmTwoFactor(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        Gate::authorize('update', $user);
        /** @var array{code: string} $validated */
        $validated = $request->validate(['code' => ['required', 'string']]);
        $code = str_replace([' ', '-'], '', $validated['code']);

        if (! ctype_digit($code) || strlen($code) < 6 || strlen($code) > 8) {
            throw ValidationException::withMessages([
                'code' => 'Der Sicherheitscode ist ungültig oder nicht mehr verfügbar.',
            ]);
        }

        /** @var int|string $userId */
        $userId = $user->getKey();
        $rateLimitKey = 'two-factor-confirm:'.$userId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json(['message' => 'Zu viele Versuche. Bitte später erneut versuchen.'], 429);
        }

        try {
            DB::transaction(function () use ($user, $code, $rateLimitKey): void {
                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

                if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
                    throw new TooManyRequestsHttpException(null, 'Zu viele Versuche. Bitte später erneut versuchen.');
                }

                $lockedUser->confirmTwoFactor($code);
                if ($lockedUser->two_factor_recovery_required_at) {
                    $lockedUser->forceFill(['two_factor_recovery_required_at' => null])->save();
                }
                RateLimiter::hit($rateLimitKey, 60);
            });
        } catch (TooManyRequestsHttpException) {
            return response()->json(['message' => 'Zu viele Versuche. Bitte später erneut versuchen.'], 429);
        } catch (InvalidTwoFactorCodeException|TwoFactorNotPendingException) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'code' => 'Der Sicherheitscode ist ungültig oder nicht mehr verfügbar.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        return response()->json(['enabled' => true]);
    }

    /**
     * Disable TOTP for the authenticated account.
     *
     * @return JsonResponse Updated factor state.
     */
    public function disableTwoFactor(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        Gate::authorize('update', $user);
        try {
            DB::transaction(function () use ($user): void {
                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
                $lockedUser->disableTwoFactor();
            });
        } catch (TwoFactorNotPendingException) {
            throw ValidationException::withMessages([
                'two_factor' => 'Die Zwei-Faktor-Einstellungen konnten nicht geändert werden.',
            ]);
        }

        return response()->json(['enabled' => false]);
    }

    /** Recover another account after a current Super-Admin TOTP confirmation. */
    public function recoverTwoFactor(
        RecoverTwoFactorRequest $request,
        User $user,
        RecoverUserTwoFactor $recovery,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        /** @var array{code: string, support_reference: string} $validated */
        $validated = $request->validated();
        $recovery->execute(
            $actor,
            $user,
            $validated['code'],
            $validated['support_reference'],
            (string) $request->ip(),
        );

        return redirect()->route('user.edit', $user->getKey())
            ->with('message', 'Der Zwei-Faktor-Zugang wurde widerrufen. Das Konto muss TOTP erneut einrichten.')
            ->with('type', 'success');
    }

    /** Block a target account from web sign-in. */
    public function lockAccount(Request $request, User $user, ManageUserAccount $accounts): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $accounts->setLocked($actor, $user, true);

        return redirect()->route('user.edit', $user->getKey())
            ->with('message', 'Das Benutzerkonto wurde gesperrt.')
            ->with('type', 'success');
    }

    /** Restore a target account's ability to sign in through the web application. */
    public function unlockAccount(Request $request, User $user, ManageUserAccount $accounts): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $accounts->setLocked($actor, $user, false);

        return redirect()->route('user.edit', $user->getKey())
            ->with('message', 'Das Benutzerkonto wurde entsperrt.')
            ->with('type', 'success');
    }

    /** Revoke all of a target account's registered passkeys. */
    public function revokePasskeys(Request $request, User $user, ManageUserAccount $accounts): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $accounts->revokePasskeys($actor, $user);

        return redirect()->route('user.edit', $user->getKey())
            ->with('message', 'Alle Passkeys des Benutzerkontos wurden widerrufen.')
            ->with('type', 'success');
    }

    /** Remove authentication credentials while retaining the target person. */
    public function resetAccount(Request $request, User $user, ManageUserAccount $accounts): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $accounts->resetToPerson($actor, $user);

        return redirect()->route('user.edit', $user->getKey())
            ->with('message', 'Das Benutzerkonto wurde entfernt; die Person bleibt erhalten.')
            ->with('type', 'success');
    }

    /**
     * Create a one-hour login URL for a target account without sending email.
     *
     * @param  Request  $request  Current authenticated request.
     * @param  User  $user  Account that will receive the link.
     * @param  CreateManualLoginLink  $createManualLoginLink  Authorized link generator.
     * @return JsonResponse Non-cacheable URL and expiration timestamp.
     */
    public function createManualLoginLink(
        Request $request,
        User $user,
        CreateManualLoginLink $createManualLoginLink,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $link = $createManualLoginLink->execute($actor, $user);

        return response()->json($link)
            ->header('Cache-Control', 'no-store')
            ->header('Pragma', 'no-cache')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UserRequest  $request  Validated request.
     * @return RedirectResponse
     */
    public function update(UserRequest $request, User $user)
    {
        Gate::authorize('update', $user);
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        $wasOfficialUser = $user->isOfficialUser;
        $data = $request->validated();
        $user->update($data);
        if (! $wasOfficialUser && $user->isOfficialUser && $user->email) {
            Mail::to($user->email)->queue(new AccountData($user, $authUser));
        }
        $this->updateUserDataFromRequest($request, $user);

        return redirect()->route('users.index')->with('success', 'Die Änderungen wurden gespeichert.');
    }

    /**
     * Update all user record relations from the request data
     */
    /**
     * @return void
     */
    protected function updateUserDataFromRequest(UserRequest $request, User $user)
    {
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        $user->homeCities()->sync(collect($request->getRelationIdsForSync('home_cities', 'cities'))->reject(function ($item) use ($user, $authUser) {
            return ! ((($user->homeCities ?? collect())->pluck('id')->contains($item)) || ($authUser->adminCities->pluck('id')->contains($item)));
        }));
        $user->parishes()->sync($request->getRelationIdsForSync('parishes'));
        $user->syncRelatedUsers(
            'vacationAdmins',
            'vacation_admin',
            array_map('intval', $request->getRelationIdsForSync('vacation_admins', 'users'))
        );
        $user->syncRelatedUsers(
            'vacationApprovers',
            'vacation_approver',
            array_map('intval', $request->getRelationIdsForSync('vacation_approvers', 'users'))
        );
        $user->roles()->sync($request->getRoles());

        /** @var array<string, mixed> $settings */
        $settings = $request->get('settings', []);
        foreach ($settings as $key => $setting) {
            $user->setSetting($key, $setting);
        }
        /** @var array<int|string, mixed> $subscriptions */
        $subscriptions = $request->get('subscriptions') ?: [];
        $user->setSubscriptionsFromArray($subscriptions);
        /** @var array<int|string, mixed> $permissions */
        $permissions = $request->get('permissions') ?: [];
        $user->updateCityPermissions($permissions, $authUser);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Der Benutzer wurde gelöscht.');
    }

    /**
     * @return \Inertia\Response
     */
    public function join(User $user)
    {
        Gate::authorize('join', $user);

        return Inertia::render('Admin/User/Join', [
            'user' => $this->compactPerson($user),
            'people' => $this->compactPeople($user->id),
        ]);
    }

    /**
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function doJoin(Request $request, User $user1, User $user2)
    {
        $this->authorize('merge', $user1);
        $this->authorize('merge', $user2);

        $user1->mergeInto($user2);

        // delete old user
        $user1->delete();

        return redirect()->route('users.index')->with('success', 'Die Benutzer wurden zusammengeführt.');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function switch(Request $request, User $user)
    {
        abort_if(Session::has('adminUserSwitchBack'), 403);
        Gate::authorize('impersonate', $user);
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        if (! $user->isOfficialUser || $user->isLocked) {
            return redirect()->back()->withErrors([
                'user' => 'Dieses Konto ist nicht aktiv und kann nicht übernommen werden.',
            ]);
        }

        $adminId = $authUser->id;
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::login($user);
        $request->session()->regenerate();
        Session::put('adminUserSwitchBack', $adminId);

        // save switch in session!
        return $this->switchRedirectResponse($request);
    }

    /**
     * Return from an impersonated account to the administrator account.
     *
     * @param  Request  $request  Current session request.
     * @return \Symfony\Component\HttpFoundation\Response Redirect response.
     */
    public function switchBack(Request $request)
    {
        if (! Session::has('adminUserSwitchBack')) {
            abort(403);
        }
        /** @var int|string|null $adminUserSwitchBack */
        $adminUserSwitchBack = Session::get('adminUserSwitchBack');
        $user = User::findOrFail((int) $adminUserSwitchBack);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::login($user);
        $request->session()->regenerate();
        Session::remove('adminUserSwitchBack');

        return $this->switchRedirectResponse($request);
    }

    /**
     * Force a full frontend reload after an impersonation change.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function switchRedirectResponse(Request $request)
    {
        if ($request->headers->has('X-Inertia')) {
            return Inertia::location(route('home'));
        }

        return redirect()->route('home');
    }

    /**
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Check if the user is a local admin and has set city permissions
     */
    protected function validateCityPermissions(Request $request): void
    {
        // Kept for backward compatibility with older call sites.
    }

    /**
     * Validate submitted data
     *
     * @param  User|null  $user  User being edited.
     * @param  bool  $withCity  Whether city validation rules are included.
     * @return array<string, mixed>
     */
    protected function validateRequest(Request $request, $user = null, $withCity = false)
    {
        $rules = [
            'first_name' => 'nullable|string',
            'last_name' => 'required|string|max:255',
            'title' => 'nullable|string',
            'email' => 'nullable|string|email|max:255|unique:users,email'.($user ? ','.$user->id : ''),
            'isOfficialUser' => 'sometimes|boolean',
            'office' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|phone_number',
            'preference_cities' => 'nullable|string',
            'manage_absences' => 'nullable|bool',
            'homeCities' => 'nullable',
            'homeCities.*' => 'int|exists:cities,id',
            'own_website' => 'nullable|string',
            'own_podcast_title' => 'nullable|string',
            'own_podcast_url' => 'nullable|string|url',
            'own_podcast_spotify' => 'nullable|bool',
            'own_podcast_itunes' => 'nullable|bool',
            'show_vacations_with_services' => 'nullable|bool',
            'needs_replacement' => 'nullable|bool',
        ];

        if ($withCity) {
            $rules['city_id'] = 'int|exists:cities,id';
        }

        // special treatment if the submitter is a local admin
        $authUser = Auth::user();
        abort_unless($authUser instanceof User, 403);
        $route = $request->route();
        abort_unless($route !== null, 500);
        if ($authUser->isLocalAdmin) {
            // on create:
            if ($route->getName() == 'user.store') {
                // check if at least one permission is set
                $rules['cityPermission'] = [new CreatedInLocalAdminDomainRule];

                // A passwordless account requires a reachable email address.
                $rules['email'] = 'required|email';
            }
        }

        /** @var array<string, mixed> $data */
        $data = $request->validate($rules);

        if (array_key_exists('isOfficialUser', $data)) {
            $data['is_official_user'] = (bool) $data['isOfficialUser'];
            unset($data['isOfficialUser']);
        }

        return $data;
    }

    /**
     * Create a user from the compact administration form.
     *
     * @param  Request  $request  User creation request.
     * @return JsonResponse Created user response.
     */
    public function add(Request $request)
    {
        Gate::authorize('create', User::class);

        $data = $this->validateRequest($request, null, true);
        $user = User::create($data);

        // activate the new user for a city
        if (isset($data['city_id'])) {
            $user->cityScopes()->attach($data['city_id']);
        }

        return response()->json($user);
    }

    /**
     * Find user records that share identifying data and may be duplicates.
     *
     * @return \Inertia\Response Duplicate review page.
     */
    public function findDuplicates()
    {
        Gate::authorize('findDuplicates', User::class);

        $allUsers = User::with(['homeCities', 'cityScopes'])->get();
        $userById = $allUsers->keyBy('id');

        // Build inverted index: name key → [user_ids]
        // Two keys are used: the stored name field, and the (first_name, last_name) pair when both are present.
        // This catches cases where the name field was auto-generated differently (e.g. title included).
        $keyIndex = [];
        foreach ($allUsers as $user) {
            if ($user->first_name && $user->last_name) {
                $keyIndex['fl:'.mb_strtolower(trim($user->first_name)).'|'.mb_strtolower(trim($user->last_name))][] = $user->id;
            }
        }

        // Union-Find: path-halving find
        /** @var array<int, int> $parent */
        $parent = $allUsers->pluck('id', 'id')->toArray();
        $find = function (int $x) use (&$parent): int {
            while ($parent[$x] !== $x) {
                $parent[$x] = $parent[$parent[$x]];
                $x = $parent[$x];
            }

            return $x;
        };

        foreach ($keyIndex as $userIds) {
            for ($i = 0; $i < count($userIds) - 1; $i++) {
                for ($j = $i + 1; $j < count($userIds); $j++) {
                    $a = $userById[$userIds[$i]];
                    $b = $userById[$userIds[$j]];
                    abort_unless($a instanceof User && $b instanceof User, 500);
                    // Different non-empty emails are a strong indicator these are distinct people
                    if ($a->email && $b->email && $a->email !== $b->email) {
                        continue;
                    }
                    $px = $find($userIds[$i]);
                    $py = $find($userIds[$j]);
                    if ($px !== $py) {
                        $parent[$px] = $py;
                    }
                }
            }
        }

        // Group users by union-find root
        $grouped = [];
        foreach ($allUsers as $user) {
            $grouped[$find($user->id)][] = $user;
        }

        $possibleDuplicates = [];
        $withoutDuplicates = [];
        /** @var Collection<int, User> $emptyDuplicates */
        $emptyDuplicates = collect();

        foreach ($grouped as $group) {
            $group = collect($group);
            if ($group->count() <= 1) {
                $user = $group->first();
                abort_unless($user instanceof User, 500);
                $user->duplicates = $emptyDuplicates;
                $user->fullNameText = $user->fullName(true);
                $withoutDuplicates[] = $user;

                continue;
            }

            // Official users (with accounts) are preferred as the keeper
            $sorted = $group->sortByDesc(fn ($u) => (int) $u->isOfficialUser)->values();
            $keeper = $sorted[0];
            abort_unless($keeper instanceof User, 500);
            $keeper->fullNameText = $keeper->fullName(true);
            $keeper->duplicates = $sorted->slice(1)->map(function ($u) use ($emptyDuplicates) {
                $u->fullNameText = $u->fullName(true);
                $u->duplicates = $emptyDuplicates;

                return $u;
            })->values();
            $possibleDuplicates[] = $keeper;
        }

        return Inertia::render('Admin/User/DuplicatesWizard', compact('possibleDuplicates', 'withoutDuplicates'));
    }

    /**
     * Merge the selected duplicate users into their target records.
     *
     * @param  Request  $request  Duplicate groups and optional name updates.
     * @return RedirectResponse User-list redirect.
     */
    public function fixDuplicates(Request $request)
    {
        Gate::authorize('fixDuplicates', User::class);

        $request->validate([
            'groups' => 'array',
            'groups.*.target_id' => 'required|integer',
            'groups.*.source_ids' => 'required|array',
            'groups.*.source_ids.*' => 'integer',
            'groups.*.name_update' => 'sometimes|array',
            'groups.*.name_update.title' => 'sometimes|nullable|string|max:255',
            'groups.*.name_update.first_name' => 'sometimes|nullable|string|max:255',
            'groups.*.name_update.last_name' => 'sometimes|nullable|string|max:255',
        ]);

        /** @var array<int, array{target_id: int, source_ids: array<int, int>, name_update?: array<string, string|null>}> $groups */
        $groups = $request->input('groups', []);
        foreach ($groups as $group) {
            $target = User::find($group['target_id']);
            if (! $target instanceof User) {
                continue;
            }

            /** @var array<string, string|null> $nameUpdate */
            $nameUpdate = $group['name_update'] ?? [];
            if (! empty($nameUpdate)) {
                /** @var array<string, mixed> $attributes */
                $attributes = array_filter($nameUpdate, fn ($v) => $v !== null);
                $target->fill($attributes)->save();
            }

            /** @var array<int, int> $sourceIds */
            $sourceIds = $group['source_ids'];
            foreach ($sourceIds as $sourceId) {
                $source = User::find($sourceId);
                if ($source instanceof User && $source->id !== $target->id) {
                    $source->mergeInto($target);
                    $source->delete();
                }
            }
        }

        return redirect()->route('users.index')->with('success', 'Die doppelten Personeneinträge wurden zusammengeführt.');
    }
}
