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
use App\UI\Modules\AdminModule;
use Illuminate\Support\Facades\Auth;

class AdminHomeScreenTab extends AbstractHomeScreenTab
{
    protected $title = 'Administration';

    protected $description = 'Werkzeuge für Administrator:innen';

    protected $config = [];

    /**
     * Check whether the administration tab is available to the user.
     *
     * @param  User|null  $user  User whose administrator access should be checked
     * @return bool Whether the user is an administrator
     */
    public function isAvailable(?User $user = null): bool
    {
        return ($user ?? Auth::user())->isAdmin;
    }

    public function toArray($data = [])
    {
        $data['people'] = User::select('id', 'first_name', 'last_name', 'title', 'email', 'is_official_user')
            ->whereNotNull('email')
            ->where('is_official_user', true)
            ->where('id', '!=', Auth::user()->id)
            ->get();
        $data['modules'] = AdminModule::modules();

        return parent::toArray($data);
    }
}
