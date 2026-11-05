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

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Class RoleController
 */
class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Role::class, 'role');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name', 'ASC')->get();

        return Inertia::render('Admin/Role/Index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $permissions = Permission::all()->sortBy('name');
        $role = new Role;

        return Inertia::render('Admin/Role/RoleEditor', compact('role', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $data = $request->validate(
            [
                'name' => 'required',
            ]
        );
        $role = Role::create(['name' => $data['name']]);
        $permissions = $request->get('permissions') ?: [];
        foreach ($permissions as $permissionName) {
            $permission = Permission::findOrCreate($permissionName);
        }
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')->with('success', 'Die Benutzerrolle wurde angelegt.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Inertia\Response
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::all()->sortBy('name');

        return Inertia::render('Admin/Role/RoleEditor', compact('role', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate(
            [
                'name' => 'required',
            ]
        );
        $role->update($data);
        $permissions = $request->get('permissions') ?: [];
        foreach ($permissions as $permissionName) {
            $permission = Permission::findOrCreate($permissionName);
        }
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')->with('success', 'Die Benutzerrolle wurde geändert.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Die Benutzerrolle wurde gelöscht.');
    }
}
