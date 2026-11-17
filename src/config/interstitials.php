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
    | - password-only: nur ohne Passkey, TOTP, ausstehende TOTP-Einrichtung oder erzwungenen Passwortwechsel
    */
    'items' => [
        'security-beyond-password' => [
            'enabled' => true,
            'audience' => 'password-only',
            'repeatable' => true,
            'dismissible' => false,
            'snooze_days' => 21,
            'level' => 'warning',
            'title' => 'Schützen Sie Ihr Konto mit einem zweiten Schutz',
            'text' => 'Wenn jemand Ihr Passwort erfährt, kann ein zusätzlicher Schutz den Zugriff auf Ihr Konto erschweren. Sie können dafür einen Passkey einrichten oder bei jeder Anmeldung einen Code aus einer App eingeben.',
            'details' => [
                'Empfohlen: Passkey. Das ist ein digitaler Schlüssel, den Ihr Smartphone, Computer oder Passwortmanager sicher verwahrt. Zum Anmelden bestätigen Sie mit Fingerabdruck, Gesichtserkennung oder Geräte-PIN. Sie müssen kein zusätzliches Passwort eintippen. Ein Passkey funktioniert nur auf der echten Pfarrplaner-Seite und hilft deshalb auch gegen gefälschte Anmeldeseiten.',
                'Alternative: Authenticator-App mit Einmalcodes. Bei der Einrichtung verbinden Sie die App mit Pfarrplaner, indem Sie einen QR-Code scannen. Die App zeigt danach alle 30 Sekunden einen neuen sechsstelligen Zahlencode. Diesen geben Sie nach Ihrem Passwort ein. Die App kann die Codes auch ohne Internetverbindung erzeugen. Der technische Name dafür ist TOTP (zeitbasiertes Einmalpasswort): ein Code, der nur kurze Zeit gilt und danach automatisch wechselt.',
                'Bei der Einrichtung zeigt Pfarrplaner Wiederherstellungscodes. Bewahren Sie diese getrennt von Ihrem Gerät an einem sicheren Ort auf. Damit können Sie sich anmelden, falls Sie Ihr Gerät verlieren.',
                'Ein Passkey oder eine Authenticator-App genügt. Sie können auch beides einrichten. Ein starkes, nur hier verwendetes Passwort bleibt als Rückfallmöglichkeit wichtig.',
            ],
            'links' => [
                [
                    'label' => 'Microsoft Authenticator für Android und iPhone',
                    'url' => 'https://support.microsoft.com/de-de/authenticator/download-microsoft-authenticator',
                ],
                [
                    'label' => '2FAS Auth: quelloffene App für Android und iPhone',
                    'url' => 'https://2fas.com/auth/',
                ],
            ],
            'action_label' => 'Sicherheitseinstellungen öffnen',
            'action_url' => '/profil?tab=security',
        ],
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
