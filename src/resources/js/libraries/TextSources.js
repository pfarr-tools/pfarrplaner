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

import {romanize} from "@pfarr.tools/romanize";

export function getTextSources(service) {
    let textSources = {};
    if (undefined !== service.liturgicalInfo.Bezeichnung) {
        ['Predigt', 'Wochenspruch', 'Psalm'].forEach(key => {
            if (undefined !== service.liturgicalInfo.key) textSources[key] = service.liturgicalInfo[key].Bibelstelle;
        });
        for (const key in service.liturgicalInfo.Perikopen) {
            textSources[isNaN(key) ? key : romanize(key)] = service.liturgicalInfo.Perikopen[key].Bibelstelle;
        }
    }
    service.baptisms.forEach(baptism => {
        if (baptism.text) textSources['Taufspruch '+baptism.candidate_name] = baptism.text;
    });
    service.funerals.forEach(funeral => {
        if (funeral.text) textSources['Beerdigungstext '+funeral.buried_name] = funeral.text;
        if (funeral.confirmation_text) textSources['Denkspruch '+funeral.buried_name] = funeral.confirmation_text;
        if (funeral.wedding_text) textSources['Trauspruch '+funeral.buried_name] = funeral.wedding_text;
    });
    service.weddings.forEach(wedding => {
        if (wedding.text) textSources['Trauspruch '+wedding.spouse1_name+' & '+wedding.spouse2_name] = wedding.text;
    });
    return textSources;
}
