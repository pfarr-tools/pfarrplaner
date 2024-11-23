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
    <div class="people-tab">
        <div class="row">
            <div class="col-md-6">
                <people-select label="Vorsitz" :people="people" :teams="teams"
                               v-model="myService.ministriesByCategory['Vorsitz']"
                               :include-teams-from-city="myService.city" :city="myService.city"
                                @count="updatePeopleCounter" />
            </div>
            <div class="col-md-6">
                <people-select label="Schriftführung" :people="people" :teams="teams"
                               v-model="myService.ministriesByCategory['Protokoll']"
                               :include-teams-from-city="myService.city" :city="myService.city"
                               @count="updatePeopleCounter" />
            </div>
        </div>
        <div><label><span class="mdi mdi-account-multiple"></span> Weitere Rollen</label></div>
        <div class="row">
            <div class="col-md-6"><label>Rolle</label></div>
            <div class="col-md-6"><label>Eingeteilte Personen</label></div>
        </div>
        <ministry-row v-for="(members,title,index) in myService.ministriesByCategory"
                      v-if="!['Protokoll', 'Vorsitz'].includes(title)"
                      :title="title" :members="members" :index="index" :people="people" :teams="teams"
                      :key="'ministry_rows'+Object.entries(myService.ministriesByCategory).length+'_'+index"
                      :ministries="ministries" v-model="myService.ministriesByCategory" @delete="deleteRow"
                      :include-teams-from-city="myService.city" :city="myService.city"
                      @count="updatePeopleCounter" />
        <button class="btn btn-light btn-sm" @click.prevent.stop="addRow">Reihe hinzufügen</button>
    </div>
</template>

<script>
import FormInput from "../../Ui/forms/FormInput";
import LocationSelect from "../../Ui/elements/LocationSelect";
import DaySelect from "../../Ui/elements/DaySelect";
import PeopleSelect from "../../Ui/elements/PeopleSelect";
import FormCheck from "../../Ui/forms/FormCheck";
import MinistryRow from "../../Ui/elements/MinistryRow";
import FormRadioGroup from "../../Ui/forms/FormRadioGroup";

export default {
    name: "AttendanceTab",
    components: {
        FormRadioGroup,
        MinistryRow,
        PeopleSelect,
        DaySelect,
        LocationSelect,
        FormInput,
        FormCheck,
    },
    props: {
        service: Object,
        locations: Array,
        days: Array,
        people: Array,
        ministries: Array,
        teams: Array,
    },
    data() {
        if (!this.service.ministriesByCategory) this.service.ministriesByCategory = {};
        if (this.service.ministriesByCategory.length == 0) this.service.ministriesByCategory = {};
        if (undefined === this.service.ministriesByCategory['Vorsitz']) this.service.ministriesByCategory['Vorsitz'] = [];
        if (undefined === this.service.ministriesByCategory['Protokoll']) this.service.ministriesByCategory['Protokoll'] = [];

        let myService = this.service;

        return {
            myService,
            myCommittees: [],
        }
    },
    mounted() {
        this.$api().get(route('api.committees.index')).then(response => {
            console.log(response.data);
        });
    },
    methods: {
        addRow() {
            this.myService.ministriesByCategory['Neuer Dienst'] = [];
            this.$forceUpdate();
        },
        deleteRow(category) {
            delete this.myService.ministriesByCategory[category];
            this.updatePeopleCounter();
            this.$forceUpdate();
        },
        updatePeopleCounter() {
            this.$emit('count');
        },
    }
}
</script>

<style scoped>

</style>
