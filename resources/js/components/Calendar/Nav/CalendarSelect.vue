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

<script>
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

export default {
    name: 'CalendarSelect',
    props: ['modelValue', 'calendars'],
    components: { Multiselect },
    emits: ['update:modelValue'],
    data() {
        return {
            myValue: this.modelValue,
        };
    },
    watch: {
        modelValue(v) { this.myValue = v; },
    },
    computed: {
        groupedCalendars() {
            const groups = {};
            (this.calendars || []).forEach(item => {
                const g = item.group || 'Kalender';
                if (!groups[g]) groups[g] = [];
                groups[g].push(item);
            });
            return Object.entries(groups).map(([label, options]) => ({ label, options }));
        },
    },
    methods: {
        changed(val) {
            this.$emit('update:modelValue', val);
        },
    },
};
</script>

<template>
    <div class="calendar-select">
        <Multiselect
            class="form-control ms-1 mt-1"
            :model-value="myValue"
            mode="tags"
            :groups="true"
            :options="groupedCalendars"
            value-prop="id"
            label="name"
            :searchable="true"
            locale="de"
            :no-results-text="{ de: 'Keine Ergebnisse gefunden', en: 'No results found' }"
            :no-options-text="{ de: 'Die Liste ist leer', en: 'The list is empty' }"
            @change="changed"
        />
    </div>
</template>

<style scoped>
.calendar-select {
    min-width: 200px;
}
</style>
