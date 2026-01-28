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
    <admin-layout :title="getTitle()">
        <template slot="navbar-left">
            <button class="btn btn-primary me-1" title="Speichern" @click="savePoolmaster">
                <span class="d-inline d-md-none mdi mdi-content-save"></span> <span class="d-none d-md-inline">Speichern</span>
            </button>
            <button class="btn btn-danger" title="Löschen" @click="deletePoolmaster">
                <span class="d-inline d-md-none mdi mdi-delete"></span> <span class="d-none d-md-inline">Löschen</span>
            </button>
        </template>
        <form-selectize label="Pool" :options="pools" v-model="myPoolmaster.pool_id" />
        <date-range-input label="Zeitraum" :from="myPoolmaster.start" :to="myPoolmaster.end"
                          @input="setDateRange" />
    </admin-layout>
</template>

<script>
import FormInput from "../../../components/Ui/forms/FormInput.vue";
import FormSelectize from "../../../components/Ui/forms/FormSelectize.vue";
import PeopleSelect from "../../../components/Ui/elements/PeopleSelect.vue";
import DateRangeInput from "../../../components/Ui/elements/DateRangeInput.vue";

export default {
    name: "Editor",
    components: {DateRangeInput, PeopleSelect, FormSelectize, FormInput},
    props: ['poolmaster', 'pools', 'date', 'user'],
    data() {
        return {
            myPoolmaster: this.poolmaster.id ? {
                start: moment(this.poolmaster.start).startOf('day'),
                end: moment(this.poolmaster.end).endOf('day'),
                ...this.poolmaster,
            } : {
                id: null,
                user_id: this.user.id,
                pool_id: this.pools.length ? this.pools[0].id : null,
                start: this.date,
                end: moment(this.date).endOf('month'),
            }
        }
    },
    methods: {
        getTitle() {
            if (!this.myPoolmaster.id) return 'Poolmaster:in werden';
            return 'Einsatz als Poolmaster:in bearbeiten';
        },
        setDateRange(e) {
            this.myPoolmaster.start = moment(e[0]).format('YYYY-MM-DD HH:mm:ss');
            this.myPoolmaster.end = moment(e[1]).format('YYYY-MM-DD HH:mm:ss');
        },
        savePoolmaster() {
            if (!this.myPoolmaster.id) {
                this.$inertia.post(route('admin.poolmasters.store'), this.myPoolmaster);
            } else {
                this.$inertia.patch(route('admin.poolmaster.update', {modelId: this.myPoolmaster.id}), this.myPoolmaster);
            }
        },
        deletePoolmaster() {
            if (!this.myPoolmaster.id) window.history.back();
            this.$inertia.delete(route('admin.poolmaster.destroy', {modelId: this.myPoolmaster.id }));
        }
    },
}
</script>

<style scoped>

</style>
