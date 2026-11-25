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

namespace App\Services;

use App\Models\People\User;
use App\Models\UserSetting;
use Illuminate\Support\Collection;

/** Cache and persistence for per-user settings. */
class SettingsService
{
    /** @var array<array-key, Collection<array-key, mixed>> Cached settings per user identifier. */
    protected $settings = [];

    /**
     * Load all settings of a user into the cache if they are not cached yet.
     *
     * @param  User  $user  User whose settings are loaded.
     */
    protected function cacheSettings(User $user): void
    {
        // cache all settings, if not done yet
        if (! is_array($this->settings) || (! isset($this->settings[$user->id])) || (count($this->settings[$user->id]) == 0)) {
            $this->settings[$user->id] = UserSetting::where('user_id', $user->id)->get()->mapWithKeys(function ($item) {
                /** @var UserSetting $item */
                /** @var string $key */
                $key = $item['key'];

                return [$key => $item];
            });
        }
    }

    /**
     * Read a single user setting or return a fallback value.
     *
     * @param  User  $user  User whose setting is read.
     * @param  string  $key  Setting key.
     * @param  null  $default  Fallback value when the setting is absent.
     * @param  bool  $returnObject  Whether the setting model is returned instead of its value.
     * @return UserSetting|mixed
     */
    public function get(User $user, string $key, $default = null, $returnObject = false)
    {
        // need to trick error reporting or else this will fail with an E_NOTICE
        $err = error_reporting();
        error_reporting(0);

        $this->cacheSettings($user);

        if (! isset($this->settings[$user->id][$key])) {
            $setting = new UserSetting(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                    'value' => $default,
                ]
            );
        } else {
            $setting = $this->settings[$user->id][$key];
        }
        /** @var UserSetting $setting */
        $return = ($returnObject ? $setting : $setting->value);
        error_reporting($err);

        return $return;

    }

    /**
     * Determine whether a user has a stored setting.
     *
     * @param  User  $user  User whose setting is checked.
     * @param  string  $key  Setting key.
     */
    public function has(User $user, string $key): bool
    {
        $this->cacheSettings($user);

        return isset($this->settings[$user->id][$key]);
    }

    /**
     * Store a setting for a user, serializing array values.
     *
     * @param  User  $user  User whose setting is stored.
     * @param  string  $key  Setting key.
     * @param  mixed  $value  Value to store; arrays are serialized before saving.
     * @return UserSetting The persisted setting model.
     */
    public function set(User $user, string $key, $value): UserSetting
    {
        $this->cacheSettings($user);
        if (is_array($value)) {
            $value = '_____'.serialize($value);
        }
        if ($this->has($user, $key)) {
            $setting = $this->get($user, $key, null, true);
            /** @var UserSetting $setting */
            $setting->value = $value;
            $setting->save();
        } else {
            $setting = UserSetting::create(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                    'value' => $value,
                ]
            );
        }

        return $setting;
    }

    /**
     * Return all settings of a user keyed by setting name.
     *
     * @param  User  $user  User whose settings are returned.
     * @param  bool  $forceReload  Whether the cached settings are discarded first.
     * @return Collection<array-key, mixed>|array{} Setting values keyed by setting name.
     */
    public function all(User $user, bool $forceReload = false): Collection|array
    {
        if ($forceReload) {
            unset($this->settings[$user->id]);
        }
        $this->cacheSettings($user);

        return $this->settings[$user->id] ? $this->settings[$user->id]->map(function ($item) {
            /** @var UserSetting $item */
            return $item->value;
        }) : [];
    }
}
