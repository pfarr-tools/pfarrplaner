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

namespace App\Models\Liturgy;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Referenz eines Liedes in einem Liederbuch.
 *
 * @property-read Song $song Das referenzierte Lied.
 * @property-read Songbook $songbook Das Liederbuch der Referenz.
 */
class SongReference extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $fillable = ['song_id', 'songbook_id'];

    protected $table = 'song_songbook';

    protected $with = ['song', 'songbook'];

    protected $appends = ['altEG'];

    /**
     * Gibt das referenzierte Lied zurück.
     *
     * @return BelongsTo<Song, $this>
     */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class, 'song_id', 'id', 'songs');
    }

    /**
     * Gibt das Liederbuch der Referenz zurück.
     *
     * @return BelongsTo<Songbook, $this>
     */
    public function songbook(): BelongsTo
    {
        return $this->belongsTo(Songbook::class, 'songbook_id', 'id', 'songbooks');
    }

    /**
     * Gibt die alternative EG-Nummer des Liedes zurück.
     *
     * @return string
     */
    public function getAltEGAttribute(): string
    {
        if ($this->songbook->code == 'EG') {
            return '';
        }
        if ($this->song->alt_eg) {
            return (string) $this->song->alt_eg;
        }
        foreach ($this->song->songbooks as $songbook) {
            if ($songbook->code == 'EG') {
                return $songbook->pivot->reference;
            }
        }

        return '';
    }
}
