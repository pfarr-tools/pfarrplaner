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

namespace App\Actions\Replacement;

use App\Actions\AbstractUpdateAction;
use App\Contracts\Replacement\UpdatesReplacements;
use App\Events\Models\Replacement\UpdatedReplacement;
use App\Models\Leave\Replacement;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateReplacement extends AbstractUpdateAction implements UpdatesReplacements
{
    public function redirectTo(): string
    {
        return '';
    }

    public function update(User $user, Replacement $replacement, array $input): Replacement
    {
        Gate::forUser($user)->authorize('update', $replacement);
        $users = $input['users'] ?? [];
        foreach ($users as $id => $userData) {
            $users[$id] = $userData['id'] ?? $userData;
        }

        $input['absence_id'] = $replacement->absence_id;
        unset($input['users']);
        $input = Validator::make($input, Replacement::$validationRules)->validateWithBag('updateReplacement');
        $replacement->update($input);
        if ($users) {
            $replacement->users()->sync($users);
        }
        UpdatedReplacement::dispatch($user, $replacement);
        $this->messages = ['success' => 'Die Vertretung wurde geändert.'];

        return $replacement;
    }
}
