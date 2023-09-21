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
            <save-button label="Erstellen" title="Dienstplan für einen Dienst erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'singleMinistry'})" ref="myForm">
            <form-csrf-token />
            <form-selectize name="cities[]" label="Plan für folgende Kirchengemeinden erstellen"
                            :options="cities" v-model="myCities" multiple />
            <form-selectize name="ministries[]" label="Dienste" :options="myMinistries"
                            v-model="mySelectedMinistries" multiple />
            <form-date-picker name="start" label="Gottesdienste von" v-model="myStart" iso-date />
            <form-date-picker name="end" label="Bis" v-model="myEnd" iso-date />
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
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker";
import FormRadioGroup from "../../../components/Ui/forms/FormRadioGroup.vue";
import FormCheck from "../../../components/Ui/forms/FormCheck.vue";
export default {
    name: "Setup",
    props: ['cities', 'ministries'],
    components: {FormCheck, FormRadioGroup, FormDatePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
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
            mySelectedMinistries: 'P',
            myCities: this.cities.length > 0 ? [this.cities[0].id] : [],
            myFileFormat: 'pdf',
            includeHeader: true,
        }
    },
    methods: {
        renderReport() {
            this.$refs.myForm.submit();
        },
    }
}
</script>

<style scoped>

</style>
