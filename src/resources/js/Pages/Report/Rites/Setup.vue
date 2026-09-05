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
    <admin-layout title="Freud & Leid für den Gemeindebrief erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Freud & Leid für den Gemeindebrief erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'rites'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <form-selectize name="includeCities[]" label="Folgende Kirchengemeinden mit einbeziehen"
                            v-model="myCities" :options="cities" multiple/>
            <h4>Kausalien</h4>
            <form-date-range-picker label="Auflisten von" v-model:from="myStart" v-model:to="myEnd" iso-date />
            <hr />
            <h4>Nächste Tauftermine</h4>
            <form-date-range-picker label="Auflisten von" v-model:from="myBaptismDatesStart" v-model:to="myBaptismDatesEnd" name-from="baptismDatesStart" name-to="baptismDatesEnd" iso-date />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDateRangePicker from "../../../components/Ui/forms/FormDateRangePicker";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities'],
    components: {FormDateRangePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        return {
            myCities: this.cities.length ? [this.cities[0].id] : null,
            myStart: moment().subtract(3, 'month').startOf('month'),
            myEnd: moment().startOf('month').subtract(1, 'day'),
            myBaptismDatesStart: moment().startOf('month').add(1, 'month'),
            myBaptismDatesEnd: moment().endOf('month').add(4, 'month'),
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'rites'}), {
                includeCities: this.myCities,
                start: this.myStart,
                end: this.myEnd,
                baptismDatesStart: this.myBaptismDatesStart,
                baptismDatesEnd: this.myBaptismDatesEnd,
            });
        },
    }
}
</script>

<style scoped>

</style>
