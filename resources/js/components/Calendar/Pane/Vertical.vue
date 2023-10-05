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
                <tr v-for="day,index in myDays">
                    <calendar-day-header
                        :day="day"
                        :key="day.id"
                        :scroll-to-date="scrollToDate"
                        :absences="absences[day.id]"  />
                    <calendar-cell v-for="(city,index) in cities" :day="day" :key="city.id" :targetMode="targetMode" :target="target"
                                   :services="getServices(city,day)" :city="city" :can-create="canCreate"
                                    />
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>

import NavButton from "../../Ui/buttons/NavButton";
import CalendarDayHeader from "../Day/Header.vue";
import CalendarCell from "../Cell.vue";
export default {
    name: 'CalendarNavVertical',
    components: {CalendarCell, CalendarDayHeader, NavButton},
    props: ['date', 'days', 'cities', 'services', 'years', 'absences', 'canCreate', 'collapseState', 'targetMode', 'target'],
    data() {
        var myDays = this.days;
        var scrollToDate = null;

        for (let dayId in myDays) {
            myDays[dayId].index = dayId;
            if (moment(myDays[dayId].date) <= moment()) scrollToDate = myDays[dayId].date;

        }

        return {
            myDays: myDays,
            scrollToDate,
        }
    },
    methods: {
        title: function (d) {
            return moment(d).locale('de-DE').format('MMMM YYYY');
        },
        getServices(city, day) {
            if (this.services[city.id] == undefined) return [];
            if (this.services[city.id][day.id] == undefined) return [];
            return this.services[city.id][day.id];
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

</style>
