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
    <admin-layout title="Jahresplan der Gottesdienste erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Jahresplan der Gottesdienste erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'serviceTable'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <form-selectize name="cities[]" label="Jahresplan für folgende Kirchengemeinden erstellen" :options="cities" v-model="myCities" multiple/>
            <form-input name="year" label="Jahr" v-model="myYear" type="number" />
            <form-selectize name="ministries[]" label="Folgende Dienste mit einschließen" :options="ministries" v-model="myMinistries" multiple/>
            <form-selectize name="name_format" label="Namen ausgeben als" :options="nameFormats" v-model="myNameFormat" />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities', 'ministries'],
    components: {FormDatePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        let nameFormats = [
            {id: 1, name: 'Pfr. Müller'},
            {id: 2, name: 'Pfr. K. Müller'},
            {id: 3, name: 'Pfr. Karl Müller'},
        ];


        return {
            myUser: this.$page.props.currentUser.data.id,
            myMinistries: [],
            myCities: this.cities.length > 0 ? [this.cities[0].id] : [],
            myYear: moment().format('YYYY'),
            nameFormats,
            myNameFormat: 3,
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'serviceTable'}), {
                cities: this.myCities,
                year: this.myYear,
                ministries: this.myMinistries,
                name_format: this.myNameFormat,
            });
        },
    }
}
</script>

<style scoped>

</style>
