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
    'images' => [
        'cuts' => [
            'Bildschirm (4x3)' => [1024, 768],
            'Bildschirm (16x9)' => [1920, 1080],
            'CommuniApp' => [550,275],
            'Quadratisch' => [1024, 1024],
            'Story' => [1080, 1920],
        ]
    ],
    'channels' => [
        'bekanntgaben' => [
            'name' => 'Bekanntgaben: extra Text',
        ],
        'communiapp' => [
            'name' => 'CommuniApp: eigene Veranstaltung',
            'depends_on' => 'communiapp_token',
        ],
        'newsletter' => [
            'name' => 'Newsletter: Feature (Bild + Text)',
        ],
        'ppt' => [
            'name' => 'Powerpoint: extra Folie',
        ],
    ],
];
