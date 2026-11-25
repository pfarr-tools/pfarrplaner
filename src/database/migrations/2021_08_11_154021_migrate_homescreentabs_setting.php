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

use App\Models\People\User;
use Illuminate\Database\Migrations\Migration;

class MigrateHomescreentabsSetting extends Migration
{
    /**
     * Convert the legacy home screen tab configuration to the nested format.
     *
     * @param  array<string, mixed>  $config  Legacy tab configuration keyed by tab type.
     * @return array<string, mixed> Migrated configuration containing the tabs and a migrated flag.
     */
    protected function migrateHomeScreenTabConfig(array $config): array
    {
        if (isset($config['migrated'])) {
            return $config;
        }
        $newConfig = [];
        foreach ($config as $tabKey => $tab) {
            $newConfig['tabs'][] = ['type' => $tabKey, 'config' => $tab];
        }
        $newConfig['migrated'] = true;

        return $newConfig;
    }

    /**
     * Convert the migrated home screen tab configuration back to the legacy format.
     *
     * @param  array<string, mixed>  $config  Migrated configuration containing tabs and a migrated flag.
     * @return array<string, mixed> Legacy tab configuration keyed by tab type.
     */
    protected function migrateBack(array $config): array
    {
        if (! isset($config['migrated'])) {
            return $config;
        }
        $newConfig = [];
        foreach ($config as $tabKey => $tab) {
            if (! is_array($tab) || ! isset($tab['type']) || ! is_string($tab['type'])) {
                continue;
            }
            $newConfig[$tab['type']] = $tab['config'];
        }

        return $newConfig;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (User::all() as $user) {
            /** @var array<string, mixed>|null $setting */
            $setting = $user->getSetting('homeScreenTabsConfig', null);
            if ($setting && is_array($setting)) {
                if (! isset($setting['migrated'])) {
                    echo 'Migrating User #'.$user->id.'... ';
                    $user->setSetting('homeScreenTabsConfig', $this->migrateHomeScreenTabConfig($setting));
                    echo 'Done.'.PHP_EOL;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (User::all() as $user) {
            /** @var array<string, mixed>|null $setting */
            $setting = $user->getSetting('homeScreenTabsConfig', null);
            if ($setting && is_array($setting)) {
                if (isset($setting['migrated'])) {
                    echo 'Migrating User #'.$user->id.' back... ';
                    $user->setSetting('homeScreenTabsConfig', $this->migrateBack($setting));
                    echo 'Done.'.PHP_EOL;
                }
            }
        }
    }
}
