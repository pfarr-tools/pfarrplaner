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

namespace App\Http\Requests;

use App\Rules\CreatedInLocalAdminDomainRule;
use App\Services\RoleService;
use App\Models\People\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (isset($this->user)) {
            return $this->user()->can('update', $this->user);
        } else {
            return $this->user()->can('create', User::class);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        // Find existing user data.
        // This is needed to prevent the "unique" validator for the "email" field to produce an error
        // when saving an existing user.
        $user = null;
        if ($this->request->has('id')) {
            $user = User::find($this->request->get('id'));
        }

        $rules = [
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'title' => 'nullable|string',
            // add an exception to "unique" when an existing user account is updated
            'email' => 'nullable|string|email|max:255|unique:users,email' . (($user && $user->id) ? ',' . $user->id : ''),
            'password' => 'nullable|string',
            'office' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|phone_number',
            'preference_cities' => 'nullable|string',
            'manage_absences' => 'nullable|bool',
            'homeCities' => 'nullable',
            'homeCities.*' => 'int|exists:cities,id',
            'own_website' => 'nullable|string',
            'own_podcast_title' => 'nullable|string',
            'own_podcast_url' => 'nullable|string|url',
            'own_podcast_spotify' => 'nullable|bool',
            'own_podcast_itunes' => 'nullable|bool',
            'show_vacations_with_services' => 'nullable|bool',
            'needs_replacement' => 'nullable|bool',
        ];

        // if a password is set, an email is required
        if ($this->get('password', '') != '') {
            $rules['email'] = 'required|email';
        }

        return $rules;
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // api token
        $data['password'] = $data['password'] ?? '';
        if ((($this->user === null) || ($this->user->api_token == '')) && ($data['password'] != '')) {
            $data['api_token'] = Str::random(60);
        }

        return $data;
    }


    public function getRelationIdsForSync($key, $table = null, $tableKey = 'id')
    {
        $table ??= $key;
        return $this->validate([$key => 'nullable|exists:' . $table . ',' . $tableKey])[$key] ?? [];
    }


    public function getRoles()
    {
        $superAdminRole = Role::where('name',RoleService::ROLE_SUPER_ADMIN)->first()->id;
        $adminRole = Role::where('name', RoleService::ROLE_ADMIN)->first()->id;

        $roles = $this->getRelationIdsForSync('roles');
        if (!count($roles)) {
            return [];
        }

        if (!$this->user()->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            if (($key = array_search(RoleService::ROLE_SUPER_ADMIN, $roles)) !== false) {
                unset($roles[$key]);
            }
        }
        if (!$this->user()->hasRole(RoleService::ROLE_SUPER_ADMIN)
            || $this->user()->hasRole(RoleService::ROLE_ADMIN)) {
            if (($key = array_search(RoleService::ROLE_ADMIN, $roles)) !== false) {
                unset($roles[$key]);
            }
        }
        return $roles;
    }

}
