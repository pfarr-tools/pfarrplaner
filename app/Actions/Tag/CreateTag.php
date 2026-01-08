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

namespace App\Actions\Tag;

use App\Actions\AbstractCreateAction;
use App\Contracts\Tag\CreatesTags;
use App\Events\Models\Tag\CreatedLocation;
use App\Events\Models\Tag\CreatedTag;
use App\Models\People\User;
use App\Models\Tag;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateTag extends AbstractCreateAction implements CreatesTags
{

    /**
     * Get the return route
     * @return string
     */
    public function redirectTo(): string
    {
        return route('admin.tags.index');
    }

    /**
     * Create a new tag
     * @param User $user
     * @param array $input
     * @return Tag
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(User $user, array $input)
    {
        Gate::forUser($user)->authorize('create', Tag::class);
        $input = Validator::make($input, Tag::$validationRules)->validateWithBag('createTag');
        if (empty($input['code'] ?? '')) $input['code'] = Str::slug($input['name']);
        $tag = Tag::create($input);
        CreatedTag::dispatch($user, $tag);
        $this->messages = ['success' => 'Die neue Kennzeichnung wurde gespeichert.'];
        return $tag;

    }
}
