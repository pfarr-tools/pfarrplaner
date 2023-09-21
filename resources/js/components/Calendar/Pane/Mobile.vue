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
                <th></th>
                <th v-for="city in cities">{{ city.name.substr(0, 5) }}</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(day,index) in days">
                <th :title="dayTooltip(day)">
                    <span class="badge"
                          :class="dayBadgeClass(day)">
                        {{ moment(day.date).locale('de').format('ddd DD') }}
                    </span>
                    <div v-if="day.liturgy.title" class="liturgy">{{ day.liturgy.title }}</div>
                </th>
                <td class="day-cell" v-for="(city,index) in cities"
                    @click="goToDay(day.date,city.id)">
                    <div v-if="city.loading"><span class="mdi mdi-spin mdi-loading"></span></div>
                    <div class="service-info" v-for="(service, serviceIndex) in getServices(city,day)"
                         :key="service.id"
                         :class="{ mine: service.isMine, funeral: (service.funerals.length > 0)}">
                        <div>{{ service.timeText }}</div>
                        <div>{{ service.locationText }}</div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>

export default {
    name: 'CalendarPaneMobile',
    props: ['date', 'days', 'cities', 'services', 'years', 'absences', 'canCreate', 'loading'],
    methods: {
        title: function (d) {
            return moment(d).locale('de-DE').format('MMMM YYYY');
        },
        getServices(city, day) {
            if (this.services[city.id] == undefined) return [];
            if (this.services[city.id][day.date] == undefined) return [];
            return this.services[city.id][day.date];
        },
        dayBadgeClass(day) {
            if (moment(day.date).isoWeekday() == 7) return 'badge-danger';
            return 'badge-light';
        },
        dayTooltip(day) {
            var items = [
                moment(day.date).locale('de').format('dddd, DD. MMMM YYYY'),
            ];
            if (day.liturgy.title) items.push(day.liturgy.title);
            return items.join('&#10;');
        },
        servicesBadgeClass(city, day) {
            var mine = false;
            this.getServices(city, day).forEach(service => {
                mine = mine || service.isMine;
            })
            return mine ? 'badge-success' : 'badge-secondary';
        },
        goToDay(dayId, cityId) {
            this.$inertia.visit(route('calendar.day', {day: dayId, city: cityId}), { preserveScroll: false, preserveState: false });
        }
    }
}
</script>

<style scoped>
table thead th {
}


tbody th {
    vertical-align: top;
    font-size: .8em;
}

.badge-light {
    background-color: white;
    border: solid 1px lightgray;
}

th .badge-info {
    background-color: #DCD0FF;
    color: black;
}

.liturgy {
    font-size: .6em;
}

.service-info {
    font-size: .5em;
    margin-bottom: .5rem;
}

.service-info.mine {
    background-color: lightgreen;
}

.service-info.funeral {
    background-color: lightgray;
}

.service-info.funeral.mine {
    background-color: #A9CDA9;
}

td.day-cell {
    padding: 1px;
    cursor: pointer;
}

td.day-cell:hover {
    padding: 1px;
    cursor: pointer;
    background-color: lightyellow;
}
</style>
