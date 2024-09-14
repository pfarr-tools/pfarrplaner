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

use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use Illuminate\Http\Request;

class BibleController extends Controller
{
    /**
     * Get a bible text from a reference
     * @param Request $request
     * @param $reference Reference
     * @param string $version Optional: Translation to be used
     * @return \Illuminate\Http\JsonResponse
     */
    public function text(Request $request, $reference, $version = 'LUT17')
    {
        $reference = str_replace('–', '-', $reference);
        $ref = ReferenceParser::getInstance()->parse($reference);
        $text = '';
        $bibleText = (new BibleText($version))->get($ref);

        $showVerseNumbers = $request->get('showVerseNumbers', true);
        $showReference = $request->get('showReference', false);

        foreach ($bibleText as $range) {
            foreach ($range['text'] as $verse) {
                $text .= ($showVerseNumbers ? $verse['verse'].' ' : '').$verse['text'].' ';
            }
        }
        if (($text) && ($showReference)) $text .= ' ('.$reference.')';

        return response()->json(['text' => $text, 'reference' => $ref]);
    }
}
