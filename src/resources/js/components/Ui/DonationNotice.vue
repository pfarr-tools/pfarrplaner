<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarr.tools/pfarrplaner
  - @version git: $Id$
  -
  - Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
  -
  - Pfarrplaner is based on the Laravel framework (https://laravel.com).
  - This file may contain code created by Laravel's scaffolding functions.
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="donation-notice text-muted small">
        <p class="mb-2">
            Pfarrplaner ist ein freies Projekt für Kirchengemeinden.<br>
            Entwicklung kostet Zeit und Geld.<br>
            Wenn dir Pfarrplaner hilft, kannst du das Projekt freiwillig unterstützen.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="https://paypal.me/potofcoffee" target="_blank" rel="noopener noreferrer">Einmal per PayPal</a>
            <a href="https://liberapay.com/christoph.fischer" target="_blank" rel="noopener noreferrer">Regelmäßig per Liberapay</a>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DonationNotice',
}
</script>

<style scoped>
.donation-notice a {
    text-decoration: none;
}

.donation-notice a:hover {
    text-decoration: underline;
}
</style>
