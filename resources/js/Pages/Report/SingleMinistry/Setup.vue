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
    <admin-layout title="Dienstplan für einzelne Dienste erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Dienstplan für einzelne Dienste erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'singleMinistry'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <city-location-filter
                :cities="cities"
                :locations="locations"
                city-label="Plan für folgende Kirchengemeinden erstellen"
                location-label="Auf folgende Orte beschränken"
                location-placeholder="Leer lassen für alle Orte"
                v-model:city-model-value="myCities"
                v-model:location-model-value="myLocations"
            />
            <form-selectize name="ministries[]" label="Dienste" :options="myMinistries"
                            v-model="mySelectedMinistries" multiple />
            <form-date-range-picker label="Gottesdienste von" v-model:from="myStart" v-model:to="myEnd" iso-date />
            <form-radio-group name="file_format" label="Dateiformat" v-model="myFileFormat" :items="{
                            'pdf': 'PDF-Datei',
                            'xlsx': 'Microsoft Excel-Tabelle',
                        }"/>
            <form-check name="includeHeader" label="Überschriftenblock mit ausgeben" v-model="includeHeader" />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormDateRangePicker from "../../../components/Ui/forms/FormDateRangePicker";
import FormRadioGroup from "../../../components/Ui/forms/FormRadioGroup.vue";
import FormCheck from "../../../components/Ui/forms/FormCheck.vue";
import CityLocationFilter from "../../../components/Reports/CityLocationFilter.vue";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities', 'locations', 'ministries'],
    components: {CityLocationFilter, FormCheck, FormRadioGroup, FormDateRangePicker, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        let myMinistries = [];
        for (let key in this.ministries) {
            myMinistries.push({ id: key, name: this.ministries[key]});
        }

        return {
            myUser: this.$page.props.currentUser.data.id,
            myStart: moment(),
            myEnd: moment().endOf('year'),
            myMinistries,
            mySelectedMinistries: ['P'],
            myCities: this.cities.length > 0 ? [this.cities[0].id] : [],
            myLocations: [],
            myFileFormat: 'pdf',
            includeHeader: true,
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'singleMinistry'}), {
                cities: this.myCities,
                locations: this.myLocations,
                ministries: this.mySelectedMinistries,
                start: this.myStart,
                end: this.myEnd,
                file_format: this.myFileFormat,
                includeHeader: this.includeHeader,
            });
        },
    }
}
</script>

<style scoped>

</style>
