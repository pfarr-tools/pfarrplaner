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

namespace App\Http\Controllers;

use App\Models\People\User;
use App\Models\Places\City;
use App\Services\RoleService;
use App\UI\Modules\AdminModule;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return \Inertia\Response
     */
    public function index()
    {
        $modules = collect(AdminModule::modules())->sortBy(['group', 'text'])
            ->reject(fn($module) => empty($module['group']))
            ->groupBy('group');

        if (Auth::user()->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            $cities = City::all();
        } else {
            $cities = collect(Auth::user()->writableCities())->merge(Auth::user()->adminCities())->unique('id');
        }
        if (count($cities) > 0) {
            $modules['Orte'] = collect();
            foreach ($cities->sortBy('name') as $city) {
                $modules['Orte']->push([
                                           'text' => $city->name,
                                           'group' => 'Orte',
                                           'icon' => 'mdi mdi-church',
                                           'url' => route('admin.city.edit', $city),
                                           'active' => false,
                                           'inertia' => true,
                                       ]);
            }
        } else {
            unset($modules['Orte']);
        }

        $canCreateCities = Auth::user()->can('create', City::class);

        return Inertia::render('Admin/Index', compact('modules', 'canCreateCities'));
    }
}
