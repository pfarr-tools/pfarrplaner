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
    <div class="button-row no-print btn-toolbar" role="toolbar">
        <div class="btn-group me-2" role="group">
            <button class="btn btn-default"
                    v-if="numericDate > 201801"
                    @click.prevent.stop="navigate(moment(date).subtract(1, 'months').format('YYYY-MM'))"
                    title="Einen Monat zurück">
                <span class="mdi mdi-chevron-left"></span>
            </button>
            <button class="btn btn-default" @click.prevent.stop="today">
                <span class="mdi mdi-calendar-today"></span><span class="d-none d-md-inline"> Gehe zu Heute </span>
            </button>

            <!-- TODO month / year dropdown -->
            <div class="btn-group" role="group">
                <button id="btnGroupDrop1" type="button" class="btn btn-default dropdown-toggle"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{ moment(date).locale('de-DE').format('MMMM') }}
                </button>
                <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-01')">Januar</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-02')">Februar</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-03')">März</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-04')">April</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-05')">Mai</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-06')">Juni</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-07')">Juli</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-08')">August</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-09')">September</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-10')">Oktober</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-11')">November</a>
                    <a class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(moment(date).format('YYYY')+'-12')">Dezember</a>
                </div>
            </div>
            <div class="btn-group" role="group">
                <button id="btnGroupDrop2" type="button" class="btn btn-default dropdown-toggle"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{ moment(date).format('YYYY') }}
                </button>
                <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                    <a v-for="year in years" class="dropdown-item" href="#"
                       @click.prevent.stop="navigate(year+'-'+moment(date).format('MM'))"
                       :key="year">{{ year }}
                    </a>
                </div>
            </div>
            <button class="btn btn-default"
                    v-if="numericDate > 201801"
                    @click.prevent.stop="navigate(moment(date).add(1, 'months').format('YYYY-MM'))"
                    title="Einen Monat weiter">
                <span class="mdi mdi-chevron-right"></span>
            </button>
        </div>

        <div class="btn-group" role="group" aria-label="Ansicht umschalten">
            <input type="radio" class="btn-check" name="calendarMode" id="calendarModeServices" autocomplete="off"
                   v-model="calendarMode"
                   value="services" @input="$emit('toggle-calendar-mode', 'services')"
                   title="Nur Gottesdienste anzeigen"/>
            <label class="btn btn-light" for="calendarModeServices"><span class="mdi mdi-church"></span></label>

            <input type="radio" class="btn-check" name="calendarMode" id="calendarModeEvents" autocomplete="off"
                   v-model="calendarMode"
                   value="events" @input="$emit('toggle-calendar-mode', 'events')"/>
            <label class="btn btn-light" for="calendarModeEvents"><span class="mdi mdi-calendar"></span></label>
        </div>

        <create-service-wizard-button v-if="canCreate" type="success"
                                      :cities="writableCities" class="ms-2 me-2" :date="date"
                                      :events="!(calendarMode == 'services')" :title="(calendarMode == 'services') ? 'Gottesdienst anlegen' : 'Veranstaltung anlegen'"
                                      :key="moment(date).toISOString()+calendarMode"/>

        <nav-button v-if="(calendarMode == 'services')"
                    class="me-2"
                    :type="targetMode ? 'warning' : 'default'"
                    :icon="targetMode ? (target.exclusive ? 'mdi mdi-account-convert-outline': 'mdi mdi-account-arrow-down-outline') : 'mdi mdi-target-account'"
                    :force-no-text="!targetMode"
                    force-icon
                    :title="'Benutzer schnell zuordnen'+(targetMode ? ' ('+targetTitle()+(target.exclusive ? ', überschreiben' : '')+')' : '')"
                    @click="$emit('toggle-target-mode', !targetMode)">
            {{ targetTitle() }}
        </nav-button>

        <a v-if="(calendarMode == 'services')" class="btn btn-default"
           :href="route('reports.setup', {report: 'ministryRequest'})"
           title="Dienstanfrage per E-Mail senden"><span class="mdi mdi-email"></span> <span class="d-none d-md-inline">Anfrage senden...</span></a>
        <calendar-select v-if="(calendarMode == 'events')" :calendars="calendars" v-model="mySelectedCalendar"
                         @input="$emit('calendar-select', $event)"/>


    </div>

</template>

<script>
import EventBus from "../../../plugins/EventBus";
import {CalendarToggleDayColumnEvent} from "../../../events/CalendarToggleDayColumnEvent";
import NavButton from "../../Ui/buttons/NavButton";
import CreateServiceWizardButton from "../../Ui/wizards/CreateServiceWizardButton.vue";
import CalendarSelect from "./CalendarSelect.vue";

export default {
    name: 'CalendarNavTop',
    components: {CalendarSelect, CreateServiceWizardButton, NavButton},
    data() {
        return {
            slave: false,
            allColumnsOpen: false,
            numericDate: parseInt(moment(this.date).format('YYYYMM')),
            mySelectedCalendar: this.selectedCalendar
        }
    },
    props: {
        date: Date,
        writableCities: Array,
        years: Array,
        orientation: String,
        targetMode: Boolean,
        target: Object,
        canCreate: Boolean,
        calendarMode: String,
        calendars: Array,
        selectedCalendar: Array,
    },
    methods: {
        monthLink: function (month) {
            return route('calendar', {
                date: this.date.getFullYear() + '-' + month
            });
        },
        yearLink: function (year) {
            return route('calendar', {
                date: year + '-' + (this.date.getUTCMonth() + 1)
            });
        },
        today() {
            if (moment(this.date).format('YYYYMM') == moment().format('YYYYMM')) {
                var el = document.getElementsByClassName('scroll-to-me');
                if (el) {
                    el[0].parentElement.scrollIntoView();
                    window.scroll(0, window.scrollY - 84);
                }
            } else {
                this.navigate(moment().format('YYYY-MM'));
            }
        },
        targetTitle() {
            if (!this.targetMode) return '';
            let people = [];
            this.target.people.forEach(person => people.push(person.name));
            return this.target.ministry + ': ' + people.join(', ');
        },
        createNewEvent() {
            this.$inertia.get(route('event.create', {
                filter: this.mySelectedCalendar,
                date: moment(this.date).format('YYYY-MM'),
            }))
        },
        navigate(targetDate) {
            this.$inertia.get(route('calendar', {date: targetDate}));
        }
    }
}
</script>

<style scoped>

</style>
