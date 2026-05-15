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

namespace App\Actions\Team;

use App\Actions\AbstractCreateAction;
use App\Contracts\Team\CreatesTeams;
use App\Events\Models\Team\CreatedTeam;
use App\Models\People\Team;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateTeam extends AbstractCreateAction implements CreatesTeams
{
    use NormalizesTeamData;

    public function redirectTo(): string
    {
        return route('admin.teams.index');
    }

    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Team::class);

        $input = $this->normalizeInput($input);
        $validated = Validator::make($input, Team::$validationRules)->validateWithBag('createTeam');

        $team = Team::create(collect($validated)->except('users')->all());
        $team->users()->sync($validated['users'] ?? []);
        $team->load(Team::$relationsForEditor);

        CreatedTeam::dispatch($user, $team);
        $this->messages = ['success' => 'Das neue Team wurde gespeichert.'];

        return $team;
    }
}
