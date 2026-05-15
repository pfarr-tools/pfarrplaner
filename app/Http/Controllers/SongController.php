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


use App\Liturgy\Music\ABCMusic;
use App\Models\Liturgy\Psalm;
use App\Models\Liturgy\Song;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SongController extends AbstractCRUDController
{
    protected string $modelClass = Song::class;

    /**
     * SongController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return Collection
     */
    protected function getModelsForIndex(): Collection
    {
        return Song::without(['verses'])->select(['id', 'title', 'alt_eg'])->get();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function songbooks()
    {
        $songbooks = [];
        foreach (Song::all() as $song) {
            $songbooks[$song->songbook_abbreviation] = [
                'title' => $song->songbook,
                'abbreviation' => $song->songbook_abbreviation
            ];
        }
        foreach (Psalm::all() as $song) {
            $songbooks[$song->songbook_abbreviation] = [
                'title' => $song->songbook,
                'abbreviation' => $song->songbook_abbreviation
            ];
        }
        return response()->json($songbooks);
    }

    /**
     * Create a separate song from a songbook reference on an existing one
     *
     * @param Song $song
     * @param $reference
     * @return RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function split(Song $song, $reference)
    {
        $this->authorize('update', $song);


        $song->load('songbooks');

        $origSync = $splitSync = [];
        foreach ($song->songbooks as $songbook) {
            if ($songbook->pivot->songbook_id == $reference) {
                $splitSync[$songbook->pivot->songbook_id] = [
                    'reference' => $songbook->pivot->reference,
                    'code' => $songbook->pivot->code
                ];
            } else {
                $origSync[$songbook->pivot->songbook_id] = [
                    'reference' => $songbook->pivot->reference,
                    'code' => $songbook->pivot->code
                ];
            }
        }

        $song->songbooks()->sync([]);
        $song->songbooks()->sync($origSync);

        $newSong = $song->replicate();
        $newSong->save();
        $newSong->refresh();
        $newSong->songbooks()->sync([]);
        $newSong->songbooks()->sync($splitSync);
        $newSong->push();

        /** @var SongVerse $verse */
        foreach ($song->verses as $verse) {
            $newVerse = SongVerse::create([
                                              'song_id' => $newSong->id,
                                              'number' => $verse->number,
                                              'text' => $verse->text,
                                              'refrain_before' => $verse->refrain_before,
                                              'refrain_after' => $verse->refrain_after,
                                              'notation' => $verse->notation,
                                          ]);
        }
        $newSong->refresh();

        return redirect()->route('admin.song.edit', $newSong->id);
    }

    public function musicEditor(Song $song)
    {
        return Inertia::render('Liturgy/Songs/MusicEditor', compact('song'));
    }

    public function music(Song $song, $verses = '', $lineNumber = null)
    {
        return response()->file(ABCMusic::renderToFile($song, $verses, ABCMusic::make($song, $verses, $lineNumber)));
    }

}
