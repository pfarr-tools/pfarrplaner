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

namespace App\HomeScreen\Tabs;

use App\Models\People\User;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeScreenTabFactory
{
    /**
     * Get homescreen tabs
     *
     * @return array
     */
    /**
     * @param  array<string, mixed>  $config  Tab configuration.
     * @param  list<string>  $filter  Tab keys to include.
     * @return array<string, AbstractHomeScreenTab>
     */
    public static function get(array $config, array $filter = [], ?User $user = null)
    {
        $homeScreenTabs = [];
        $files = glob(app_path('HomeScreen/Tabs/*HomeScreenTab.php')) ?: [];
        foreach ($files as $file) {
            if (basename($file, 'HomeScreenTab.php') != 'Abstract') {
                $key = lcfirst(basename($file, 'HomeScreenTab.php'));
                if (! isset($config[$key])) {
                    $config[$key] = [];
                }
                $class = 'App\\HomeScreen\\Tabs\\'.basename($file, '.php');
                /** @var class-string<AbstractHomeScreenTab> $class */
                /** @var AbstractHomeScreenTab $object */
                $object = new $class($config);
                if ($object->isAvailable($user)) {
                    $homeScreenTabs[$object->getTitle()] = $object;
                }
            }
        }
        ksort($homeScreenTabs);
        $tabs = [];
        foreach ($homeScreenTabs as $tab) {
            $tabs[$tab->getKey()] = $tab;
        }
        if (count($filter)) {
            $allTabs = $tabs;
            $tabs = [];
            foreach ($filter as $tab) {
                if (isset($allTabs[$tab])) {
                    $tabs[$tab] = $allTabs[$tab];
                }
            }
        }

        return $tabs;
    }

    /**
     * Get a single tab with contents
     *
     * @throws \Exception
     */
    /**
     * @param  array<string, mixed>  $tab  Tab definition.
     * @param  int|string  $tabIndex  Tab index.
     * @return array<string, mixed>|JsonResource|null
     */
    public static function getOne($tab, $tabIndex)
    {
        $class = 'App\\HomeScreen\\Tabs\\'.ucfirst($tab['type'].'HomeScreenTab');
        if (class_exists($class) && is_subclass_of($class, AbstractHomeScreenTab::class)) {
            $tabObject = new $class($tab['config']);
            if (! $tabObject->isAvailable()) {
                return null;
            }
        } else {
            throw new \Exception('Class not found: '.$class);
        }
        /** @var AbstractHomeScreenTab $tabObject */
        $result = $tabObject->toArray();
        if ($result instanceof JsonResource) {
            $resourceData = $result->resource;
            if (! is_array($resourceData)) {
                throw new \UnexpectedValueException('Home screen tab resource must contain an array.');
            }
            $resourceData['config'] = $tab['config'];
            $resourceData['key'] = $tabObject->getKey().$tabIndex;
            $resourceData['index'] = $tabIndex;
            $result->resource = $resourceData;

            return $result;
        }
        $result['config'] = $tab['config'];
        $result['key'] = $result['type'].$tabIndex;
        $result['index'] = $tabIndex;

        return $result;
    }

    /**
     * Get an item count for a single tab
     *
     * @throws \Exception
     */
    /**
     * @param  array<string, mixed>  $tab  Tab definition.
     * @param  int|string  $tabIndex  Tab index.
     * @return int|null
     */
    public static function getCount($tab, $tabIndex)
    {
        $class = 'App\\HomeScreen\\Tabs\\'.ucfirst($tab['type'].'HomeScreenTab');
        if (class_exists($class) && is_subclass_of($class, AbstractHomeScreenTab::class)) {
            /** @var AbstractHomeScreenTab $tabObject */
            $tabObject = new $class($tab['config']);
            if (! $tabObject->isAvailable()) {
                return null;
            }
        } else {
            throw new \Exception('Class not found: '.$class);
        }

        return $tabObject->getCount();
    }

    /**
     * Get all homescreen tabs
     *
     * @return array
     */
    /**
     * @param  array<string, mixed>  $config  Tab configuration.
     * @return array<string, AbstractHomeScreenTab>
     */
    public static function all(array $config)
    {
        return self::get($config, []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function available(?User $user = null): array
    {
        $tabs = self::get([], [], $user);
        $available = [];
        /**
         * @var string $key
         * @var AbstractHomeScreenTab $tab
         */
        foreach ($tabs as $key => $tab) {
            if ($tab->isAvailable()) {
                $available[$key] = [
                    'type' => $key,
                    'title' => $tab->getTitle(),
                    'description' => $tab->getDescription(),
                    'config' => $tab->getConfig(),
                ];
            }
        }

        return $available;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $tabsConfig  Tab definitions.
     * @return array<int|string, string>
     */
    public static function getTitles($tabsConfig)
    {
        $titles = [];
        foreach ($tabsConfig as $tabIndex => $tab) {
            $class = 'App\\HomeScreen\\Tabs\\'.ucfirst($tab['type'].'HomeScreenTab');
            if (class_exists($class) && is_subclass_of($class, AbstractHomeScreenTab::class)) {
                $tabObject = new $class($tab['config']);
                $titles[$tabIndex] = $tabObject->getTitle();
            } else {
                throw new \Exception('Class not found: '.$class);
            }

        }

        return $titles;
    }
}
