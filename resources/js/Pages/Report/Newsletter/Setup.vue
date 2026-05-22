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
    <admin-layout title="Gottesdienstliste für den Newsletter erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Newsletter erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'newsletter'})" ref="myForm">
            <form-csrf-token />
            <city-location-filter
                :cities="cities"
                :locations="locations"
                city-label="Newsletter für folgende Kirchengemeinden erstellen"
                location-label="Auf folgende Orte beschränken"
                location-placeholder="Leer lassen für alle Orte"
                v-model:city-model-value="myForm.cities"
                v-model:location-model-value="myForm.locations"
            />
            <form-check name="includeWeeklyVerse" label="Wochenspruch mit aufnehmen." v-model="myForm.includeWeeklyVerse"/>
            <form-date-range-picker label="Gottesdienste von" v-model:from="myForm.start" v-model:to="myForm.end" iso-date />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormDateRangePicker from "../../../components/Ui/forms/FormDateRangePicker";
import FormCheck from "../../../components/Ui/forms/FormCheck";
import CityLocationFilter from "../../../components/Reports/CityLocationFilter.vue";
export default {
    name: "Setup",
    props: ['cities', 'locations'],
    components: {CityLocationFilter, FormCheck, FormDateRangePicker, FormCsrfToken, SaveButton},
    data() {
        let myStart = moment();
        let myEnd = moment().add(7, 'days');

        return {
            myForm: {
                cities: this.cities.length ? [this.cities[0].id] : null,
                locations: [],
                start: myStart,
                end: myEnd,
                includeWeeklyVerse: false,
            }
        }
    },
    methods: {
        renderReport() {
            this.$inertia.post(route('reports.render', 'newsletter'), this.myForm);
        },
    }
}
</script>

<style scoped>

</style>
