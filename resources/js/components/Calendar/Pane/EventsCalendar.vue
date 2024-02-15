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
    <div class="px-3 height: 90vh;">
        <ToastUICalendar ref="calendar"
                         style="height: 90vh;"
                         :usage-statistics="false"
                         view="month"
                         :month="monthOptions"
        />
    </div>
</template>


<script>
import ToastUICalendar from '@toast-ui/vue-calendar';
import '@toast-ui/calendar/dist/toastui-calendar.css';

export default {
    name: "EventsCalendar",
    props: ['date', 'calendars'],
    computed: {
        calendarInstance() {
            return this.$refs.calendar.getInstance();
        },
    },
    components: {
        ToastUICalendar,
    },
    data() {
        return {
            monthOptions: {
                dayNames: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
                startDayOfWeek: 1,
            }
        }
    },
    mounted() {
        this.calendarInstance.setCalendars(this.calendars);
        this.calendarInstance.setDate(new Date(this.date));
        this.$api().get(route('api.events.range', {
            start: this.calendarInstance.getDateRangeStart().toString(),
            end: this.calendarInstance.getDateRangeEnd().toString(),
            calendars: '1,3',
        })).then(response => {
            this.calendarInstance.createEvents(response.data.data);
        })
    },
    methods: {
        loadEvents() {

        }
    }
}
</script>

<style scoped>

</style>
