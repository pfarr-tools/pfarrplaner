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

namespace App\Http\Requests;

use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

/** Authorizes and validates user profile and account-management requests. */
class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $target = $this->user;
        $actor = $this->user();
        if (! $actor instanceof User) {
            return false;
        }

        if (isset($this->user)) {
            return $actor->can('update', $this->user);
        } else {
            return $actor->can('create', User::class);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {

        // Find existing user data.
        // This is needed to prevent the "unique" validator for the "email" field to produce an error
        // when saving an existing user.
        $user = null;
        if ($this->request->has('id')) {
            $user = User::find($this->request->get('id'));
        }

        $rules = [
            'first_name' => 'nullable|string',
            'last_name' => 'required|string',
            'title' => 'nullable|string',
            // add an exception to "unique" when an existing user account is updated
            'email' => 'nullable|string|email|max:255|unique:users,email'.(($user && $user->id) ? ','.$user->id : ''),
            'isOfficialUser' => 'sometimes|boolean',
            'is_official_user' => 'sometimes|boolean',
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

        if ($this->boolean('createAccount')
            || $this->boolean('isOfficialUser')
            || $this->boolean('is_official_user')) {
            $rules['email'] = 'required|email';
        }

        return $rules;
    }

    /**
     * Normalize the official-account classification.
     *
     * @param  string|null  $key  Optional validated value key.
     * @param  mixed  $default  Fallback value kept for FormRequest compatibility.
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();
        $officialStatusProvided = array_key_exists('isOfficialUser', $data)
            || array_key_exists('is_official_user', $data);
        if (array_key_exists('isOfficialUser', $data)) {
            $data['is_official_user'] = (bool) $data['isOfficialUser'];
            unset($data['isOfficialUser']);
        }

        if (! $officialStatusProvided && $this->boolean('createAccount')) {
            $data['is_official_user'] = true;
        }

        return $data;
    }

    /**
     * Return validated relation identifiers for a form field.
     *
     * @param  string  $key  Form field holding the relation identifiers.
     * @param  string|null  $table  Table used by the exists rule; defaults to the field name.
     * @param  string  $tableKey  Key column used by the exists rule.
     * @return array<int, int|string>
     */
    public function getRelationIdsForSync(string $key, ?string $table = null, string $tableKey = 'id'): array
    {
        $table ??= $key;

        /** @var array<string, mixed> $validated Validierte Relation-IDs. */
        $validated = $this->validate([$key => 'nullable|exists:'.$table.','.$tableKey]);
        /** @var array<int, int|string> $ids IDs der zu synchronisierenden Relation. */
        $ids = $validated[$key] ?? [];

        return $ids;
    }

    /**
     * Return role identifiers allowed for the current actor and target.
     *
     * @return array<int, int|string>
     */
    public function getRoles(): array
    {
        $superAdminRole = Role::where('name', RoleService::ROLE_SUPER_ADMIN)->firstOrFail()->id;
        $adminRole = Role::where('name', RoleService::ROLE_ADMIN)->firstOrFail()->id;

        $roles = collect($this->getRelationIdsForSync('roles'));
        if (! count($roles)) {
            return [];
        }

        $actor = $this->user();
        if (! $actor instanceof User) {
            return $roles->all();
        }

        if (! $actor->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            $roles = $roles->reject(function ($item) use ($superAdminRole) {
                return $item == $superAdminRole;
            });
        }
        if (! $actor->hasRole(RoleService::ROLE_SUPER_ADMIN)
            || $actor->hasRole(RoleService::ROLE_ADMIN)) {
            $roles = $roles->reject(function ($item) use ($adminRole) {
                return $item == $adminRole;
            });
        }

        return $roles->all();
    }
}
