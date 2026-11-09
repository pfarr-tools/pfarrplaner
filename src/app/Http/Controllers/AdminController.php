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

namespace App\Http\Controllers;

use App\Models\Places\City;
use App\Services\RoleService;
use App\UI\Modules\AdminModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Render the administration module overview and city-specific links.
 */
class AdminController extends Controller
{
    /**
     * Create the collection used for city-specific administration modules.
     *
     * @phpstan-return Collection<int, non-empty-array<string, mixed>> Empty module collection.
     */
    private function emptyCityModules(): Collection
    {
        return new Collection;
    }

    /**
     * Protect administration routes with authentication middleware.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return Response
     */
    public function index()
    {
        /** @var Collection<int|string, Collection<int, non-empty-array<string, mixed>>> $modules */
        $modules = collect(AdminModule::modules())->sortBy(['group', 'text'])
            ->reject(fn ($module) => empty($module['group']))
            ->groupBy('group');

        if (Auth::user()->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            $cities = City::all();
        } else {
            $cities = collect(Auth::user()->writableCities)->merge(Auth::user()->adminCities)->unique('id');
        }
        if (count($cities) > 0) {
            $modules['Orte'] = $this->emptyCityModules();
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
