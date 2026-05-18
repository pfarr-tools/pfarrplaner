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
    <div class="form-inline align-items-center">
        <label :for="selectId">Zeige</label>
        <select :id="selectId" :value="dsShowEntries" class="form-control me-1 ms-1" @change="change" aria-label="Anzahl der angezeigten Datensätze">
            <option v-for="option in dsShowEntriesLovs" :key="option.value" :value="option.value">
                {{ option.text }}
            </option>
        </select>
        <label>Datensätze</label>
    </div>
</template>

<script>
export default {
    inject: ['showEntries'],
    props: {
        dsShowEntries: {
            type: Number,
            default: 10
        },
        dsShowEntriesLovs: {
            type: Array,
            default: () => [
                { value: 5, text: 5 },
                { value: 10, text: 10 },
                { value: 25, text: 25 },
                { value: 50, text: 50 },
                { value: 100, text: 100 }
            ]
        }
    },
    computed: {
        selectId() {
            return 'dataset-show-entries';
        },
    },
    created() {
        this.showEntries(Number(this.dsShowEntries))
    },
    methods: {
        change(e) {
            this.$emit('changed', Number(e.target.value))
            this.showEntries(Number(e.target.value))
        }
    }
}
</script>

<style scoped>
    .form-control {
        display: inline-block;
        width: auto !important;
    }
</style>
