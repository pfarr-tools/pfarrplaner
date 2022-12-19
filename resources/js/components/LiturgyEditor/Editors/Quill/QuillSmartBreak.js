/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

import {Quill} from "vue-quill-editor/src";

const Delta = Quill.import("delta");
const Break = Quill.import("blots/break");
const Embed = Quill.import("blots/embed");

export const lineBreakMatcher = () => {
    let newDelta = new Delta();
    newDelta.insert({ break: "" });
    return newDelta;
};

export class SmartBreak extends Break {
    blotName = "break";
    tagName = "BR";
    length() {
        return 1;
    }
    value() {
        return "\n";
    }

    insertInto(parent, ref) {
        Embed.prototype.insertInto.call(this, parent, ref);
    }

}

