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
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Resources\Json\JsonResource;
use View;

class AbstractHomeScreenTab
{
    /** @var string */
    protected $title = '';

    /** @var string */
    protected $description = '';

    /** @var array<string, mixed> */
    protected $config = [];

    /**
     * Return the authenticated user required to build this tab.
     *
     * @throws AuthenticationException When no user is authenticated.
     */
    protected function user(): User
    {
        return auth()->user() ?? throw new AuthenticationException;
    }

    /**
     * @param  array<string, mixed>  $config  Tab configuration.
     */
    public function __construct($config = [])
    {
        if (! count($config)) {
            $this->setConfig($config);
        }
    }

    /**
     * Check if this tab is available to the current user
     */
    public function isAvailable(?User $user = null): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array|mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return int item count
     */
    public function getCount()
    {
        return 0;
    }

    /**
     * @param  array|mixed  $config
     */
    public function setConfig($config): void
    {
        if (isset($config[$this->getKey()])) {
            $this->config = $config[$this->getKey()];
        } else {
            $this->config = $config;
        }
    }

    /**
     * @param  array<string, mixed>  $config  Tab configuration.
     * @param  array<string, mixed>  $defaults  Default values.
     * @return void
     */
    protected function setDefaultConfig(array $config, array $defaults)
    {
        if (isset($config[$this->getKey()])) {
            $config = $config[$this->getKey()];
        }
        foreach ($defaults as $key => $val) {
            if (! isset($config[$key])) {
                $config[$key] = $val;
            }
        }
        $this->setConfig($config);
    }

    /**
     * Get the class's key
     */
    public function getKey(): string
    {
        return lcfirst(basename(str_replace('\\', '/', get_called_class()), 'HomeScreenTab'));
    }

    /**
     * @param  string  $viewName  View name.
     * @return string
     */
    public function viewName($viewName)
    {
        return 'homescreen.tabs.'.$this->getKey().'.'.$viewName;
    }

    /**
     * Render a specific view
     *
     * @return string|\Illuminate\Contracts\View\View Rendered view
     */
    /**
     * @param  string  $viewName  View name.
     * @param  array<string, mixed>  $data  View data.
     * @return string|\Illuminate\Contracts\View\View
     */
    public function view($viewName, $data = [])
    {
        $viewName = $this->viewName($viewName);
        $data = array_merge($data, ['tab' => $this, 'config' => $this->config]);
        if (View::exists($viewName)) {
            /** @var view-string $viewName */
            return View::make($viewName, $data);
        } else {
            if ($viewName == 'config') {
                return '<i>Für diesen Reiter gibt es nichts einzustellen.</i>';
            } else {
                return '';
            }
        }
    }

    /**
     * Render the tab header
     *
     * @param  array  $data
     * @return string|\Illuminate\Contracts\View\View
     */
    /**
     * @param  array<string, mixed>  $data  View data.
     * @return string|\Illuminate\Contracts\View\View
     */
    public function getHeader($data = [])
    {
        return $this->view('tabHeader', $data);
    }

    /**
     * Render the tab content
     *
     * @return string|\Illuminate\Contracts\View\View
     */
    /**
     * @param  array<string, mixed>  $data  View data.
     * @return string|\Illuminate\Contracts\View\View
     */
    public function getContent($data = [])
    {
        return $this->view('tab', $data);
    }

    /**
     * Get the name of a config key
     *
     * @param  string  $name
     * @return string
     */
    public function configKey($name = '', bool $isArray = false)
    {
        return 'settings[homeScreenTabsConfig]['.$this->getKey().']'.($name ? '['.$name.']' : '').($isArray ? '[]' : '');
    }

    /**
     * @param  array<string, mixed>  $data  Additional tab data.
     * @return array<string, mixed>|JsonResource Tab data.
     */
    public function toArray($data = [])
    {
        $data['title'] = $this->getTitle();
        $data['description'] = $this->getDescription();
        $data['type'] = $this->getKey();
        $data['filled'] = true;
        if (! isset($data['badgeType'])) {
            $data['badgeType'] = 'primary';
        }

        return $data;
    }
}
