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
 */

/**
 * Maps manual chapter slugs to the Laravel route name prefixes they cover.
 * Used by HandleInertiaRequests to set the helpPage shared prop. The frontend
 * opens the matching chapter on the static manual site.
 */
return [
    'anmeldung' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['login', 'password.', 'logout', 'about'],
    ],
    'kalender' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['calendar', 'cal.'],
    ],
    'oeffentliche-seiten' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => [
            'cc-public',
            'dimissorial.',
            'ministry.request',
            'ministry.plan',
            'service.nextstream',
            'embed.',
            'report.embed',
        ],
    ],
    'veranstaltungen' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['service.', 'home'],
    ],
    'predigt' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['sermon.'],
    ],
    'liturgie' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['liturgy.', 'liturgyBlock.', 'liturgyItem.'],
    ],
    'kasualien' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['rites.', 'baptism', 'wedding', 'funeral'],
    ],
    'urlaubsplan' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['absences.', 'absence.', 'planner.', 'admin.poolmaster', 'admin.poolmasters'],
    ],
    'eingaben' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['inputs.'],
    ],
    'berichte' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['reports.', 'report.'],
    ],
    'einstellungen' => [
        'manual' => 'benutzerhandbuch',
        'prefixes' => ['user.profile', 'password.edit', 'password.change', 'apitoken', 'tokens'],
    ],
    'administration' => [
        'manual' => 'administratorhandbuch',
        'prefixes' => [
            'admin.',
            'user.',
            'users.',
            'role.',
            'roles.',
            'song.',
            'songs.',
            'songbook.',
            'songbooks.',
            'psalm.',
            'psalms.',
            'teams.',
            'team.',
            'city.',
            'cities.',
            'location.',
            'locations.',
            'pool.',
            'pools.',
            'tag.',
            'tags.',
            'template.',
            'seatingSection.',
            'seatingRow.',
        ],
    ],
];
