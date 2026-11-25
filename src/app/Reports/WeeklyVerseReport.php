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

namespace App\Reports;

use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Services\FileNameService;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyVerseReport extends AbstractCSVReport
{
    public const FILE_TITLE = 'Wochensprueche';

    public const FILE_SIGNATURE = '50.1';

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
     * @return Response
     */
    public function setup()
    {
        return Inertia::render('Report/WeeklyVerse/Setup');
    }

    public function render(Request $request)
    {
        /** @var array{start: string, end: string} $data */
        $data = $request->validate(
            [
                'start' => 'required|date',
                'end' => 'required|date',
            ]
        );

        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);

        $records = [];
        $currentDate = $start->copy();
        do {
            /** @var array<int, array<string, mixed>> $liturgyInfo */
            $liturgyInfo = LiturgyService::getLiturgyInfoByDate($currentDate);
            $dayData = $liturgyInfo[0] ?? [];
            if (count($dayData)) {
                $records[$currentDate->format('Y-m-d')] = $dayData;
            }
            $currentDate->addDay();
        } while ($currentDate <= $end);

        $bible = new BibleText;

        /** @var string $title */
        $title = static::FILE_TITLE;

        return $this->csv(
            FileNameService::make(
                $title,
                'csv',
                static::FILE_SIGNATURE,
                [$start, $end],
            ),
            $records,
            [
                'Datum' => function ($item, $key) {
                    return $key;
                },
                'Titel des Tages' => function ($item, $key) {
                    /** @var array{Bezeichnung: string} $entry */
                    $entry = $item;

                    return strtr($entry['Bezeichnung'], [
                        'So. n.' => 'Sonntag nach',
                        'So n.' => 'Sonntag nach',
                        'Drittl.S.d.Kj.' => 'Drittletzter Sonntag des Kirchenjahres',
                        'd. Kj.' => 'des Kirchenjahres',
                        'So. ' => 'Sonntag ',
                    ]);
                },
                'Überschrift' => function ($item, $key) {
                    /** @var string $dateKey */
                    $dateKey = $key;

                    return (Carbon::parse($dateKey)->weekday()) ? 'Für diesen besonderen Tag' : 'Für die neue Woche';
                },
                'Wochenspruch' => function ($item, $key) {
                    /** @var array{Wochenspruch: array{Text: string}} $entry */
                    $entry = $item;

                    return $entry['Wochenspruch']['Text'];
                },
                'Bibelstelle' => function ($item, $key) {
                    /** @var array{Wochenspruch: array{Bibelstelle: string}} $entry */
                    $entry = $item;

                    return ReferenceParser::getInstance()->beautify($entry['Wochenspruch']['Bibelstelle']);
                },
            ]);

    }
}
