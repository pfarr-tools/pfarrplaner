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
    <admin-layout title="Liste der Wochensprüche erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Liste der Wochensprüche erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'weeklyVerse'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <form-date-range-picker label="Von" v-model:from="myStart" v-model:to="myEnd" iso-date />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDateRangePicker from "../../../components/Ui/forms/FormDateRangePicker";
import FormCheck from "../../../components/Ui/forms/FormCheck";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    components: {FormCheck, FormDateRangePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        let myStart = moment().startOf('month').add(1, 'month');
        let myEnd = moment().endOf('year');

        return {
            myStart,
            myEnd,
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'weeklyVerse'}), {
                start: this.myStart,
                end: this.myEnd,
            });
        },
    }
}
</script>

<style scoped>

</style>
