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

namespace App\Models\Places;

use App\Models\AbstractModel;
use App\Models\Ads\AdChannel;
use App\Models\Leave\Pool;
use App\Models\Location;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Class City
 * @package App
 */
class City extends AbstractModel
{

    protected static string $prefix = 'kirchengemeinde';
    protected static string $prefixPlural = 'kirchengemeinden';
    public static array $exceptRoutes = ['web' => ['show'], 'api' => ['show']];
    public static array $validationRules = [
        'name' => 'required|max:255',
        'public_events_calendar_url' => 'nullable',
        'default_offering_goal' => 'nullable',
        'default_offering_description' => 'nullable',
        'default_funeral_offering_goal' => 'nullable',
        'default_funeral_offering_description' => 'nullable',
        'default_wedding_offering_goal' => 'nullable',
        'default_wedding_offering_description' => 'nullable',
        'op_domain' => 'nullable',
        'op_customer_key' => 'nullable',
        'op_customer_token' => 'nullable',
        'podcast_title' => 'nullable|string',
        'podcast_logo' => 'nullable|string',
        'sermon_default_image' => 'nullable|string',
        'homepage' => 'nullable|string',
        'podcast_owner_name' => 'nullable|string',
        'podcast_owner_email' => 'nullable|string',
        'google_auth_code' => 'nullable|string',
        'google_access_token' => 'nullable|string',
        'google_refresh_token' => 'nullable|string',
        'youtube_channel_url' => 'nullable|string',
        'konfiapp_apikey' => 'nullable|string',
        'youtube_active_stream_id' => 'nullable|string',
        'youtube_passive_stream_id' => 'nullable|string',
        'youtube_auto_startstop'=> 'nullable|int',
        'youtube_cutoff_days'=> 'nullable|int',
        'default_offering_url' => 'nullable|string',
        'youtube_self_declared_for_children'=> 'nullable|int',
        'communiapp_url' => 'nullable|string',
        'communiapp_token' => 'nullable|string',
        'communiapp_default_group_id'=> 'nullable|int',
        'communiapp_use_outlook'=> 'nullable|int',
        'communiapp_use_op'=> 'nullable|int',
        'konfiapp_default_type' => 'nullable|string',
        'official_name' => 'nullable|string',
        'default_ministries' => 'nullable',
        'iban' => 'nullable|string',
        'bic' => 'nullable|string',
        'is_org' => 'nullable|bool',
        'parent_id' => 'nullable|int|exists:cities,id',
        'childIds' => 'nullable|array',
        'childIds.*' => 'nullable|int|exists:cities,id',
    ];

    public static $adminIcon = 'mdi mdi-church';
    public static $adminGroup = 'Orte';



    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'public_events_calendar_url',
        'default_offering_goal',
        'default_offering_description',
        'default_funeral_offering_goal',
        'default_funeral_offering_description',
        'default_wedding_offering_goal',
        'default_wedding_offering_description',
        'op_domain',
        'op_customer_key',
        'op_customer_token',
        'podcast_title',
        'podcast_logo',
        'sermon_default_image',
        'homepage',
        'podcast_owner_name',
        'podcast_owner_email',
        'google_auth_code',
        'google_access_token',
        'google_refresh_token',
        'youtube_channel_url',
        'konfiapp_apikey',
        'youtube_active_stream_id',
        'youtube_passive_stream_id',
        'youtube_auto_startstop',
        'youtube_cutoff_days',
        'default_offering_url',
        'youtube_self_declared_for_children',
        'communiapp_url',
        'communiapp_token',
        'communiapp_default_group_id',
        'communiapp_use_outlook',
        'communiapp_use_op',
        'konfiapp_default_type',
        'official_name',
        'logo',
        'default_ministries',
        'iban',
        'bic',
        'parent_id',
        'is_org',
    ];

    /**
     * @var string
     */
    protected $orderBy = 'name';
    /**
     * @var string
     */
    protected $orderDirection = 'ASC';

    /**
     * @return HasMany
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pools()
    {
        return $this->belongsToMany(Pool::class);
    }

    /**
     * @return HasManyThrough
     */
    public function services()
    {
        return $this->hasManyThrough(Service::class, Location::class);
    }

    /**
     * @return HasMany
     */
    public function parishes()
    {
        return $this->hasMany(Parish::class);
    }


    public function pastors()
    {
        return $this->belongsToMany(User::class, 'user_home')->role('Pfarrer:in');
    }

    /**
     * @return HasMany
     */
    public function adChannels()
    {
        return $this->hasMany(AdChannel::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function children() {
        return $this->hasMany(self::class, 'parent_id');
    }


    /**
     * Check if this city is administered by a particular use
     * @param User $user User
     * @return bool True if user has admin rights here
     */
    public function administeredBy(User $user)
    {
        if ($user->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            return true;
        }
        $city = $user->cities->where('id', $this->id)->first();
        if ($city && ($city->pivot->permission == 'a')) {
            return true;
        }
        return false;
    }

    /**
     * Get the default ad channels for this city
     * @return \Illuminate\Support\Collection
     */
    public function getDefaultAdChannels()
    {
        $channels = [];
        foreach (config('ads.channels') as $key => $channelConfig) {
            if ($channelConfig['depends_on'] ?? false) {
                /*
                    some adChannels have a dependency on a specific property of the City model
                    e.g. a CommuniApp AdChannel will only be shown if the city has a communiapp_token
                */
                if ($this->{$channelConfig['depends_on']}) $channels[$key] = $channelConfig;
            } else {
                $channels[$key] = $channelConfig;
            }
        }
        return collect($channels);
    }

    public function getActiveAdChannels()
    {
        return $this->getDefaultAdChannels()
            ->merge($this->adChannels->keyBy('slug'))
            ->sortBy('name');

    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function scopedUsers()
    {
        return $this->belongsToMany(User::class, 'user_scopes');
    }

    /**
     * Check if this city has any locations where seating is defined
     * @return bool True if such locations exist
     */
    public function hasRegistrableLocations()
    {
        return Location::where('city_id', $this->id)->whereHas('seatingSections')->count() > 0;
    }

    /**
     * @return string[]
     */
    public function getDefaultMinistriesAttribute()
    {
        return explode('||', $this->attributes['default_ministries']);
    }

    /**
     * @param string[] $ministries
     */
    public function setDefaultMinistriesAttribute(array $ministries)
    {
        $this->attributes['default_ministries'] = join('||', $ministries ?? []);
    }

    public function fillDefaults(): array
    {
        return ['default_ministries' => []];
    }

    /**
     * Updates the children relationship for the current model based on the provided input.
     *
     * @param array $input An associative array containing child IDs in the 'childIds' key.
     * @return void
     */
    public function setChildrenFromInput(array $input)
    {
        $this->children()->update(['parent_id' => null]);
        if (count($input['childIds'] ?? [])) {
            City::whereIn('id', $input['childIds'])->update(['parent_id' => $this->id]);
        }

    }

}
