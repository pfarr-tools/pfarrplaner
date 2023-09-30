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
    <div class="calendar-month calendar-horizontal">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th class="no-print text-left"></th>
                <calendar-day-header
                    v-for="(day,index) in myDays"
                    :day="day"
                    :key="'_day_'+index"
                    :index="index"
                    :absences="absences[day.id]"
                />
            </tr>
            </thead>
            <tbody>
                <tr v-for="city in cities">
                    <th class="city-title">
                        <span class="pt-2">{{ city.name }}</span><span class="mdi mdi-arrow-down-circle pt-2"></span>
                    </th>
                    <calendar-cell v-for="(day,index) in myDays" :day="day"
                                   :targetMode="targetMode" :target="target"
                                   :key="city.loading+'_'+day.date+'_'+city.id"
                                   :index="index" :services="getServices(city,day)"
                                   :city="city" :can-create="canCreate" />
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>

import NavButton from "../../Ui/buttons/NavButton";
export default {
    components: {NavButton},
    props: ['date', 'days', 'cities', 'services', 'years', 'absences', 'canCreate','collapseState', 'targetMode', 'target'],
    data() {
        var myDays = this.days;

        for (let dayId in myDays) {
            myDays[dayId].index = dayId;
        }
        return {
            myDays: myDays,
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
    },
    computed: {
        serviceCount() {
            return this.services.length;
        }
    }
}
</script>

<style scoped>
    table.table thead th {
        vertical-align: top;
    }

    .city-title {
        writing-mode: sideways-lr;
        padding: 1px;
    }

    .btn-xs.mt-3 {
        padding: 1em 0.75em;
    }

</style>
