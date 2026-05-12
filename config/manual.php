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
    'anmeldung'            => ['login', 'password.', 'logout', 'about'],
    'kalender'             => ['calendar', 'cal.'],
    'oeffentliche-seiten'  => [
        'cc-public',
        'dimissorial.',
        'ministry.request',
        'ministry.plan',
        'service.nextstream',
        'embed.',
        'report.embed',
    ],
    'veranstaltungen'      => ['service.', 'home'],
    'predigt'              => ['sermon.'],
    'liturgie'             => ['liturgy.', 'liturgyBlock.', 'liturgyItem.'],
    'kasualien'            => ['rites.', 'baptism', 'wedding', 'funeral'],
    'urlaubsplan'          => ['absences.', 'absence.', 'planner.', 'admin.poolmaster', 'admin.poolmasters'],
    'eingaben'             => ['inputs.'],
    'berichte'             => ['reports.', 'report.'],
    'einstellungen'        => ['user.profile', 'password.edit', 'password.change', 'apitoken', 'tokens'],
    'administration'       => [
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
];
