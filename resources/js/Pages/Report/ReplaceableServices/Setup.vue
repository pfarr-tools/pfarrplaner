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
    <admin-layout title="Zu vertretende Dienste für eine Person finden">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Zu vertretende Dienste für eine Person finden" @click="renderReport" />
            <nav-button title="Zur Übersicht der Diensten mit Möglichkeit zum Eintragen" icon="mdi mdi-list" class="ms-1" @click="wizard">Direkt eintragen</nav-button>
        </template>
        <form method="post" :action="myAction" ref="myForm" :key="myAction">
            <form-csrf-token />
            <form-selectize name="person" label="Nach folgender Person suchen" :options="users" v-model="myUser" />
            <form-date-picker name="start" label="Dienste von" v-model="myStart" iso-date />
            <form-date-picker name="end" label="Bis" v-model="myEnd" iso-date />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker";
import NavButton from "../../../components/Ui/buttons/NavButton.vue";
export default {
    name: "Setup",
    props: ['users'],
    components: {NavButton, FormDatePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        return {
            myUser: this.$page.props.currentUser.data.id,
            myStart: moment(),
            myEnd: moment().endOf('year'),
            myAction: route('reports.render', {report: 'replaceableServices'}),
        }
    },
    methods: {
        renderReport() {
            this.myAction = route('reports.render', {report: 'replaceableServices'});
            this.$refs.myForm.action = this.myAction;
            this.$refs.myForm.submit();
        },
        wizard() {
            this.myAction = route('report.step', {report: 'replaceableServices', step: 'wizard'})
            this.$refs.myForm.action = this.myAction;
            this.$refs.myForm.submit();
        }
    }
}
</script>

<style scoped>

</style>
