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
    <admin-layout title="Opferplan bearbeiten">
        <template #navbar-left>
            <nav-button type="primary" title="Opferplan anzeigen" icon="mdi mdi-table"
                        @click="showTable">Plan anzeigen</nav-button>
        </template>
        <form-selectize label="Opferplan für folgende Kirchengemeinden bearbeiten" :options="cities"
                        v-model="setup.cities" name="cities" multiple />
        <form-selectize label="Auf folgende Orte beschränken" placeholder="Leer lassen für alle Orte"
                        v-model="setup.locations" :options="locations" multiple />
        <date-range-input label="Zeitraum" :model-value="dateRange" @update:modelValue="onDateRangeChange" />
    </admin-layout>
</template>

<script>
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import NavButton from "../../../components/Ui/buttons/NavButton";
import LocationSelect from "../../../components/Ui/elements/LocationSelect";
import DateRangeInput from "../../../components/Ui/elements/DateRangeInput";
export default {
    name: "Setup",
    components: {DateRangeInput, LocationSelect, NavButton, FormSelectize},
    props: ['cities', 'locations'],
    data() {
        return {
            setup: {
                from: moment().startOf('year').format('DD.MM.YYYY'),
                to: moment().endOf('year').format('DD.MM.YYYY'),
                cities: this.cities.length ? [this.cities[0].id] : null,
                locations: [],
            }
        }
    },
    computed: {
        dateRange() {
            return [
                this.setup.from ? moment(this.setup.from, 'DD.MM.YYYY') : null,
                this.setup.to ? moment(this.setup.to, 'DD.MM.YYYY') : null,
            ];
        },
    },
    methods: {
        onDateRangeChange(val) {
            if (val && val.length === 2 && val[1]) {
                this.setup.from = moment(val[0]).format('DD.MM.YYYY');
                this.setup.to = moment(val[1]).format('DD.MM.YYYY');
            }
        },
        showTable() {
            this.$inertia.post(route('inputs.input', 'offerings'), this.setup);
        }
    }
}
</script>

<style scoped>

</style>
