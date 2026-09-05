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
    <admin-layout title="Kirchliche Nachrichten erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Kirchliche Nachrichten erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'billBoard'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <form-selectize name="cities[]" label="Kirchliche Nachrichten für folgende Kirchengemeinden erstellen" v-model="myCities"
                            @input="setParishes" multiple
                            :options="cities" />
            <form-check name="printHeaders" label="Kopfzeilen drucken" v-model="printHeaders" />
            <form-input name="altCity" label="Alternative Ortsbezeichnung" v-model="altCity" />
            <form-date-picker name="start" label="Gottesdienste ab" v-model="myStart" iso-date />
            <form-selectize name="parishes[]" label="Folgende Pfarrämter mit einbeziehen"
                            v-model="myParishes" :key="'parish_'+cityUpdated"
                            @input="setPastors"
                            :options="availableParishes" multiple />
            <people-select name="pastors[]" label="Urlaub für folgende Pfarrer:innen mit einbeziehen"
                           v-model="myPastors" :key="'pastors_'+parishUpdated" :allow-create="false"
                           :people="availablePastors" multiple />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker";
import FormCheck from "../../../components/Ui/forms/FormCheck";
import PeopleSelect from "../../../components/Ui/elements/PeopleSelect.vue";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities', 'parishes'],
    components: {PeopleSelect, FormCheck, FormDatePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    computed: {
        availableParishes() {
            let p = [];
            this.myCities.forEach(city => p = p.concat(this.parishes[city]));
            return p;
        },
        availablePastors() {
            let p = [];
            this.availableParishes.forEach(parish => p.push(...parish.users));
            return p;
        },
    },
    data() {
        let myStart = moment().startOf('isoWeek').add(6, 'days');

        let presets = this.$page.props.settings['reports_billboard_presets'] || {};
        console.log('presets from setting', presets);
        presets.cities = presets.cities || [];
        presets.parishes = presets.parishes || [];
        presets.pastors = presets.pastors || [];
        presets.altCity = presets.altCity || '';
        presets.printHeaders = presets.printHeaders || false;
        console.log('presets normalized', presets);


        return {
            myCities: presets.cities,
            printHeaders: presets.printHeaders,
            myParishes: presets.parishes,
            myPastors: presets.pastors,
            cityUpdated: 0,
            parishUpdated: 0,
            myStart,
            altCity: presets.altCity,
        }
    },
    mounted() {
        this.$forceUpdate();
        this.setParishes(this.myCity);
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'billBoard'}), {
                cities: this.myCities,
                altCity: this.altCity,
                start: this.myStart,
                parishes: this.myParishes,
                pastors: this.myPastors,
                printHeaders: this.printHeaders,
            });
        },
        setParishes(e) {
            this.myParishes = this.availableParishes.map(({id}) => id);
            this.cityUpdated++;
            this.setPastors(this.myParishes);
        },
        setPastors(e) {
            this.myPastors = this.availablePastors.map(({id}) => id);
            this.parishUpdated++;
        },
    }
}
</script>

<style scoped>

</style>
