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
    <div v-if="showMaterials" class="dropdown">
        <button type="button" id="liturgyMaterialsMenuButton" data-bs-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false" title="Links zu Predigthilfen"
                class="btn btn-outline-secondary dropdown-toggle">
            <span class="mdi mdi-text-box-search-outline me-lg-1"></span>
            <span class="d-none d-lg-inline">Materialsammlung</span>
        </button>
        <div aria-labelledby="liturgyMaterialsMenuButton" class="dropdown-menu dropdown-menu-end">
            <a v-if="service.liturgicalInfo.DKJ" target="_blank"
               :href="service.liturgicalInfo.DKJ.URL" class="dropdown-item">
                <div class="fw-bold">Das Kirchenjahr</div>
                <div>{{ service.liturgicalInfo.Bezeichnung }}</div>
            </a>
            <a v-for="(link,linkTitle) in service.liturgicalInfo.Links" :key="linkTitle" target="_blank"
               :href="link" class="dropdown-item">
                <div class="fw-bold">{{ getLinkLabel(linkTitle) }}</div>
                <div v-if="getLinkAuthor(linkTitle)" class="text-small fst-italic">
                    {{ getLinkAuthor(linkTitle) }}
                </div>
                <div>{{ getLinkTitle(linkTitle) }}</div>
            </a>
        </div>
    </div>
</template>

<script>
export default {
    name: "LiturgyMaterialsButton",
    props: {
        service: Object,
    },
    computed: {
        showMaterials() {
            return Object.keys(this.service.liturgicalInfo?.Links || {}).length > 0;
        },
    },
    methods: {
        getLinkLabel(link) {
            if (!link.includes(']')) return this.getLinkTitle(link);
            return link.substring(1, link.indexOf(']'));
        },
        getLinkTitle(link) {
            if (link.includes(']')) link = link.substring(link.indexOf(']') + 1).trim();
            if (link.includes('(')) link = link.substring(0, link.indexOf('(')).trim();
            return link;
        },
        getLinkAuthor(link) {
            if (!link.includes('(')) return '';
            link = (link.substring(link.indexOf('(') + 1, link.indexOf(')')));
            if (isNaN(link.substring(0, 1))) return link;
            return '';
        },
    },
}
</script>

<style scoped>

</style>
