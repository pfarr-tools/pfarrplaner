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
        <div v-if="hasData">
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
                                   :services="getServices(city,dayDate)" :city="city" :can-create="canCreate" :loading="loading"
                    />
                </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="month-loading">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th class="no-print text-start city-title"></th>
                    <th v-for="city in cities" class="city-title">
                        <span class="mdi mdi-arrow-down-circle pr-2"></span>
                        {{ city.name }}
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="day in skeletonDays" :key="day.date">
                    <th class="day-header-cell skeleton-day-cell">
                        <div class="skeleton-day-badge"></div>
                        <div class="skeleton-day-line"></div>
                    </th>
                    <calendar-cell
                        v-for="city in cities"
                        :key="'skeleton_'+city.id+'_'+day.date"
                        :day="day"
                        :city="city"
                        :services="[]"
                        :targetMode="targetMode"
                        :target="target"
                        :loading="true"
                    />
                </tr>
                </tbody>
            </table>
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
    props: ['date', 'cities', 'canCreate', 'collapseState', 'targetMode', 'target', 'initialData'],
    data() {
        return {
            loading: false,
            data: this.initialData?.data || null,
            loadedDate: this.initialData?.loadedDate || null,
        }
    },
    mounted() {
        if (this.loadedDate !== this.date) {
            this.loadServices();
        }
    },
    watch: {
        date(newVal) {
            if (this.loadedDate !== newVal) {
                this.loadServices();
            }
        },
    },
    methods: {
        loadServices() {
            this.loading = true;
            this.data = null;
            this.$api().get(route('api.calendar.month', {
                date: this.date,
            })).then(response => {
                this.data = response.data.data;
                this.loadedDate = response.data.loadedDate;
                this.loading = false;
                this.$forceUpdate();
            });
        },
        getServices(city, day) {
            if (this.data[day] == undefined) return [];
            if (this.data[day].services == undefined) return [];
            if (!city.is_org) return this.data[day].services[city.id] || [];

            // org results: include all child ids
            // "unpack" the lists by ids to avoid duplicates
            let result = {};
            (this.data[day].services[city.id] || []).forEach(item => {
                result[item.id] = item;
            });
            city.childIds.forEach(childId => {
                (this.data[day].services[childId] || []).forEach(item => {
                    result[item.id] = item;
                });
            });
            return Object.values(result);
        },
    },
    computed: {
        hasData() {
            return !!this.data && Object.keys(this.data).length > 0;
        },
        skeletonDays() {
            const firstDay = moment(this.date + '-01');
            const result = [];
            const dayCount = firstDay.daysInMonth();
            for (let day = 1; day <= dayCount; day++) {
                result.push({ date: firstDay.date(day).format('YYYY-MM-DD') });
            }
            return result;
        },
    }
}
</script>
<style scoped>
    .city-title {
        position: sticky;
        top: 0px;
        background-color: #f4f6f9;
    }

    .btn-xs {
        padding: 0.75em 1em;
    }

    .month-loading {
        min-height: 50vh;
    }

    .skeleton-day-cell {
        min-width: 100px;
        background-color: #f8fafc;
    }

    .skeleton-day-badge,
    .skeleton-day-line {
        border-radius: 4px;
        background: linear-gradient(90deg, #f3f5f7 25%, #e7ebef 37%, #f3f5f7 63%);
        background-size: 400% 100%;
        animation: skeleton-shimmer 1.4s ease infinite;
    }

    .skeleton-day-badge {
        height: 1.2rem;
        width: 55%;
        margin-bottom: 0.6rem;
    }

    .skeleton-day-line {
        height: 0.8rem;
        width: 75%;
    }

    @keyframes skeleton-shimmer {
        0% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0 50%;
        }
    }


</style>
