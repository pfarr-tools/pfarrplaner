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
        <div class="calendar-grid-wrapper" :class="{'month-loading': loading}">
            <table class="table table-bordered table-sm mb-0 calendar-grid">
                <colgroup>
                    <col class="day-column">
                    <col v-for="city in cities" :key="'col_'+city.id" class="city-column">
                </colgroup>
                <thead>
                <tr>
                    <th class="no-print text-start city-title day-column-header"></th>
                    <th v-for="city in cities" :key="'header_'+city.id" class="city-title">
                        <div class="city-title-content">
                            <span class="mdi mdi-map-marker-outline city-title-icon"></span>
                            <span class="city-title-text">{{ city.name }}</span>
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(day, dayDate) in monthDays" :key="dayDate">
                    <calendar-day-header
                        :day="day"
                        :key="dayDate"
                    />
                    <calendar-cell
                        v-for="city in cities"
                        :key="'cell_'+city.id+'_'+dayDate"
                        :day="day"
                        :city="city"
                        :services="getServices(city, dayDate)"
                        :targetMode="targetMode"
                        :target="target"
                        :loading="loading"
                        @deleted="removeService"
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
            data: this.initialData?.data || this.createMonthShell(this.initialData?.loadedDate || this.date),
            loadedDate: this.initialData?.loadedDate || null,
            loadingRequestDate: null,
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
        createMonthShell(targetDate) {
            const monthStart = moment(targetDate + '-01');
            const result = {};
            const dayCount = monthStart.daysInMonth();

            for (let day = 1; day <= dayCount; day++) {
                const dayDate = monthStart.clone().date(day).format('YYYY-MM-DD');
                result[dayDate] = {
                    date: dayDate,
                    liturgy: {},
                    absences: [],
                    services: {},
                };
            }

            return result;
        },
        loadServices() {
            const requestDate = this.date;
            this.loading = true;
            this.loadingRequestDate = requestDate;
            this.data = this.createMonthShell(requestDate);
            this.$api().get(route('api.calendar.month', {
                date: requestDate,
            })).then(response => {
                if (this.loadingRequestDate !== requestDate) return;
                this.data = response.data.data;
                this.loadedDate = response.data.loadedDate;
                this.loading = false;
                this.$forceUpdate();
            }).catch(() => {
                if (this.loadingRequestDate !== requestDate) return;
                this.loading = false;
            });
        },
        getServices(city, day) {
            if (this.data?.[day] == undefined) return [];
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
        removeService(service) {
            const serviceDate = service?.date;
            const serviceId = service?.id;
            if (!serviceDate || !serviceId || !this.data?.[serviceDate]?.services) return;

            Object.keys(this.data[serviceDate].services).forEach(cityId => {
                this.data[serviceDate].services[cityId] = (this.data[serviceDate].services[cityId] || [])
                    .filter(item => item.id !== serviceId);
            });
        },
    },
    computed: {
        monthDays() {
            return this.data || this.createMonthShell(this.date);
        },
    }
}
</script>
<style scoped>
.calendar-vertical {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}

.calendar-grid-wrapper {
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    overflow: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #fff;
}

.calendar-grid {
    width: 100%;
    table-layout: fixed;
    margin-bottom: 0;
}

.day-column {
    width: 12rem;
}

.city-column {
    width: auto;
}

.city-title {
    position: sticky;
    top: 0;
    z-index: 20;
    padding: 0.55rem 0.65rem;
    background-color: #f8f9fa;
    border-bottom-width: 1px;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.day-column-header {
    left: 0;
    z-index: 25;
}

.city-title-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 2rem;
}

.city-title-icon {
    color: #6c757d;
    font-size: 1rem;
}

.city-title-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.month-loading {
    min-height: 50vh;
}

:deep(th.day-header-cell) {
    position: sticky;
    left: 0;
    z-index: 15;
    background: #fff;
}

:deep(td.calendar-cell) {
    min-width: 0;
}

@media (max-width: 991.98px) {
    .day-column {
        width: 9.75rem;
    }

    .city-title {
        font-size: 0.72rem;
        padding: 0.45rem 0.35rem;
    }

    .city-title-content {
        flex-direction: column;
        gap: 0.15rem;
    }
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
