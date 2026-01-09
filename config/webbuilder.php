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
    'layouts' => [
        [
            'id' => 'neo',
            'name' => 'Layout Neo',
            'templates' => [
                'events' => [
                    'name' => 'Alle Veranstaltungen',
                ],
                'services' => [
                    'name' => 'Gottesdienste',
                ],
                'baptisms' => [
                    'name' => 'Taufgottesdienste',
                    'fields' => [
                        'suppressTitle' => [
                            'label' => 'Maximale Anzahl Taufen pro Gottesdienst',
                            'type' => 'number',
                            'rules' => 'nullable|int|min:1',
                        ],
                    ]
                ],
                'triple-feature' => [
                    'name' => 'Features auf der Startseite',
                ],
                'event-ul' => [
                    'name' => 'Einfache Terminaufzählung',
                    'fields' => [
                        'suppressTitle' => [
                            'label' => 'Veranstaltungstitel nicht anzeigen, wenn er so lautet:',
                            'type' => 'text',
                            'rules' => 'nullable|string',
                        ],
                        'suppressLocation' => [
                            'label' => 'Veranstaltungsort nicht anzeigen, wenn er so lautet:',
                            'type' => 'text',
                            'rules' => 'nullable|string',
                        ],
                        'suppressTime' => [
                            'label' => 'Uhrzeit der Veranstaltung nicht anzeigen, wenn sie so lautet:',
                            'type' => 'text',
                            'rules' => 'nullable|string',
                        ],
                    ],
                ],
            ],
        ],
        [
            'id' => 'jubi',
            'name' => 'Jubiläumslayout',
            'templates' => [
            ],
        ],
    ],
];
