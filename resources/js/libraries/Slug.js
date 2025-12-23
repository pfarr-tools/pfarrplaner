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

/**
 * Slug generator compatible with Laravel Str::slug() for German text.
 *
 * - ä → ae, ö → oe, ü → ue
 * - Ä → ae, Ö → oe, Ü → ue
 * - ß → ss
 * - Lowercase
 * - Spaces and punctuation → "-"
 * - Collapses duplicate "-"
 * - Trims leading/trailing "-"
 */
export function slug(input) {
    if (input == null) return '';

    return String(input)
        // German-specific replacements (must come first)
        .replace(/Ä/g, 'Ae')
        .replace(/Ö/g, 'Oe')
        .replace(/Ü/g, 'Ue')
        .replace(/ä/g, 'ae')
        .replace(/ö/g, 'oe')
        .replace(/ü/g, 'ue')
        .replace(/ß/g, 'ss')

        // Normalize remaining accents (é → e, etc.)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')

        // Lowercase (after transliteration)
        .toLowerCase()

        // Replace non-alphanumeric characters with dashes
        .replace(/[^a-z0-9]+/g, '-')

        // Remove leading/trailing dashes
        .replace(/^-+|-+$/g, '')

        // Collapse multiple dashes
        .replace(/-+/g, '-');
}

export default slug;
