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

return [
    'versions' => [
        'LUT17' => resource_path('bible/lut17.txt'), // BITTE BEACHTEN: Diese Datei wird nicht mit dem Pfarrplaner mitgeliefert,
    ],
    'parser' => [
        'books' => [
            'Gen' => ['Genesis', '1.Mose', '1Mose'],
            'Exo' => ['Exodus', '2.Mose', '2Mose'],
            'Lev' => ['Leviticus', '3.Mose', '3Mose'],
            'Num' => ['Numeri', '4.Mose', '4Mose'],
            'Deu' => ['Deuteronomium', '5.Mose', '5Mose'],
            'Jos' => ['Josua'],
            'Jdg' => ['Richter'],
            'Rut' => ['Ruth'],
            '1Sa' => ['1.Samuel', '1Samuel'],
            '2Sa' => ['2.Samuel', '2Samuel'],
            '1Ki' => ['1.Könige', '1Könige'],
            '2Ki' => ['2.Könige', '2Könige'],
            '1Ch' => ['1.Chronik', '1Chronik'],
            '2Ch' => ['2.Chronik', '2Chronik'],
            'Ezr' => ['Ezra'],
            'Neh' => ['Nehemia'],
            'Est' => ['Esther', 'Ester'],
            'Job' => ['Hiob', 'Ijob', 'Job'],
            'Psa' => ['Psalm', 'Psalmen', 'Psalter'],
            'Pro' => ['Sprüche'],
            'Ecc' => ['Ecclesiates', 'Prediger', 'Kohelet', 'Qohelet'],
            'Sol' => ['Hohelied', 'Hoheslied'],
            'Isa' => ['Jesaja'],
            'Jer' => ['Jeremia'],
            'Lam' => ['Klagelieder'],
            'Eze' => ['Hesekiel', 'Ezechiel'],
            'Dan' => ['Daniel'],
            'Hos' => ['Hosea'],
            'Joe' => ['Joel'],
            'Amo' => ['Amos'],
            'Oba' => ['Obadja', 'Obadia'],
            'Jon' => ['Jona'],
            'Mic' => ['Micha'],
            'Nah' => ['Nahum'],
            'Hab' => ['Habakkuk'],
            'Zep' => ['Zefania', 'Zefanja'],
            'Hag' => ['Haggai'],
            'Zec' => ['Sacharia', 'Sacharja'],
            'Mal' => ['Maleachi'],
            'Mat' => ['Matthäus', 'Mt'],
            'Mar' => ['Markus', 'Mk'],
            'Luk' => ['Lukas', 'Lk'],
            'Joh' => ['Johannes', 'Jn', 'Jh'],
            'Act' => ['Apostelgeschichte', 'Apg'],
            'Rom' => ['Römer', 'Romer', 'Roemer'],
            '1Co' => ['1.Korinther', '1.Kor', '1Kor', '1Korinther'],
            '2Co' => ['2.Korinther', '2.Kor', '2Kor', '2Korinther'],
            'Gal' => ['Galater'],
            'Eph' => ['Epheser'],
            'Phi' => ['Philipper'],
            'Col' => ['Kolosser'],
            '1Th' => ['1.Thessalonicher', '1Thessalonicher'],
            '2Th' => ['2.Thessalonicher', '2Thessalonicher'],
            '1Ti' => ['1.Timotheus', '1.Tim', '1.Ti', '1Timotheus'],
            '2Ti' => ['2.Timotheus', '2.Tim', '2.Ti', '2Timotheus'],
            'Tit' => ['Titus'],
            'Phm' => ['Philemon'],
            'Heb' => ['Hebräer'],
            'Jam' => ['Jakobus'],
            '1Pe' => ['1.Petrus', '1Petrus'],
            '2Pe' => ['2.Petrus', '2Petrus'],
            '1Jo' => ['1.Johannes', '1Johannes'],
            '2Jo' => ['2.Johannes', '2Johannes'],
            '3Jo' => ['3.Johannes', '3Johannes'],
            'Jud' => ['Judas'],
            'Rev' => ['Offenbarung', 'Offb'],
            'Jdt' => ['Judith', 'Judit'],
            'Weish' => ['Weisheit'],
            'Tob' => ['Tobit'],
            'Sir' => ['Sirach', 'Jesus Sirach', 'Ben Sirach'],
            'Bar' => ['Baruch'],
            '1Ma' => ['1.Makkabäer', '1Makkabäer'],
            '2Ma' => ['2.Makkabäer', '2Makkabäer'],
            'StZuDan' => ['Stücke zu Daniel', '2.Daniel', '2Daniel'],
            'StZuEst' => ['Stücke zu Ester', 'Stücke zu Esther', '2.Esther', '2.Ester', '2Esther', '2Ester'],
            'GebMan' => ['Gebet des Manasse'],
        ]
    ]
];
