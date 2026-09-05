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
    <admin-layout title="Übersicht der eingenommenen Opfer erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Übersicht der eingenommenen Opfer erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'offeringAmounts'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <city-location-filter
                :cities="cities"
                :locations="locations"
                city-label="Bericht für folgende Kirchengemeinden erstellen"
                location-label="Auf folgende Orte beschränken"
                location-placeholder="Leer lassen für alle Orte"
                v-model:city-model-value="myCities"
                v-model:location-model-value="myLocations"
            />
            <form-date-range-picker label="Gottesdienste von" v-model:from="myStart" v-model:to="myEnd" iso-date />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormDateRangePicker from "../../../components/Ui/forms/FormDateRangePicker";
import CityLocationFilter from "../../../components/Reports/CityLocationFilter.vue";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities', 'locations'],
    components: {CityLocationFilter, FormDateRangePicker, FormCsrfToken, SaveButton},
    data() {
        let myStart = moment().startOf('year');
        let myEnd = moment().endOf('year');

        return {
            myCities: this.cities.length ? [this.cities[0].id] : null,
            myLocations: [],
            myStart,
            myEnd,
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'offeringAmounts'}), {
                cities: this.myCities,
                locations: this.myLocations,
                start: this.myStart,
                end: this.myEnd,
            });
        },
    }
}
</script>

<style scoped>

</style>
