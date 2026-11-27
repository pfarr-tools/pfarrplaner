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

namespace App\Models\People;

use App\Facades\Settings;
use App\HomeScreens\AbstractHomeScreen;
use App\Models\Calendar\External\CalendarConnection;
use App\Models\Comment;
use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\Leave\Poolmaster;
use App\Models\Leave\Replacement;
use App\Models\Parish;
use App\Models\Places\City;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\UserSetting;
use App\Providers\AuthServiceProvider;
use App\Services\NameService;
use App\Services\RoleService;
use Carbon\Carbon;
use Database\Factories\People\UserFactory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use RoundlyConsulting\TwoFactor\Concerns\HasTwoFactorAuthentication;
use RoundlyConsulting\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * @property string|null $canEditFields Comma-separated editable field names.
 * @property bool $canEdit Whether the viewing user may edit this user.
 * @property Collection<int, User> $duplicates Potential duplicate accounts.
 * @property string $fullNameText Display name for duplicate lists.
 * @property mixed $Church Legacy church flag.
 * @property mixed $General Legacy general flag.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, City&object{pivot: Pivot&object{permission: string}}> $cities Cities with this user's access level.
 */
class User extends Authenticatable implements PasskeyUser, TwoFactorAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasTwoFactorAuthentication, Notifiable, PasskeyAuthenticatable;

    public const NAME_FORMAT_DEFAULT = 1;

    public const NAME_FORMAT_INITIAL_AND_LAST = 2;

    public const NAME_FORMAT_FIRST_AND_LAST = 3;

    /** @var string */
    public $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'title',
        'email',
        'is_official_user',
        'password',
        'notifications',
        'office',
        'address',
        'phone',
        'preference_cities',
        'canEditOfferings',
        'canEditCC',
        'new_features',
        'manage_absences',
        'api_token',
        'own_website',
        'own_podcast_title',
        'own_podcast_url',
        'own_podcast_spotify',
        'own_podcast_itunes',
        'show_vacations_with_services',
        'needs_replacement',
        'image',
        'must_change_password',
    ];

    /**
     * Return the casts for persisted user attributes, including two-factor
     * secrets and recovery-code storage supplied by the package.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'new_features' => 'datetime',
            'is_official_user' => 'boolean',
            'is_locked' => 'boolean',
            'security_onboarding_email_logins' => 'integer',
            'security_onboarding_last_shown_at' => 'datetime',
            ...$this->twoFactorCasts(),
        ];
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // this is for laravel-permissions, to always use web guard when matching permissions
    protected $appends = [
        'isOfficialUser',
        'isAdmin',
        'isSuperAdmin',
        'isLocalAdmin',
        'isPastor',
        'sortName',
        'name',
    ];

    /**
     * @var string
     */
    protected $orderBy = 'last_name';

    /**
     * @var string
     */
    protected $orderDirection = 'ASC';

    /** @var string[] cached user settings */
    protected $settings = [];

    // ACCESSORS

    /** Return the user's formatted full name. */
    public function getNameAttribute(): string
    {
        return $this->nameService()->format(NameService::FIRST_LAST);
    }

    /**
     * @return City[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getAdminCitiesAttribute()
    {
        if ($this->hasRole(AuthServiceProvider::SUPER)) {
            return City::all();
        }

        return $this->adminCities()->get();
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function adminCities()
    {
        return $this->belongsToMany(City::class)->withPivot('permission')->wherePivotIn('permission', ['a']);
    }

    /**
     * @return list<string>
     */
    public function getAllPermissionsAttribute()
    {
        $permissions = [];
        $currentUser = Auth::user();
        foreach (Permission::all() as $permission) {
            if ($currentUser?->can($permission->name)) {
                $permissions[] = $permission->name;
            }
        }

        return $permissions;
    }

    /**
     * @return bool
     */
    public function getIsAdminAttribute()
    {
        return $this->hasRole(RoleService::ROLE_ADMIN) || $this->hasRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /** Return whether the user has the global super-administrator role. */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /**
     * @return bool
     */
    public function getIsLocalAdminAttribute()
    {
        return (! $this->isAdmin) && (count($this->adminCities) > 0);
    }

    /** Return the persisted classification for an official user account. */
    public function getIsOfficialUserAttribute(): bool
    {
        return (bool) ($this->attributes['is_official_user'] ?? false);
    }

    /** Return whether an administrator has blocked this account's web access. */
    public function getIsLockedAttribute(): bool
    {
        return (bool) ($this->attributes['is_locked'] ?? false);
    }

    /**
     * @return bool
     */
    public function getIsPastorAttribute()
    {
        return $this->hasRole(RoleService::ROLE_PASTOR);
    }

    /**
     * @return string
     */
    public function getPlanNameAttribute()
    {
        return $this->lastName(true);
    }

    /**
     * @param  bool  $withTitle
     * @return string
     */
    public function lastName($withTitle = false)
    {
        return ($withTitle ? ($this->title ? $this->title.' ' : '') : '').$this->last_name;
    }

    /** Return the family-name-first sorting label.
     * @return string
     */
    public function getSortNameAttribute()
    {
        return $this->last_name.($this->first_name ? ', '.$this->first_name : '');
    }

    /**
     * @return City[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getWritableCitiesAttribute()
    {
        if (Auth::user()?->hasRole(AuthServiceProvider::SUPER)) {
            return City::all();
        }
        $writableCities = $this->writableCities()->get();
        $replacements = $this->currentReplacements();
        if ($replacements !== null) {
            foreach ($replacements as $replacement) {
                $absenceOwner = $replacement->absence?->user;
                if ($absenceOwner !== null) {
                    foreach ($absenceOwner->writableCities as $city) {
                        $writableCities->push($city);
                    }
                }
            }
        }

        return $writableCities->unique();
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function writableCities()
    {
        return $this->belongsToMany(City::class)->withPivot('permission')->wherePivotIn('permission', ['w', 'a']);
    }

    /** Return replacements currently assigned to this user.
     * @return \Illuminate\Database\Eloquent\Collection<int, Replacement>
     */
    public function currentReplacements()
    {
        return Replacement::with('absence')
            ->whereHas('users', function ($query) {
                $query->where('user_id', $this->id);
            })
            ->where('from', '<=', Carbon::now())
            ->where('to', '>=', Carbon::now())
            ->get();
    }

    /** Return pools currently mastered by this user.
     * @return \Illuminate\Database\Eloquent\Collection<int, Poolmaster>
     */
    public function currentlyMasteredPools()
    {
        return Poolmaster::with('currentReplacements')
            ->where('user_id', $this->id)
            ->where('start', '<=', Carbon::now())
            ->where('end', '>=', Carbon::now())
            ->get();
    }
    // END ACCESSORS

    // MUTATORS
    /**
     * Automatically hash password
     *
     * @param  string  $value  Plain-text password.
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = $value;

            return;
        }

        // Laravel's authentication provider may assign an already rehashed
        // password hash to the model after a successful login. Do not hash
        // that hash a second time.
        $this->attributes['password'] = password_get_info((string) $value)['algoName'] !== 'unknown'
            ? $value
            : Hash::make($value);
    }
    // END MUTATORS

    // SCOPES
    /**
     * Query for users subscribed to a specific service
     *
     * This requires one of two condition sets to be true:
     * 1) User is subscribed to all services for this city
     * 2) User is subscribed to own services for this city AND is a participant in this service
     *
     * @param  bool  $isNew  True, if this is a newly created service
     * @param  Builder<static>  $query  Query being filtered.
     * @return mixed
     */
    public function scopeSubscribedTo(Builder $query, Service $service, $isNew = false)
    {
        $query->whereHas(
            'subscriptions',
            function ($query) use ($service) {
                $query->where('city_id', $service->city_id);
                $query->where('subscription_type', Subscription::SUBSCRIBE_ALL);
            }
        )->orWhere(
            function ($query) use ($service) {
                $query->whereIn('id', $service->participants->pluck('id'));
                $query->whereHas(
                    'subscriptions',
                    function ($query) use ($service) {
                        $query->where('city_id', $service->city_id);
                        $query->where('subscription_type', Subscription::SUBSCRIBE_OWN);
                    }
                );
            }
        );

        // some users only get notified when time or day_id change
        $changed = $service->changed;
        if ($isNew || isset($changed['time']) || isset($changed['timeText']) || isset($changed['day_id'])) {
            $query->orWhereHas(
                'subscriptions',
                function ($query) use ($service) {
                    $query->where('city_id', $service->city_id);
                    $query->where('subscription_type', Subscription::SUBSCRIBE_TIME_CHANGES);
                }
            );
        }

        return $query;
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeVisibleFor(Builder $query, User $user)
    {
        return $query->whereHas('cityScopes', function ($q) use ($user) {
            $q->whereIn('city_id', $user->cities->pluck('id'));
        });
    }

    // END SCOPES
    // SETTERS
    /**
     * @param  int|string  $name  Identifier or name of the user.
     * @return int|string User identifier.
     */
    public static function createIfNotExists($name)
    {
        if ((! is_numeric($name)) || (User::find($name) === false)) {
            $title = $firstName = $lastName = '';
            if (strpos($name, '_') === false) {
                // split participant name into its parts
                $tmp = explode(' ', $name);
                if (count($tmp) == 3) {
                    $title = array_shift($tmp);
                }
                if (count($tmp) == 2) {
                    $firstName = array_shift($tmp);
                }
                $lastName = array_shift($tmp);
            } elseif ((substr($name, 0, 1) == '"') && (substr($name, -1, 1) == '"')) {
                // allow submitting participant names in double quotes ("participant name"), which will prevent splitting
                $name = substr($name, 1, -1);
            } else {
                // allow submitting participant name with an underscore, which will prevent splitting
                $name = str_replace('_', ' ', $name);
            }
            $user = new User(
                [
                    'office' => '',
                    'phone' => '',
                    'address' => '',
                    'preference_cities' => '',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'title' => $title,
                ]
            );
            $user->save();
            $name = $user->id;
        }

        return $name;
    }

    /** Register the default ordering global scope. */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('last_name', 'asc')->orderBy('first_name', 'asc');
        });
    }

    /**
     * @return mixed
     */
    public function absencesToBeApproved()
    {
        $approvableUsers = $this->approvableUsers();

        return Absence::whereIn('user_id', $approvableUsers->pluck('id'))
            ->where('status', 'pending')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function approvableUsers()
    {
        $id = $this->id;

        return User::whereHas(
            'approvers',
            function ($query) use ($id) {
                $query->where('approver_id', $id);
            }
        )->get();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function approvers()
    {
        return $this->belongsToMany(User::class, 'user_approver', 'user_id', 'approver_id');
    }

    /**
     * @return HasMany<CalendarConnection, $this>
     */
    public function calendarConnections()
    {
        return $this->hasMany(CalendarConnection::class);
    }

    /**
     * Check if the user has at least one of an array of permissions
     *
     * @param  string[]  $permissions
     * @param  mixed  $data
     * @return bool
     */
    public function canAny($permissions, $data = null)
    {
        $can = false;
        foreach ($permissions as $permission) {
            $can = $can || $this->can($permission, $data);
        }

        return $can;
    }

    /**
     * @param  string  $area  Area or field to check.
     * @return bool
     */
    public function canEdit($area)
    {
        if (in_array($area, ['general', 'church'])) {
            $property = ucfirst($area);

            return $this->isAdmin || $this->$property;
        } else {
            return $this->canEditField($area);
        }
    }

    /**
     * @param  string  $field  Field to check.
     * @return bool
     */
    public function canEditField($field)
    {
        return $this->isAdmin || in_array($field, $this->getEditableFields() ?: []);
    }

    /**
     * @return false|string[]
     */
    public function getEditableFields()
    {
        return explode(',', $this->canEditFields ?? '');
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function cityScopes()
    {
        return $this->belongsToMany(City::class, 'user_scopes');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Ensure certain user settings are present, if not, set a sensible default
     *
     * @return void
     */
    public function ensureDefaultSettings()
    {
        $needReload = false;
        $settings = Settings::all($this);
        /** @var array<string, mixed> $defaults */
        $defaults = config('user-settings.defaults');
        foreach ($defaults as $key => $defaultSetting) {
            if (! isset($settings[$key])) {
                $settings[$key] = $defaultSetting;
                Settings::set($this, $key, $defaultSetting);
            } else {
                if (is_array($defaultSetting)) {
                    if (is_array($settings[$key])) {
                        $settings[$key] = array_replace_recursive($defaultSetting, $settings[$key]);
                    } else {
                        $settings[$key] = $defaultSetting;
                    }
                    Settings::set($this, $key, $settings[$key]);
                }
            }
        }
    }

    /**
     * @param  string  $key  Setting key.
     * @return void
     *
     * @throws Exception
     */
    public function forgetSetting($key)
    {
        if ($this->hasSetting($key)) {
            /** @var UserSetting $setting */
            $setting = $this->getSetting($key, null, true);
            $setting->delete();
        }
    }

    /**
     * @param  string  $key  Setting key.
     * @return bool
     */
    public function hasSetting($key)
    {
        return Settings::has($this, $key);
    }

    /**
     * @param  null  $default
     * @param  bool  $returnObject
     * @param  bool  $unserialize
     * @return UserSetting|mixed
     */
    /**
     * Read a user setting with an arbitrary fallback value.
     *
     * @param  string  $key  Setting key.
     * @param  mixed  $default  Value returned when the setting is absent.
     * @param  bool  $returnObject  Whether the setting model is returned.
     * @return mixed
     */
    public function getSetting($key, $default = null, $returnObject = false)
    {
        /** @var null $default */
        $settingDefault = $default;

        return Settings::get($this, $key, $settingDefault, $returnObject);
    }

    /**
     * Get the user's name in one of the predefined formats
     *
     * @param  int  $format
     * @param  bool  $withTitle
     * @return string
     */
    public function formattedName($format = self::NAME_FORMAT_DEFAULT, $withTitle = true)
    {
        switch ($format) {
            case self::NAME_FORMAT_INITIAL_AND_LAST:
                return $this->initialedName($withTitle);
            case self::NAME_FORMAT_FIRST_AND_LAST:
                return $this->fullName($withTitle);
        }

        return $this->lastName($withTitle);
    }

    /**
     * @param  bool  $withTitle
     * @return string
     */
    public function initialedName($withTitle = false)
    {
        return ($withTitle ? ($this->title ? $this->title.' ' : '') : '')
            .($this->first_name ? substr($this->first_name, 0, 1).'app ' : '')
            .$this->last_name;
    }

    /**
     * @param  bool  $withTitle
     * @return string
     */
    public function fullName($withTitle = false)
    {
        return $this->nameService()->format(
            $withTitle ? NameService::TITLE_FIRST_LAST : NameService::FIRST_LAST
        );
    }

    /**
     * Get an instance of the HomeScreen for this user
     *
     * @return AbstractHomeScreen|null
     */
    public function getHomeScreen()
    {
        /** @var string $homeScreen */
        $homeScreen = $this->getSetting('homeScreen', 'route:calendar');
        if (substr($homeScreen, 0, 11) == 'homescreen:') {
            $homeScreenClass = 'App\\HomeScreens\\'.ucfirst(substr($homeScreen, 11)).'HomeScreen';
            if (class_exists($homeScreenClass) && is_subclass_of($homeScreenClass, AbstractHomeScreen::class)) {
                return new $homeScreenClass;
            }
        }

        return null;
    }

    /** Return the model field used for its profile image.
     * @return string
     */
    public function getImageField()
    {
        return 'image';
    }

    /**
     * @return mixed
     */
    public function getSortedCities()
    {
        if ($this->hasSetting('sorted_cities')) {
            /** @var string $sortedCities */
            $sortedCities = $this->getSetting('sorted_cities');
            $ids = explode(',', $sortedCities);

            return City::whereIn('id', $ids)->get()->sortBy(
                function ($model) use ($ids) {
                    return array_search($model->getKey(), $ids);
                }
            );
        } else {
            // default if user preference not set:
            $cities = $this->visibleCities;
            /** @var array<int, string> $cityIds */
            $cityIds = $cities->pluck('id')->toArray();
            $this->setSetting('sorted_cities', implode(',', $cityIds));

            return $cities;
        }
    }

    /** Persist one user setting.
     * @param  string  $key  Setting key.
     * @param  mixed  $value  Value to store.
     * @return mixed
     */
    public function setSetting($key, $value)
    {
        return Settings::set($this, $key, $value);
    }

    /**
     * Get the users subscription type for a city
     *
     * @param  int|City  $city  City or its identifier.
     * @return int
     */
    public function getSubscriptionType($city)
    {
        $subscription = $this->getSubscription($city)->subscription_type;

        return $subscription;
    }

    /**
     * Get a single subscription for a specific city
     *
     * If no subscription is present yet, it will be created with type SUBSCRIBE_NONE
     *
     * @param  int|City  $city
     * @return Subscription
     */
    public function getSubscription($city)
    {
        $cityId = is_int($city) ? $city : $city->id;
        $subscription = $this->subscriptions()->where('city_id', $cityId)->first();
        if ($subscription === null) {
            $subscription = new Subscription(
                [
                    'user_id' => $this->id,
                    'city_id' => $cityId,
                    'subscription_type' => Subscription::SUBSCRIBE_OWN,
                ]
            );
            $subscription->save();
        }

        return $subscription;
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->api_token;
    }

    /**
     * Find all users for which this user may see the absences
     *
     * Permission logic:
     * (A) Pastors (role Pastor:in, Diakon:in) may see all fellow pastors in their district (cities with view rights), all staff in their home cities, and pastors sharing a pool
     * (B) Staff (users with manage_absences flag set) may see all fellow staff in their home city
     * (C) Users with pfarrer_urlaub_lesen may see pastors from explicitly assigned visibility pools
     * (D) Users without the manage_absences flag see nothing at all
     *
     * @return Collection<int, User>
     */
    public function getViewableAbsenceUsers()
    {
        // (C) Users without the manage_absences flag see nothing at all
        if (! $this->manage_absences) {
            return new Collection;
        }

        // always include the user
        /** @var array<int|string> $ids */
        $ids = [$this->id];

        // (B) Staff (users with manage_absences flag set) may see all fellow staff in their home city
        /** @var array<int|string> $newIds */
        $newIds = User::whereHas(
            'homeCities',
            function ($query) {
                $query->whereIn('cities.id', $this->homeCities->pluck('id'));
            }
        )->where('manage_absences', 1)
            ->pluck('id')->all();
        $ids = array_merge($ids, $newIds);

        // (A) Pastors (role Pastor:in, Diakon:in) may also see all fellow pastors in their district (cities with view rights)
        if ($this->hasRole('Pfarrer:in') || $this->hasRole('Diakon:in')) {
            /** @var array<int|string> $newIds */
            $newIds = User::where(
                function ($query2) {
                    $query2->whereHas(
                        'roles',
                        function ($query) {
                            $query->whereIn('name', ['Pfarrer:in', 'Diakon:in']);
                        }
                    );
                    $query2->whereHas(
                        'homeCities',
                        function ($query) {
                            $query->whereIn('cities.id', $this->cities->pluck('id'));
                        }
                    );
                }
            )->pluck('id')->all();
            $ids = array_merge($ids, $newIds);

            $poolIds = $this->pools()->pluck('pools.id');
            if ($poolIds->isNotEmpty()) {
                /** @var array<int|string> $newIds */
                $newIds = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['Pfarrer:in', 'Diakon:in']);
                })->whereHas('pools', function ($query) use ($poolIds) {
                    $query->whereIn('pools.id', $poolIds);
                })->pluck('id')->all();
                $ids = array_merge($ids, $newIds);
            }
        } elseif ($this->hasPermissionTo('pfarrer_urlaub_lesen')) {
            $poolIds = $this->visiblePools()->pluck('pools.id');
            if ($poolIds->isNotEmpty()) {
                /** @var array<int|string> $newIds */
                $newIds = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['Pfarrer:in', 'Diakon:in']);
                })->whereHas('pools', function ($query) use ($poolIds) {
                    $query->whereIn('pools.id', $poolIds);
                })->pluck('id')->all();
                $ids = array_merge($ids, $newIds);
            }
        }

        $ids = array_unique($ids);

        $userQuery = User::whereIn('id', $ids);
        $userQuery->orderBy('last_name');
        $userQuery->orderBy('first_name');

        $users = $userQuery->get();

        return $users;
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function homeCities()
    {
        return $this->belongsToMany(City::class, 'user_home');
    }

    /**
     * @param  bool  $returnAbsence
     * @param  mixed  $date  Date to check.
     * @return bool|Absence|null
     *
     * @throws Exception
     */
    public function isAbsent($date, $returnAbsence = false)
    {
        if (! $this->manage_absences) {
            return $returnAbsence ? null : false;
        }
        if (! $date instanceof Carbon) {
            /** @var \DateTimeInterface|float|int|string|null $dateValue */
            $dateValue = $date;
            $date = new Carbon($dateValue);
        }
        $absences = Absence::with('user')
            ->where('user_id', $this->id)
            ->where('from', '<=', $date)
            ->where('to', '>=', $date)
            ->first();

        return ($absences !== null) ? ($returnAbsence ? $absences : null) : ($returnAbsence ? null : false);
    }

    /**
     * @param  bool  $returnServices
     * @param  mixed  $date  Date to check.
     * @return \Illuminate\Database\Eloquent\Collection<int, Service>|bool|null
     */
    public function isBusy($date, $returnServices = false)
    {
        $services = Service::whereHas(
            'day',
            function ($query) use ($date) {
                $query->where('date', $date);
            }
        )->whereHas(
            'participants',
            function ($query) {
                $query->where('user_id', $this->id);
            }
        )->get();

        return count($services) ? ($returnServices ? $services : null) : ($services ? null : false);
    }

    /**
     * @param  bool  $returnAbsence
     * @param  mixed  $date  Date to check.
     * @return Absence|bool|null
     *
     * @throws Exception
     */
    public function isReplacement($date, $returnAbsence = false)
    {
        if (! $this->manage_absences) {
            return $returnAbsence ? null : false;
        }
        if (! $date instanceof Carbon) {
            /** @var \DateTimeInterface|float|int|string|null $dateValue */
            $dateValue = $date;
            $date = new Carbon($dateValue);
        }
        $absences = Absence::where('replacement', $this->id)
            ->where('from', '<=', $date)
            ->where('to', '>=', $date)
            ->first();

        return ($absences !== null) ? ($returnAbsence ? $absences : null) : ($returnAbsence ? null : false);
    }

    /** Merge this user into another account and transfer its associations.
     * @return void
     */
    public function mergeInto(User $user)
    {
        if ((! $user->isOfficialUser) && $this->isOfficialUser) {
            $user->roles()->sync($this->roles->pluck('id'));
            $user->permissions()->sync($this->permissions->pluck('id'));
            $user->update([
                'manage_absences' => $this->manage_absences,
                'preference_cities' => $this->preference_cities,
            ]);
        }

        // Additive city merge: all cities from both users, keep the better permission for shared cities
        $permissionRank = ['n' => 0, 'r' => 1, 'w' => 2, 'a' => 3];
        foreach ($this->cities as $city) {
            $sourcePermission = $city->pivot->permission;
            $targetCity = $user->cities()->where('cities.id', $city->id)->first();
            if ($targetCity === null) {
                $user->cities()->attach($city->id, ['permission' => $sourcePermission]);
            } elseif (($permissionRank[$sourcePermission] ?? 0) > ($permissionRank[$targetCity->pivot->permission] ?? 0)) {
                $user->cities()->updateExistingPivot($city->id, ['permission' => $sourcePermission]);
            }
        }

        // Merge pivot memberships additively
        $user->cityScopes()->syncWithoutDetaching($this->cityScopes->pluck('id')->toArray());
        $user->homeCities()->syncWithoutDetaching($this->homeCities->pluck('id')->toArray());
        $user->pools()->syncWithoutDetaching($this->pools->pluck('id')->toArray());

        // Transfer direct FK associations
        Absence::where('user_id', $this->id)->update(['user_id' => $user->id]);
        CalendarConnection::where('user_id', $this->id)->update(['user_id' => $user->id]);
        Comment::where('user_id', $this->id)->update(['user_id' => $user->id]);
        Participant::where('user_id', $this->id)->update(['user_id' => $user->id]);
        Subscription::where('user_id', $this->id)->update(['user_id' => $user->id]);
        UserSetting::where('user_id', $this->id)->update(['user_id' => $user->id]);

        // Merge parishes — syncWithoutDetaching avoids duplicate pivot rows
        foreach ($this->parishes as $parish) {
            /** @var Parish $parish */
            $parish->users()->detach($this->id);
            $parish->users()->syncWithoutDetaching([$user->id]);
        }

        // Merge teams — syncWithoutDetaching avoids duplicate pivot rows
        foreach ($this->teams as $team) {
            /** @var Team $team */
            $team->users()->detach($this->id);
            $team->users()->syncWithoutDetaching([$user->id]);
        }

        // Transfer replacement assignments (BelongsToMany pivot, no user_id FK on replacements)
        $srcUser = $this;
        Replacement::whereHas('users', function ($query) use ($srcUser) {
            $query->where('user_id', $srcUser->id);
        })->get()->each(function (Replacement $replacement) use ($user, $srcUser) {
            $replacement->users()->detach($srcUser->id);
            $replacement->users()->syncWithoutDetaching([$user->id]);
        });
    }

    /**
     * Cities to which the user has at least read access.
     *
     * @return BelongsToMany<City, $this>
     */
    public function cities()
    {
        return $this->belongsToMany(City::class)
            ->withPivot('permission');
    }

    /**
     * @return BelongsToMany<Parish, $this>
     */
    public function parishes()
    {
        return $this->belongsToMany(Parish::class)->withTimestamps();
    }

    /**
     * @param  City|int|string  $city  City or its identifier.
     * @return string|null
     */
    public function permissionForCity($city)
    {
        if (is_numeric($city)) {
            $city = City::find($city);
        }

        /** @var City $city */
        return $this->cities()->where('cities.id', $city->id)->first()->pivot->permission ?? 'n';
    }

    /**
     * @return BelongsToMany<Pool, $this>
     */
    public function pools()
    {
        return $this->belongsToMany(Pool::class);
    }

    /** Return pools whose pastors this user may see in the absence planner.
     * @return BelongsToMany<Pool, $this>
     */
    public function visiblePools()
    {
        return $this->belongsToMany(Pool::class, 'pool_user_visibility')->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function relatedUsers()
    {
        return $this->belongsToMany(User::class, 'user_user', 'user_id', 'related_user_id')->withPivot('relation');
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services()
    {
        return $this->belongsToMany(Service::class)
            ->withTimestamps()
            ->withPivot('category');
    }

    /** Replace city subscriptions from a city/type map.
     * @param  array<int|string, mixed>  $subscriptions  Subscription settings.
     * @return void
     */
    public function setSubscriptionsFromArray($subscriptions)
    {
        foreach ($subscriptions as $city => $type) {
            /** @var int|string $type */
            $typeId = $type;
            $this->setSubscription((int) $city, (int) $typeId);
        }
    }

    /**
     * Set the user's subscription for a city
     *
     * @param  City|int  $city  City
     * @param  int  $type  Subscription type
     * @return Subscription
     */
    public function setSubscription($city, int $type)
    {
        $city = is_int($city) ? City::query()->findOrFail($city) : $city;
        $subscription = $this->getSubscription($city);
        if ($subscription === null) {
            $subscription = new Subscription(
                [
                    'user_id' => $this->id,
                    'city_id' => $city->id,
                ]
            );
        }
        $subscription->subscription_type = $type;
        $subscription->save();

        return $subscription;
    }

    /**
     * Sync related users list for a specific kind of relation
     *
     * @param  string  $relation  Relation name.  Kind of relation
     * @param  string  $relationString  Pivot value.  Relation pivot string
     * @param  array<int, int>  $userIds  User identifiers.  User ids
     * @return void
     */
    public function syncRelatedUsers($relation, $relationString, $userIds)
    {
        /** @var BelongsToMany<User, $this> $relationship */
        $relationship = $this->$relation();
        $relationship->sync([]);
        foreach ($userIds as $userId) {
            $relationship->attach([$userId => ['relation' => $relationString]]);
        }
    }

    /** Return teams assigned to the user.
     * @return BelongsToMany<Team, $this>
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    /**
     * Update this users permissions from an array
     * Format [ city_id => ['permission' => level]]
     *
     * @param  array<int|string, mixed>  $permissions  Permissions by city.
     * @return void
     */
    public function updateCityPermissions($permissions, ?User $actingUser = null)
    {
        $actingUser ??= $this;

        // change array format
        foreach ($permissions as $cityId => $permission) {
            $permissions[$cityId] = ['permission' => $permission];
        }
        // check user rights, remove entries for cities without admin rights
        foreach ($permissions as $cityId => $permission) {
            $city = City::find($cityId);
            if (! $city || ! $city->administeredBy($actingUser)) {
                unset($permissions[$cityId]);
            }
        }

        // add missing cities
        foreach ($this->visibleCities as $city) {
            if (! isset($permissions[$city->id])) {
                $permissions[$city->id] = ['permission' => $city->pivot->permission];
            }
        }

        foreach ($permissions as $cityId => $permission) {
            if ($permission['permission'] == 'n') {
                unset($permissions[$cityId]);
            }
        }
        $result = $this->cities()->sync($permissions);

        // update the user's own sorting (remove cities which are not allowed)
        /** @var string $sortedCities */
        $sortedCities = $this->getSetting('sorted_cities', '');
        $thisPref = explode(',', $sortedCities);
        if (! count($thisPref)) {
            /** @var array<string> $thisPref */
            $thisPref = $result['attached'];
        } else {
            foreach ($thisPref as $key => $city) {
                if (in_array($city, $result['detached'])) {
                    unset($thisPref[$key]);
                }
            }
            // make added cities visible without having to edit user preference in profile:
            /** @var array<string> $thisPref */
            $thisPref = array_merge($thisPref, $result['attached']);
        }
        $this->setSetting('sorted_cities', implode(',', $thisPref));

        // update the user's subscriptions (remove cities which are not allowed)
        $subscriptions = $this->subscriptions;
        foreach ($subscriptions as $subscription) {
            if (in_array($subscription->city_id, $result['detached'])) {
                $subscription->delete();
            }
        }
    }

    /**
     * Check if another user has admin rights for this user
     * This is the case, if the other user administers one of this users homeCities
     *
     * @param  User  $user  Potential administrator.  User User to be checked for admin rights
     * @return bool
     */
    public function administeredBy($user)
    {
        if ($user->hasRole('Super-Administrator:in')) {
            return true;
        }
        foreach ($this->homeCities as $city) {
            if ($city->administeredBy($user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return HasMany<UserSetting, $this>
     */
    public function userSettings()
    {
        return $this->hasMany(UserSetting::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function vacationAdmins()
    {
        return $this->belongsToMany(User::class, 'user_user', 'user_id', 'related_user_id')->wherePivot(
            'relation',
            '=',
            'vacation_admin'
        );
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function vacationApprovers()
    {
        return $this->belongsToMany(User::class, 'user_user', 'user_id', 'related_user_id')->wherePivot(
            'relation',
            'vacation_approver'
        );
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function visibleCities()
    {
        return $this->cities();
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function writableCitiesWithoutAdmin()
    {
        return $this->belongsToMany(City::class)->withPivot('permission')->wherePivotIn('permission', ['w']);
    }

    /** Build the user's name-formatting service. */
    public function nameService(): NameService
    {
        return NameService::fromUser($this);
    }

    /**
     * @param  int  $format  Name format constant.
     * @return ($format is NameService::LAST_FIRST_ARRAY ? list<string> : string)
     */
    public function formatName($format)
    {
        return $this->nameService()->format($format);
    }

    /** Determine whether the user is an active poolmaster for a city today.
     * @return bool
     */
    public function isCurrentlyPoolmasterForCity(City $city)
    {
        return Poolmaster::where('user_id', $this->id)
            ->whereHas('pool', function ($query) use ($city) {
                $query->whereHas('cities', function ($query2) use ($city) {
                    $query2->where('city_id', $city->id);
                });
            })->where('start', '<=', now()->endOfDay())->where('end', '>=', now()->startOfDay())->count() > 0;
    }
}
