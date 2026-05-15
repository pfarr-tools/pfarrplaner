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

use App\Actions\AbstractDeleteAction;
use App\Contracts\Replacement\DeletesReplacements;
use App\Events\Models\Replacement\DeletedReplacement;
use App\Models\Leave\Replacement;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;

class DeleteReplacement extends AbstractDeleteAction implements DeletesReplacements
{
    public function redirectTo(): string
    {
        return '';
    }

    public function delete(User $user, Replacement $replacement): ?bool
    {
        Gate::forUser($user)->authorize('delete', $replacement);
        $replacement->users()->sync([]);
        $result = $replacement->delete();
        DeletedReplacement::dispatch($user, $replacement);
        $this->messages = ['success' => 'Die Vertretung wurde gelöscht.'];

        return $result;
    }
}
