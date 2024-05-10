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

namespace App\Reports;

use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeeklyVerseReport extends AbstractCSVReport
{

    /**
     * @var string
     */
    public $title = 'Liste aller Wochensprüche';
    /**
     * @var string
     */
    public $description = 'Gibt alle Wochensprüche für einen bestimmten Zeitraum aus.';

    protected $inertia = true;


    /**
     * @return \Inertia\Response
     */
    public function setup()
    {
        return Inertia::render('Report/WeeklyVerse/Setup');
    }

    public function render(Request $request)
    {
        $data = $request->validate(
            [
                'start' => 'required|date',
                'end' => 'required|date',
            ]
        );

        $start = Carbon::parse( $data['start']);
        $end = Carbon::parse( $data['end']);

        $records = [];
        $currentDate = $start->copy();
        do {
            $dayData =  LiturgyService::getDayInfo($currentDate);
            if (count($dayData)) {
                $records[$currentDate->format('Y-m-d')] = $dayData;
            }
            $currentDate->addDay(1);
        } while($currentDate <= $end);

        $bible = new BibleText();

        return $this->csv(
            $start->format('Ymd').'-'.$end->format('Ymd').' Wochensprueche.csv',
            $records,
            [
            'Datum' => function($item, $key) { return $key; },
            'Titel des Tages' => function($item, $key) {
                return strtr($item['title'], [
                    'So. n.' => 'Sonntag nach',
                    'So n.' => 'Sonntag nach',
                    'Drittl.S.d.Kj.' => 'Drittletzter Sonntag des Kirchenjahres',
                    'd. Kj.' => 'des Kirchenjahres',
                    'So. ' => 'Sonntag ',
                ]);
            },
            'Überschrift' => function($item, $key) {
                return (Carbon::parse($item['dateSql'])->weekday()) ? 'Für diesen besonderen Tag' : 'Für die neue Woche';
            },
            'Wochenspruch' => 'litTextsWeeklyQuoteText',
            'Bibelstelle' => function($item, $key) use ($bible) {
                return (ReferenceParser::getInstance()->beautify($item['litTextsWeeklyQuote']));
            },
        ]);

    }

}
