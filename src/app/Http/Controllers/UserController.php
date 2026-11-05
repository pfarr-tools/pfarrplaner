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

use App\Facades\Settings;
use App\HomeScreen\Tabs\HomeScreenTabFactory;
use App\Http\Requests\UserRequest;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\Location;
use App\Models\Parish;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

/**
 * Class UserController
 */
class UserController extends Controller
{
    use HandlesAttachedImageTrait;
    use HandlesAttachmentsTrait;

    protected $model = User::class;

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
        $userQuery = User::select(['id', 'first_name', 'last_name', 'title', 'email', 'password'])
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

        if (! Auth::user()->isAdmin) {
            if (Auth::user()->adminCities->count()) {
                // Local admin: can see anyone from his/her admin'd cities and people without homeCities
                $userQuery->where(function ($q) {
                    $q->whereHas('homeCities', function ($q2) {
                        $q2->whereIn('cities.id', Auth::user()->homeCities->pluck('id'));
                    });
                })->orWhere(function ($q) {
                    $q->whereDoesntHave('homeCities');
                });
            } elseif (Auth::user()->can('benutzer-bearbeiten')) {
                // Clerk: Can only see users
                $userQuery->where('password', '!=', '');
            } elseif (Auth::user()->can('benutzerliste-lokal-sehen')) {
                // Local clerk: Can only see users from own cities
                $cityIds = Auth::user()->writableCities->pluck('id');
                $userQuery->whereHas('cities', function ($q) use ($cityIds) {
                    $q->whereIn('cities.id', $cityIds);
                });
            } else {
                abort(403);
            }
        }

        $canCreate = Auth::user()->can('create', User::class);

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
            if ($city->administeredBy(Auth::user())) {
                $adminCities[] = $city;
            }
        }

        return Inertia::render(
            'Admin/User/UserEditor',
            [
                'user' => $this->compactEditorUser($user),
                'cities' => $this->compactOptions($cities),
                'adminCities' => $this->compactOptions($adminCities),
                'roles' => $this->compactRoles(Role::all()),
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
        if (Auth::user()->hasRole('Super-Administrator:in')) {
            $roles = Role::all();
        } else {
            $roles = Role::where('name', '!=', 'Super-Administrator:in')->get();
        }

        return $roles->sortBy('name');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(UserRequest $request)
    {
        Gate::authorize('create', User::class);
        $data = $request->validated();
        $user = User::create($data);
        $this->updateUserDataFromRequest($request, $user);

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
            if ($city->administeredBy(Auth::user())) {
                $adminCities[] = $city;
                $adminCityIds[] = $city->id;
            }
        }

        $cities = $user->homeCities->merge(collect($adminCities));

        $roles = Role::all()->sortBy('name')->reject(function ($item) {
            return $item->name == 'Super-Administrator:in';
        });

        $parishes = Parish::inCities(Auth::user()->adminCities->pluck('id'))->get();
        $homescreen = $user->getSetting('homeScreen', 'route:calendar');
        $users = $this->compactPeople();
        $subscriptions = Subscription::where('user_id', $user->id)
            ->inCities($adminCityIds)
            ->get();

        $homeScreenTabsConfig = $user->getSetting('homeScreenTabsConfig') ?? [];
        $settings = Settings::all($user);
        $availableTabs = HomeScreenTabFactory::available();
        $locations = $this->compactOptions($this->locationsForUser());
        $ministries = MinistryService::all();
        $modules = Modules::tree();

        $user = $this->compactEditorUser($user);
        $cities = $this->compactOptions($cities);
        $adminCities = $this->compactOptions($adminCities);
        $roles = $this->compactRoles($roles);
        $parishes = $this->compactOptions($parishes);

        $activeTab = $request->get('tab', 'home');

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
            )
        );
    }

    /**
     * Build the small representation needed by the user overview.
     *
     * @return array<string, mixed>
     */
    protected function compactIndexUser(User $user, Collection $allCities): array
    {
        $adminCities = $user->hasRole(AuthServiceProvider::SUPER)
            ? $allCities
            : $user->getRelation('adminCities');
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
            'home_cities' => $this->compactOptions($user->getRelation('homeCities')),
            'cities' => $this->compactOptions($user->getRelation('cities')),
            'writable_cities' => $this->compactOptions($writableCities),
            'admin_cities' => $this->compactOptions($adminCities),
            'roles' => $this->compactRoles($user->getRelation('roles')),
        ];
    }

    /**
     * Build the representation needed by the user editor without serializing the model.
     *
     * @return array<string, mixed>
     */
    protected function compactEditorUser(User $user): array
    {
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
            'home_cities' => $this->compactIds($user->getRelation('homeCities')),
            'parishes' => $this->compactIds($user->getRelation('parishes')),
            'roles' => $this->compactIds($user->getRelation('roles')),
            'cities' => $this->compactIds($user->getRelation('cities')),
            'writable_cities' => $this->compactIds($user->getRelation('writableCities')),
            'admin_cities' => $this->compactIds($user->getRelation('adminCities')),
            'vacation_admins' => $this->compactIds($user->getRelation('vacationAdmins')),
            'vacation_approvers' => $this->compactIds($user->getRelation('vacationApprovers')),
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
     * @return array<int, int|string>
     */
    protected function compactIds(iterable $records): array
    {
        return collect($records)->pluck('id')->values()->all();
    }

    /**
     * Convert roles and permissions to the fields used by the user interface.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function compactRoles(iterable $roles): array
    {
        return collect($roles)
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions
                    ->map(fn ($permission) => ['id' => $permission->id, 'name' => $permission->name])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Load only the location fields needed for home-screen configuration.
     */
    protected function locationsForUser(): Collection
    {
        return Location::query()
            ->without(['seatingSections', 'city'])
            ->select(['id', 'name'])
            ->inCities(Auth::user()->cities->pluck('id'))
            ->get();
    }

    /**
     * @return \Inertia\Response
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
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
        $locations = Location::inCities(Auth::user()->cities->pluck('id'))->get();
        $ministries = MinistryService::all();

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
            )
        );
    }

    /**
     * @return RedirectResponse
     */
    public function profileSave(Request $request)
    {
        $user = Auth::user();
        Gate::authorize('update', $user);
        $data = $this->validateRequest($request, $user);
        $user->update($data);

        // change password?
        if ($request->has('new_password')) {
            $passwordData = $request->validate([
                'current_password' => 'required|hash:'.Auth::user()->password,
                'new_password' => 'required|string|min:6|confirmed|not_current_password|notIn:testtest',
                'new_password_confirmation' => 'required',
            ]);
            $user->update(['password' => $passwordData['new_password']]);
        }

        // set subscriptions
        $user->setSubscriptionsFromArray($request->get('subscriptions') ?: []);

        // settings
        if ($request->has('settings')) {
            foreach ($request->get('settings', []) as $key => $setting) {
                $user->setSetting($key, $setting);
            }
        }

        if ($request->has('homeScreenTabsConfig')) {
            $user->setSetting('homeScreenTabsConfig', $request->get('homeScreenTabsConfig'));
        }

        return redirect()->route('home')->with('success', 'Die Änderungen wurden gespeichert.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UserRequest $request, User $user)
    {
        Gate::authorize('update', $user);
        $data = $request->validated();

        $user->update($data);
        if (($user->email == '') && ($user->password != '')) {
            // this only works with a raw query!
            DB::table('users')->where('id', $user->id)->update(['password' => null]);
            $user->update([
                'manage_absences' => 0,
                'needs_replacement' => 0,
                'show_vacations_with_services' => 0,
            ]);
            $user->cities()->sync([]);
            $user->writableCities()->sync([]);
            $user->adminCities()->sync([]);
            $user->roles()->sync([]);
            $user->permissions()->sync([]);
            $user->homeCities()->sync([]);
            $user->vacationAdmins()->sync([]);
            $user->vacationApprovers()->sync([]);
        }
        $this->updateUserDataFromRequest($request, $user);

        return redirect()->route('users.index')->with('success', 'Die Änderungen wurden gespeichert.');
    }

    /**
     * Update all user record relations from the request data
     */
    protected function updateUserDataFromRequest(UserRequest $request, User $user)
    {
        $user->homeCities()->sync(collect($request->getRelationIdsForSync('home_cities', 'cities'))->reject(function ($item) use ($user) {
            return ! ((($user->homeCities ?? collect())->pluck('id')->contains($item)) || (Auth::user()->adminCities->pluck('id')->contains($item)));
        }));
        $user->parishes()->sync($request->getRelationIdsForSync('parishes'));
        $user->syncRelatedUsers(
            'vacationAdmins',
            'vacation_admin',
            $request->getRelationIdsForSync('vacation_admins', 'users')
        );
        $user->syncRelatedUsers(
            'vacationApprovers',
            'vacation_approver',
            $request->getRelationIdsForSync('vacation_approvers', 'users')
        );
        $user->roles()->sync($request->getRoles());

        foreach ($request->get('settings', []) as $key => $setting) {
            $user->setSetting($key, $setting);
        }
        $user->setSubscriptionsFromArray($request->get('subscriptions') ?: []);
        $user->updateCityPermissions($request->get('permissions') ?: [], Auth::user());

        if ($request->get('createAccount', false)) {
            $user->resetAccount();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
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

        if ($user1 === null || $user2 === null) {
            return redirect()->route('home')->with('error', 'Ein Fehler ist aufgetreten.');
        }

        $user1->mergeInto($user2);

        // delete old user
        $user1->delete();

        return redirect()->route('users.index')->with('success', 'Die Benutzer wurden zusammengeführt.');
    }

    /**
     * @return RedirectResponse
     */
    public function switch(Request $request, User $user)
    {
        Gate::authorize('impersonate', $user);
        $adminId = Auth::user()->id;
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::login($user);
        $request->session()->regenerate();
        Session::put('adminUserSwitchBack', $adminId);

        // save switch in session!
        return $this->switchRedirectResponse($request);
    }

    public function switchBack(Request $request)
    {
        if (! Session::has('adminUserSwitchBack')) {
            abort(403);
        }
        $user = User::findOrFail(Session::get('adminUserSwitchBack'));
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
    protected function validateCityPermissions(Request $request)
    {
        // Kept for backward compatibility with older call sites.
    }

    /**
     * Validate submitted data
     *
     * @param  User|null  $user
     * @return array
     */
    protected function validateRequest(Request $request, $user = null, $withCity = false)
    {
        $rules = [
            'first_name' => 'nullable|string',
            'last_name' => 'required|string|max:255',
            'title' => 'nullable|string',
            'email' => 'nullable|string|email|max:255|unique:users,email'.($user ? ','.$user->id : ''),
            'password' => 'nullable|string',
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
        if (Auth::user()->isLocalAdmin) {
            // on create:
            if ($request->route()->getName() == 'user.store') {
                // check if at least one permission is set
                $rules['cityPermission'] = [new CreatedInLocalAdminDomainRule];

                // force setting a password
                $rules['email'] = 'required|email';
                $rules['password'] = 'required|string';
            }
        }

        // if a password is set, an email is required
        if ($request->get('password', '') != '') {
            $rules['email'] = 'required|email';
        }

        $data = $request->validate($rules);

        // api token
        $data['password'] = $data['password'] ?? '';
        if ((($user === null) || ($user->api_token == '')) && ($data['password'] != '')) {
            $data['api_token'] = Str::random(60);
        }

        return $data;
    }

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

        foreach ($grouped as $group) {
            $group = collect($group);
            if ($group->count() <= 1) {
                $user = $group->first();
                $user->duplicates = collect();
                $user->fullNameText = $user->fullName(true);
                $withoutDuplicates[] = $user;

                continue;
            }

            // Official users (with accounts) are preferred as the keeper
            $sorted = $group->sortByDesc(fn ($u) => (int) $u->isOfficialUser)->values();
            $keeper = $sorted[0];
            $keeper->fullNameText = $keeper->fullName(true);
            $keeper->duplicates = $sorted->slice(1)->map(function ($u) {
                $u->fullNameText = $u->fullName(true);
                $u->duplicates = collect();

                return $u;
            })->values();
            $possibleDuplicates[] = $keeper;
        }

        return Inertia::render('Admin/User/DuplicatesWizard', compact('possibleDuplicates', 'withoutDuplicates'));
    }

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

        foreach ($request->input('groups', []) as $group) {
            $target = User::find($group['target_id']);
            if (! $target) {
                continue;
            }

            if (! empty($group['name_update'])) {
                $target->fill(array_filter($group['name_update'], fn ($v) => $v !== null))->save();
            }

            foreach ($group['source_ids'] as $sourceId) {
                $source = User::find($sourceId);
                if ($source && $source->id !== $target->id) {
                    $source->mergeInto($target);
                    $source->delete();
                }
            }
        }

        return redirect()->route('users.index')->with('success', 'Die doppelten Personeneinträge wurden zusammengeführt.');
    }

    public function resetPassword(Request $request, User $user)
    {
        Gate::authorize('resetPassword', $user);
        $user->resetAccount();

        return redirect()->route('users.index')->with('success', 'Das Benutzerpasswort für '.$user->fullName().' wurde zurückgesetzt. Eine E-Mail mit neuen Zugangsdaten wurde an '.$user->email.' versandt.');
    }
}
