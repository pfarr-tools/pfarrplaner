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

namespace App\Liturgy\Music;

use App\Models\Liturgy\Song;
use App\Models\Liturgy\SongVerse;
use Illuminate\Support\Facades\Storage;

class ABCMusic
{
    public const COLORS_NORMAL = 1;

    public const COLORS_INVERTED = 2;

    public const COLORS_GREENSCREEN = 3;

    /**
     * Get printable verses from a verse range string
     *
     * @param  Song  $song  Song
     * @param  string|array<int, string>|int  $rangeString  Verse range string or pre-split ranges.
     * @return array<int, int|string> Verse numbers in print order.
     */
    public static function getVerses(Song $song, $rangeString)
    {
        if (is_int($rangeString)) {
            $rangeString = (string) $rangeString;
        }
        if (is_string($rangeString) && $rangeString === 'refrain') {
            return [$rangeString];
        }
        if (is_array($rangeString)) {
            return array_values(array_map(
                static fn ($value): int|string => is_numeric($value) ? (int) $value : (string) $value,
                $rangeString
            ));
        }
        $verseRefs = [];
        if (empty($rangeString)) {
            foreach ($song->verses as $verse) {
                $verseRefs[] = (int) $verse['number'];
            }
        } else {
            foreach (explode('+', $rangeString) as $range) {
                $subRange = explode('-', $range);
                if (count($subRange) == 1) {
                    $verseRefs[] = (int) $subRange[0];
                } elseif (is_numeric($subRange[0]) && is_numeric($subRange[1]) && ($subRange[0] < $subRange[1])) {
                    for ($i = $subRange[0]; $i <= $subRange[1]; $i++) {
                        $verseRefs[] = (int) $i;
                    }
                } else {
                    $verseRefs[] = (int) $range;
                }
            }
        }

        return $verseRefs;
    }

    /**
     * Build the complete ABC notation for a song.
     *
     * @param  string|array<int, string>  $verses  Verse selection.
     * @param  int|null  $lineNumber  Single line to render, or null for all lines.
     * @return string ABC notation.
     */
    public static function make(Song $song, $verses = '', $lineNumber = null)
    {
        $music = self::makeHeaders($song, $lineNumber);
        $verses = self::getVerses($song, $verses);

        if ($lineNumber !== null) {
            $music .= self::makeSingleLine($song, $verses, $lineNumber);
        } else {
            for ($lineCtr = 0; $lineCtr < count(explode("\n", $song->notation ?? '')); $lineCtr++) {
                $music .= self::makeSingleLine($song, $verses, $lineCtr);
            }
        }

        return $music;
    }

    /**
     * Build the ABC header block for a song.
     *
     * @param  int|null  $lineNumber  First rendered line number.
     * @return string ABC headers.
     */
    public static function makeHeaders(Song $song, $lineNumber = null)
    {
        $music = '';
        $headers = [
            'X' => 1,
            'L' => $song->note_length,
        ];
        if ($lineNumber == 0) {
            $headers['M'] = $song->measure ?: '4/4';
        }

        foreach ($headers as $key => $header) {
            $music .= $key.':'.$header."\n";
        }
        if ($song->prolog) {
            $music .= trim($song->prolog)."\n";
        }
        $music .= 'K:'.$song->key."\n";

        return $music;
    }

    /**
     * Render one notation line including lyric text.
     *
     * @param  array<int, int|string>|int|string  $verses  Verses to render.
     * @param  int  $lineNumber  Line index.
     * @param  bool  $showLineNumber  Whether verse numbers are printed.
     * @param  bool  $textOnly  Whether notation is omitted.
     * @return string Rendered line.
     */
    public static function makeSingleLine(Song $song, $verses, $lineNumber, $showLineNumber = true, $textOnly = false)
    {
        $musicLines = explode("\n", $song->notation ?? '');
        $music = $textOnly ? '' : ($musicLines[$lineNumber] ?? '')."\n";
        if (! is_array($verses)) {
            $verses = [$verses];
        }
        foreach ($verses as $verseNumber) {
            $verse = self::getVerse($song, $verseNumber);
            if ($verse === null) {
                continue;
            }
            $textLines = explode("\n", $verse->notation ?? '');
            if (isset($textLines[$lineNumber])) {
                $music .= 'w:'
                    .($showLineNumber ? $verse->number.'.~' : '')
                    .$textLines[$lineNumber]."\n";
            }
        }

        return $music;
    }

    /**
     * Build the cache path for a rendered notation image.
     *
     * @param  string|array<int, string>|int  $verses  Verse selection.
     * @param  string  $music  ABC notation used for the cache hash.
     * @param  int  $colors  Color mode constant.
     * @param  int|null  $lineNumber  Rendered line number.
     * @param  bool  $wildCard  Whether the path is a wildcard for all hashes.
     * @return string Absolute cache path.
     */
    protected static function cachedFilePath(Song $song, $verses, $music, $colors, $lineNumber = null, $wildCard = false)
    {
        $parts = [
            $song->songbook_abbreviation,
            $song->reference,
            implode('+', self::getVerses($song, $verses)),
            $colors,
            ($lineNumber !== null) ? $lineNumber : 'X',
            $wildCard ? '*' : md5($music),
        ];

        return storage_path(
            'app/music/abc/'.implode('--', $parts).'.jpg'
        );
    }

    /**
     * Remove cached images for a verse selection.
     *
     * @param  string|array<int, string>|int  $verses  Verse selection.
     * @param  int  $colors  Color mode constant.
     * @param  int|null  $lineNumber  Rendered line number.
     * @return void
     */
    protected static function clearCache(Song $song, $verses, $colors, $lineNumber = null)
    {
        exec('rm '.self::cachedFilePath($song, $verses, '', $colors, $lineNumber, true));
    }

    /**
     * Render ABC notation to a cached image file.
     *
     * @param  string|array<int, string>|int  $verses  Verse selection.
     * @param  string  $music  ABC notation.
     * @param  int  $colors  Color mode constant.
     * @param  int|null  $lineNumber  Rendered line number.
     * @return string Absolute path of the rendered image.
     */
    public static function renderToFile(Song $song, $verses, $music, $colors = self::COLORS_NORMAL, $lineNumber = null)
    {
        Storage::makeDirectory('music/abc/');

        $cachedFileName = self::cachedFilePath($song, $verses, $music, $colors, $lineNumber);
        if (file_exists($cachedFileName)) {
            return $cachedFileName;
        }
        self::clearCache($song, $verses, $colors, $lineNumber);

        $temp = tempnam(sys_get_temp_dir(), 'pfp-abc-');
        $tempABC = $temp.'.abc';
        $tempPS = $temp.'.ps';
        $tempJPG = $temp.'.jpg';

        // build ABC
        file_put_contents($tempABC, $music);

        // build PS
        exec('abcm2ps -O '.$tempPS.' '.$tempABC);
        unlink($tempABC);

        exec('gs -sDEVICE=jpeg -dJPEGQ=100 -dNOPAUSE -dBATCH -dSAFER -r300 -sOutputFile='.$tempJPG.' '.$tempPS);
        unlink($tempPS);

        $params = ['-trim'];

        switch ($colors) {
            case self::COLORS_INVERTED:
                $params[] = '-channel RGB';
                $params[] = '-negate';
                break;
            case self::COLORS_GREENSCREEN:
                $params[] = '-channel RGB';
                $params[] = '-negate';
                $params[] = "-fill 'rgb(4,59,4)'";
                $params[] = "-opaque 'rgb(0,0,0)'";
                break;
        }

        exec('convert '.$tempJPG.' '.implode(' ', $params).' '.$cachedFileName);

        return $cachedFileName;
    }

    /**
     * Find a single verse by its number.
     *
     * @param  int|string  $number  Verse number.
     * @return SongVerse|null Matching verse, if any.
     */
    protected static function getVerse(Song $song, $number)
    {
        foreach ($song->verses as $verse) {
            if ($verse->number == $number) {
                return $verse;
            }
        }

        return null;
    }

    /**
     * Render one image per verse line.
     *
     * @param  string|array<int, string>  $verses  Verse selection.
     * @param  int  $colors  Color mode constant.
     * @param  bool  $mergeVerses  Whether verses share a single image set.
     * @return array<string, string> Image paths keyed by slide identifier.
     */
    public static function images(Song $song, $verses, $colors = self::COLORS_NORMAL, $mergeVerses = true)
    {
        if ($mergeVerses) {
            return self::imagesMerged($song, $verses, $colors);
        }
        $images = [];

        $verses = self::getVerses($song, $verses);
        foreach ($verses as $verseNumber) {
            $verse = self::getVerse($song, $verseNumber);
            if ($verse === null) {
                continue;
            }

            if ($verse->refrain_before) {
                $images = self::makeRefrain($song, $images, $verseNumber, $colors, 'before');
            }

            for ($i = 0; $i < count(explode("\n", $verse->notation ?? '')); $i++) {
                $music = self::makeHeaders($song, $i).self::makeSingleLine($song, $verseNumber, $i, ($i == 0));
                $images[$verseNumber.'-'.$i] = self::renderToFile($song, $verse->number, $music, $colors, $i);
            }

            if ($verse->refrain_after) {
                $images = self::makeRefrain($song, $images, $verseNumber, $colors, 'after');
            }

        }

        return $images;
    }

    /**
     * Render the refrain lines before or after a verse.
     *
     * @param  array<string, string>  $images  Already rendered images.
     * @param  int|string  $verseNumber  Verse the refrain belongs to.
     * @param  int  $colors  Color mode constant.
     * @param  string  $tag  Position marker: before or after.
     * @return array<string, string> Images including the refrain.
     */
    public static function makeRefrain(Song $song, $images, $verseNumber, $colors, $tag)
    {
        $refrainLines = explode("\n", $song->refrain_notation ?? '');
        $refrainText = explode("\n", $song->refrain_text_notation ?? '');
        for ($i = 0; $i < count($refrainLines); $i++) {
            $music = self::makeHeaders($song, $i).$refrainLines[$i]."\n".(($refrainText[$i] ?? '') !== '' ? 'w:'.$refrainText[$i]."\n" : '');
            $images['refrain-'.$tag.'-'.$verseNumber.'-'.$i] = self::renderToFile($song, 'refrain', $music, $colors, $i);
        }

        return $images;
    }

    /**
     * Render merged verse images for a song.
     *
     * @param  string|array<int, string>  $verses  Verse selection.
     * @param  int  $colors  Color mode constant.
     * @return array<string, string> Image paths keyed by slide identifier.
     */
    public static function imagesMerged(Song $song, $verses, $colors = self::COLORS_NORMAL)
    {
        $verseDesignation = $verses;
        $images = [];
        $verseNumbers = self::getVerses($song, $verses);
        $verses = [];

        $refrainBefore = false;
        $refrainAfter = false;
        $verse = null;
        $verseNumber = null;
        foreach ($verseNumbers as $verseNumber) {
            $verse = self::getVerse($song, $verseNumber);
            if ($verse === null) {
                continue;
            }
            $verses[] = $verse;
            $refrainBefore = $refrainBefore || $verse->refrain_before;
            $refrainAfter = $refrainAfter || $verse->refrain_after;
        }
        if ($verse === null) {
            return $images;
        }
        if ($refrainBefore) {
            $images = self::makeRefrain($song, $images, $verseNumbers[0], $colors, 'before');
        }

        $music = '';
        for ($i = 0; $i < count(explode("\n", $verse->notation ?? '')); $i++) {
            $music = self::makeHeaders($song, $i);
            $first = true;
            foreach ($verseNumbers as $verseNumber) {
                $music .= self::makeSingleLine($song, $verseNumber, $i, true, ! $first);
                $first = false;
            }
            $images[$verseNumber.'-'.$i] = self::renderToFile($song, $verseDesignation, $music, $colors, $i);
        }

        if ($refrainAfter) {
            $images = self::makeRefrain($song, $images, $verseNumbers[0], $colors, 'after');
        }

        return $images;
    }
}
