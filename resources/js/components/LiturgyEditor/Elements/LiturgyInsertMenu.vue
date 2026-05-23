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
    <div class="dropdown liturgy-insert-menu">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle liturgy-insert-menu__button" type="button"
                data-bs-toggle="dropdown" aria-expanded="false" @click.stop
                :title="label">
            <span class="mdi mdi-plus"></span>
            <span class="d-none d-md-inline ms-1">{{ label }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end" @click.stop>
            <button type="button" class="dropdown-item" @click.prevent="insert('Freetext')">
                <span class="mdi mdi-text me-2"></span> Freitext
            </button>
            <button type="button" class="dropdown-item" @click.prevent="insert('Psalm')">
                <span class="mdi mdi-hands-pray me-2"></span> Psalm
            </button>
            <button type="button" class="dropdown-item" @click.prevent="insert('Reading')">
                <span class="mdi mdi-book-open-variant me-2"></span> Schriftlesung
            </button>
            <button type="button" class="dropdown-item" @click.prevent="insert('Sermon')">
                <span class="mdi mdi-microphone me-2"></span> Predigt
            </button>
            <button type="button" class="dropdown-item" @click.prevent="insert('Song')">
                <span class="mdi mdi-music me-2"></span> Lied
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "LiturgyInsertMenu",
    props: {
        label: {
            type: String,
            default: 'Hier einfügen',
        },
    },
    emits: ['insert'],
    methods: {
        insert(type) {
            this.$emit('insert', type);
        },
    },
}
</script>

<style scoped>
.liturgy-insert-menu__button {
    border-radius: 999px;
    background: rgba(var(--bs-white-rgb), 0.96);
}

.liturgy-insert-menu__button:hover,
.liturgy-insert-menu__button:focus,
.liturgy-insert-menu__button.show {
    background: rgba(var(--bs-primary-rgb), 0.1);
    border-color: rgba(var(--bs-primary-rgb), 0.28);
    color: var(--bs-primary);
}
</style>
