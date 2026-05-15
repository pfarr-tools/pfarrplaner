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

namespace App\Models\Liturgy;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends AbstractModel
{
    use HasFactory;

    protected static string $prefix = 'lied';
    protected static string $prefixPlural = 'lieder';
    public static array $exceptRoutes = [
        'web' => ['show'],
        'api' => ['index', 'show', 'store', 'update', 'destroy'],
    ];
    public static array $validationRules = [
        'title' => 'required|string',
        'refrain' => 'nullable|string',
        'copyrights' => 'nullable|string',
        'key' => 'nullable|string',
        'measure' => 'nullable|string',
        'note_length' => 'nullable|string',
        'notation' => 'nullable|string',
        'refrain_notation' => 'nullable|string',
        'refrain_text_notation' => 'nullable|string',
        'verses.*.number' => 'nullable',
        'verses.*.text' => 'nullable|string',
        'verses.*.refrain_before' => 'nullable|bool',
        'verses.*.refrain_after' => 'nullable|bool',
        'verses.*.notation' => 'nullable|string',
        'songbooks.*.code' => 'nullable|string',
        'songbooks.*.pivot.songbook_id' => 'nullable|int|exists:songbooks,id',
        'songbooks.*.pivot.reference' => 'nullable|string',
        'songbooks.*.pivot.color' => 'nullable|string',
        'alt_eg' => 'nullable|string',
    ];
    public static $relationsForEditor = ['verses', 'songbooks'];
    public static $adminTitle = 'Lieder';
    public static $adminIcon = 'mdi mdi-music';
    public static $adminGroup = 'Liturgie';

    protected $fillable = [
        'title',
        'refrain',
        'copyrights',
        'songbook',
        'songbook_abbreviation',
        'reference',
        'key',
        'measure',
        'note_length',
        'prolog',
        'notation',
        'refrain_notation',
        'refrain_text_notation',
        'alt_eg',
    ];
    protected $with = ['verses', 'songbooks'];

    /**
     * @param string $page
     * @return string
     */
    public static function getVuePath(string $page)
    {
        return match ($page) {
            'editor' => 'Admin/Song/SongEditor',
            default => parent::getVuePath($page),
        };
    }

    /**
     * @return AbstractModel
     */
    public static function getEmptyModel(): AbstractModel
    {
        /** @var Song $model */
        $model = parent::getEmptyModel();
        $model->verses = new Collection();
        $model->songbooks = new Collection();
        return $model;
    }

    /**
     * @return array
     */
    public function fillDefaults(): array
    {
        return [
            'title' => '',
            'refrain' => '',
            'copyrights' => '',
            'key' => '',
            'measure' => '',
            'note_length' => '',
            'prolog' => '',
            'notation' => '',
            'refrain_notation' => '',
            'refrain_text_notation' => '',
            'alt_eg' => '',
        ];
    }

    /**
     * @return string
     */
    public function getLabelAttribute(): string
    {
        return $this->title ?: '';
    }

    /**
     * @return HasMany
     */
    public function verses(): HasMany
    {
        return $this->hasMany(SongVerse::class);
    }

    /**
     * @return BelongsToMany
     */
    public function songbooks(): BelongsToMany
    {
        return $this->belongsToMany(Songbook::class)->withPivot(['id', 'reference', 'code', 'color']);
    }

    /**
     * @param array $data
     * @return void
     */
    public function syncVersesFromRequest(array $data): void
    {
        $this->verses()->delete();
        foreach ($data['verses'] ?? [] as $verse) {
            $verse['song_id'] = $this->id;
            SongVerse::create($verse);
        }
    }

    /**
     * @param array $data
     * @return void
     */
    public function syncSongbooksFromRequest($data)
    {
        if (!isset($data['songbooks'])) return;
        $sync = [];
        foreach ($data['songbooks'] as $item) {
            $sync[$item['pivot']['songbook_id']] = ['reference' => $item['pivot']['reference'], 'code' => $item['code'], 'color' => ($item['pivot']['color'] ?? '')];
        }
        $this->songbooks()->sync($sync, true);
    }

}
