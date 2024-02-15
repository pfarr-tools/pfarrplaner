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
    <div class="calendar-month calendar-vertical">
        <div v-if="!loading">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th class="no-print text-start city-title"><!-- // TODO: slave mode --></th>
                    <th v-for="city in cities" class="city-title">
                        <span class="mdi mdi-arrow-down-circle pr-2"></span>
                        {{ city.name }}
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(day,dayDate) in data">
                    <calendar-day-header
                        :day="day"
                        :key="dayDate"
                    />
                    <calendar-cell v-for="(city,index) in cities" :day="day" :key="city.id" :targetMode="targetMode" :target="target"
                                   :services="getServices(city,dayDate)" :city="city" :can-create="canCreate"
                    />
                </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="month-loading">
            <div><span class="mdi mdi-spin mdi-loading"></span></div>
            <div>Kalender wird geladen</div>
        </div>
    </div>
</template>

<script>

import NavButton from "../../Ui/buttons/NavButton";
import CalendarDayHeader from "../Day/Header.vue";
import CalendarCell from "../Cell.vue";
export default {
    name: 'CalendarPaneVertical',
    components: {CalendarCell, CalendarDayHeader, NavButton},
    props: ['date', 'cities', 'canCreate', 'collapseState', 'targetMode', 'target'],
    data() {
        return {
            loading: false,
            data: null,
        }
    },
    mounted() {
        this.loadServices();
    },
    watch: {
        date(newVal, oldVal) {
            this.loadServices();
        },
    },
    methods: {
        loadServices() {
            this.loading = true;
            this.$api().get(route('api.calendar.month', {
                date: this.date,
            })).then(response => {
                this.data = response.data.data;
                this.loading = false;
                this.$forceUpdate();
            });
        },
        getServices(city, day) {
            if (this.data[day] == undefined) return [];
            if (this.data[day].services == undefined) return [];
            if (this.data[day].services[city.id] == undefined) return [];
            return this.data[day].services[city.id];
        },
    }
}
</script>
<style scoped>
    .city-title {
        position: sticky;
        top: 58px;
        background-color: #f4f6f9;
    }

    .btn-xs {
        padding: 0.75em 1em;
    }

    .month-loading {
        font-size: 3em;
        font-width: bold;
        color: lightgray;
        text-align: center;
        padding-top: 25vh;
    }


</style>
