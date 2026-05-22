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
    <admin-layout title="Themenplan der Gottesdienste erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Themenplan der Gottesdienste erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'serviceThemes'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <city-location-filter
                :cities="cities"
                :locations="locations"
                city-label="Themenplan für folgende Kirchengemeinden erstellen"
                location-label="Auf folgende Orte beschränken"
                location-placeholder="Leer lassen für alle Orte"
                v-model:city-model-value="myCities"
                v-model:location-model-value="myLocations"
            />
            <form-input name="year" label="Jahr" v-model="myYear" type="number" />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import CityLocationFilter from "../../../components/Reports/CityLocationFilter.vue";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities', 'locations'],
    components: {CityLocationFilter, FormInput, FormCsrfToken, SaveButton},
    data() {

        return {
            myUser: this.$page.props.currentUser.data.id,
            myCities: this.cities.length > 0 ? [this.cities[0].id] : [],
            myLocations: [],
            myYear: moment().format('YYYY'),
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'serviceThemes'}), {
                cities: this.myCities,
                locations: this.myLocations,
                year: this.myYear,
            });
        },
    }
}
</script>

<style scoped>

</style>
