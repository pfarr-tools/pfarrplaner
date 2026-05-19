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
    <admin-layout title="Opferplan ausgeben">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Opferplan ausgeben" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'offeringPlan'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <form-selectize name="cities[]" label="Opferplan für folgende Kirchengemeinden erstellen" :options="cities" v-model="myCities" multiple />
            <form-input name="year" label="Jahr" v-model="myYear" type="number" />
            <br />
            <form-check name="includeOfferingCounters" v-model="myIncludeOfferingCounters" label="Opferzähler mit ausgeben"/>
            <form-check name="emptyAsOwn" v-model="myEmptyAsOwn" label="Leere Felder als &quot;eigene Gemeinde&quot; ausgeben"/>
            <form-check name="highlightEmpty" v-model="myHighlightEmpty" label="Fehlende Einträge hervorheben"/>
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker";
import FormCheck from "../../../components/Ui/forms/FormCheck.vue";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities'],
    components: {FormCheck, FormDatePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        return {
            myCities: this.cities.length > 0 ? [this.cities[0].id] : null,
            myYear: moment().format('YYYY'),
            myIncludeOfferingCounters: false,
            myEmptyAsOwn: true,
            myHighlightEmpty: false,
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'offeringPlan'}), {
                cities: this.myCities,
                year: this.myYear,
                includeOfferingCounters: this.myIncludeOfferingCounters,
                emptyAsOwn: this.myEmptyAsOwn,
                highlightEmpty: this.myHighlightEmpty,
            });
        },
    }
}
</script>

<style scoped>

</style>
