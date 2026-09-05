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
    'setting_key' => 'interstitials',

    /*
    |--------------------------------------------------------------------------
    | Interstitials
    |--------------------------------------------------------------------------
    |
    | Jeder Eintrag wird über seinen Array-Key identifiziert. Wenn Sie eine
    | Meldung später erneut anzeigen möchten, ändern Sie einfach den Key.
    |
    | audience:
    | - all: für alle Benutzer:innen
    | - admin: nur für globale oder lokale Administrator:innen
    | - writer: nur für Benutzer:innen mit Schreibrechten in mind. einer Gemeinde
    */
    'items' => [
        'opferzaehler-umstellung-2026-06-30' => [
            'enabled' => true,
            'audience' => 'writer',
            'level' => 'warning',
            'title' => 'Wichtige Änderung bei den Opferzähler-Feldern',
            'text' => 'Zum 30. Juni 2026 werden die Felder "Opferzähler 1" und "Opferzähler 2" entfernt. Stattdessen werden die Angaben in einen normalen Dienst eingetragen, ähnlich wie bei "Schriftlesung" oder anderen Diensten.',
            'details' => [
                'Bitte prüfe vor der Umstellung alle Einträge in den Opferzähler-Feldern.',
                'Trage dort vollständige Namen ein, am besten im Format "Nachname, Vorname".',
                'Das Format "Vorname Nachname" kann ebenfalls funktionieren, ist aber weniger sicher.',
                'Falsch oder uneinheitlich geschriebene Einträge können bei der Umstellung verloren gehen.',
            ],
        ],
    ],
];
