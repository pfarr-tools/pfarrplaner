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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 24.04.2019
 * Time: 14:32
 */

namespace App\HomeScreens;

use App\Models\Leave\Absence;
use App\Models\Leave\Replacement;
use App\Models\People\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class AbstractHomeScreen
 */
abstract class AbstractHomeScreen
{
    /** @var bool true, if this view can be configured in user profile settings */
    protected $hasConfiguration = false;

    public function __construct() {}

    /**
     * Render the homescreen
     *
     * @return mixed
     */
    abstract public function render();

    /**
     * Return the authenticated user required to render a home screen.
     *
     * @return User Authenticated user.
     *
     * @throws AuthenticationException When no user is authenticated.
     */
    protected function user(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }

    /**
     * Called to render a specific blade view
     *
     * @param  string  $viewName  Name of the view to render
     * @param  array<string, mixed>  $data  Data passed to the view (absence data will be added)
     * @return Factory|View
     */
    public function renderView(string $viewName, array $data)
    {
        if ($this->user()->manage_absences) {
            $data = array_merge($data, $this->getAbsences());
        }

        /** @var view-string $viewName */
        return view($viewName, $data);
    }

    /**
     * Get absence data for view
     *
     * @return array<string, mixed> Absence data
     */
    protected function getAbsences()
    {
        $userId = $this->user()->id;
        $absences = Absence::where('user_id', $userId)->where('to', '>=', now())->get();
        $replacements = Replacement::with('absence')
            ->whereHas(
                'users',
                function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                }
            )
            ->where('to', '>=', now())
            ->orderBy('from')
            ->orderBy('to')
            ->get();

        return compact('absences', 'replacements');
    }

    /**
     * Get a view for configuration setttings
     *
     * @return string
     */
    public function renderConfigurationView()
    {
        return 'Für diesen Startbildschirm gibt es nichts zu konfigurieren.';
    }

    /**
     * Save submitted configuration
     *
     * @param  Request  $request  Request
     */
    public function setConfiguration(Request $request): void {}
}
