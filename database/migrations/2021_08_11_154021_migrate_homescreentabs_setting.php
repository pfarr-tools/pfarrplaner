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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateHomescreentabsSetting extends Migration
{

    protected function migrateHomeScreenTabConfig($config) {
        if (isset($config['migrated'])) return $config;
        $newConfig = [];
        foreach ($config as $tabKey => $tab) {
            $newConfig['tabs'][] = ['type' => $tabKey, 'config' => $tab ];
        }
        $newConfig['migrated'] = true;
        return $newConfig;
    }


    protected function migrateBack($config) {
        if (!isset($config['migrated'])) return $config;
        $newConfig = [];
        foreach ($config as $tabKey => $tab) {
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
        foreach (\App\Models\People\User::all() as $user) {
            $setting = $user->getSetting('homeScreenTabsConfig', null);
            if ($setting) {
                if (!isset($setting['migrated'])) {
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
        foreach (\App\Models\People\User::all() as $user) {
            $setting = $user->getSetting('homeScreenTabsConfig', null);
            if ($setting) {
                if (isset($setting['migrated'])) {
                    echo 'Migrating User #'.$user->id.' back... ';
                    $user->setSetting('homeScreenTabsConfig', $this->migrateBack($setting));
                    echo 'Done.'.PHP_EOL;
                }
            }
        }
    }
}
