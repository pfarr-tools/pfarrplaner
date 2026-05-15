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

namespace App\Actions\Song;

use App\Actions\AbstractCreateAction;
use App\Contracts\Song\CreatesSongs;
use App\Events\Models\Song\CreatedSong;
use App\Models\Liturgy\Song;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateSong extends AbstractCreateAction implements CreatesSongs
{
    /**
     * @return string
     */
    public function redirectTo(): string
    {
        return route('admin.songs.index');
    }

    /**
     * @param User $user
     * @param array $input
     * @return Song
     */
    public function create(User $user, array $input): Song
    {
        Gate::forUser($user)->authorize('create', Song::class);

        $validated = Validator::make($input, Song::$validationRules)->validateWithBag('createSong');
        $song = Song::create(collect($validated)->except(['verses', 'songbooks'])->all());
        $song->syncVersesFromRequest($validated);
        $song->syncSongbooksFromRequest($validated);
        $song->load(Song::$relationsForEditor);

        CreatedSong::dispatch($user, $song);
        $this->messages = ['success' => 'Das neue Lied wurde gespeichert.'];

        return $song;
    }
}
