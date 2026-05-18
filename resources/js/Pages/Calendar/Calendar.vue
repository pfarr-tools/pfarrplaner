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
    <admin-layout :enable-control-sidebar="false" :title="pageTitle" no-padding no-content-header no-outer-padding :key="calendarState">
        <template #navbar-left>
            <calendar-nav-top :date="new Date(myDate)" :years="years"
                              :targetMode="targetMode" :target="target" :calendar-mode="calendarMode"
                              :writable-cities="writableCities" :can-create="canCreate"
                              @toggle-target-mode="toggleTargetMode"
                              @navigate="navigateTo"
            />
        </template>
        <template #navbar-right>
            <div class="calendar-topbar-actions">
                <div class="btn-group calendar-mode-toggle" role="group" aria-label="Kalenderansicht umschalten">
                    <input type="radio" class="btn-check" name="calendarModeTopbar" id="calendarModeTopbarServices" autocomplete="off"
                           :checked="calendarMode === 'services'"
                           value="services" @input="toggleCalendarMode('services')"/>
                    <label class="btn btn-sm btn-outline-secondary calendar-topbar-button" for="calendarModeTopbarServices" title="Gottesdienstkalender anzeigen">
                        <span class="mdi mdi-church"></span>
                        <span class="d-none d-xl-inline">Gottesdienste</span>
                    </label>

                    <input type="radio" class="btn-check" name="calendarModeTopbar" id="calendarModeTopbarEvents" autocomplete="off"
                           :checked="calendarMode === 'events'"
                           value="events" @input="toggleCalendarMode('events')"/>
                    <label class="btn btn-sm btn-outline-secondary calendar-topbar-button" for="calendarModeTopbarEvents" title="Veranstaltungskalender anzeigen">
                        <span class="mdi mdi-calendar"></span>
                        <span class="d-none d-xl-inline">Veranstaltungen</span>
                    </label>
                </div>

                <calendar-city-select v-if="calendarMode == 'services'" :cities="cities"/>
                <calendar-select v-if="calendarMode == 'events'"
                                 :calendars="calendars"
                                 :model-value="selectedCalendar"
                                 @update:modelValue="selectCalendar"/>
            </div>
        </template>
        <div v-if="calendarMode == 'services'" class="calendar-mode-container">
            <div class="calendar-full-container">
                <calendar-pane-vertical :date="myDate" :cities="cityList"
                                        :initial-data="calendarData"
                                        :key="calendarState"
                                        :targetMode="targetMode" :target="target"
                                        :can-create="canCreate "/>
            </div>
        </div>
        <div v-if="calendarMode == 'events'" class="calendar-mode-container">
            <div class="calendar-full-container">
                <events-calendar :date="myDate" :calendar="selectedCalendar" :writable-cities="writableCities" :key="selectedCalendar.join(',')+myDate"/>
            </div>
        </div>
        <modal v-if="showTargetModeModal" title="Person(en) schnell eintragen"
               @close="setTarget" :key="peopleLoaded"
               close-button-label="Aktivieren"
               @cancel="showTargetModeModal = false; targetMode = false;">
            <div v-if="!peopleLoaded" class="text-small text-muted"><span class="mdi mdi-spin mdi-loading"></span>
                Personenliste wird geladen...
            </div>
            <div v-if="peopleLoaded" :key="myPeople.length">
                <people-select :people="myPeople" :teams="myTeams" v-model="target.people"
                               label="Folgende Person(en) eintragen" :key="myPeople.length"/>
            </div>
            <div v-if="!ministriesLoaded" class="text-small text-muted"><span class="mdi mdi-spin mdi-loading"></span>
                Dienste werden geladen...
            </div>
            <div v-if="ministriesLoaded" :key="myMinistries.length">
                <form-selectize label="Für folgenden Dienst eintragen" v-model="target.ministry" :options="myMinistries"
                                :key="myMinistries.length"/>
            </div>
            <form-check label="Bestehende Einträge überschreiben" v-model="target.exclusive"/>
        </modal>
    </admin-layout>
</template>

<script>
import dayjs from 'dayjs';
import EventBus from "../../plugins/EventBus";
import {CalendarNewSortOrderEvent} from "../../events/CalendarNewSortOrderEvent";
import CalendarPaneHorizontal from '../../components/Calendar/Pane/Horizontal.vue';
import CalendarPaneVertical from '../../components/Calendar/Pane/Vertical.vue';
import CalendarPaneMobile from "../../components/Calendar/Pane/Mobile";
import Modal from "../../components/Ui/modals/Modal";
import PeopleSelect from "../../components/Ui/elements/PeopleSelect";
import FormSelectize from "../../components/Ui/forms/FormSelectize";
import FormCheck from "../../components/Ui/forms/FormCheck";
import CalendarNavTop from "../../components/Calendar/Nav/Top.vue";
import CalendarCitySelect from "../../components/Calendar/Nav/CitySelect.vue";
import CalendarSelect from "../../components/Calendar/Nav/CalendarSelect.vue";
import EventsCalendar from "../../components/Calendar/Pane/EventsCalendar.vue";

export default {
    components: {
        CalendarCitySelect,
        CalendarSelect,
        EventsCalendar,
        CalendarNavTop,
        FormCheck,
        FormSelectize,
        PeopleSelect,
        Modal,
        CalendarPaneMobile,
        CalendarPaneHorizontal,
        CalendarPaneVertical
    },
    props: ['date', 'cities', 'years', 'canCreate', 'ministries', 'writableCities', 'calendars', 'initialCalendarData'],
    provide() {
        return {
            settings: this.$page.props.settings || {},
        }
    },
    data() {
        return {
            calendarState: Math.random().toString(36).substr(2, 9),
            myDate: this.date,
            cityList: this.cities,
            settings: this.$page.props.settings || {},
            calendarMode: this.$page.props.settings.calendar_mode || 'services',
            targetMode: false,
            showTargetModeModal: false,
            peopleLoaded: 0,
            myPeople: [],
            myTeams: [],
            ministriesLoaded: false,
            myMinistries: [],
            target: {
                people: [],
                ministry: this.$page.props.currentUser.data.isPastor ? 'P' : null,
                exclusive: false,
            },
            calendarData: this.initialCalendarData,
            selectedCalendar: this.normalizeSelectedCalendar(this.$page.props.settings.calendar_select || this.calendars[0]?.id || null),
        }
    },
    created() {
        if (undefined === this.settings.show_cc_details) this.settings.show_cc_details = 0;
        this.navigateTo(dayjs(this.myDate).format('YYYY-MM'));
    },
    mounted() {
        EventBus.listen(CalendarNewSortOrderEvent, this.sortHandler);
    },
    methods: {
        sortHandler(e) {
            this.cityList = e.list;
        },
        toggleTargetMode(e) {
            if (!e) {
                this.targetMode = false;
                return;
            }
            this.showTargetModeModal = true;
            this.ensureTargetModeDataLoaded();
        },
        toggleCalendarMode(e) {
            this.calendarMode = e || 'services';
            this.setUserSetting('calendar_mode', this.calendarMode);
        },
        setTarget() {
            this.showTargetModeModal = false;
            this.targetMode = true;
        },
        navigateTo(targetDate) {
            this.myDate = targetDate;
        },
        normalizeSelectedCalendar(value) {
            if (Array.isArray(value)) return value;
            if (value === null || value === undefined || value === '') return [];
            return [value];
        },
        selectCalendar(e) {
            this.selectedCalendar = this.normalizeSelectedCalendar(e);
            this.setUserSetting('calendar_select', this.selectedCalendar);
        },
        ensureTargetModeDataLoaded() {
            if (!this.peopleLoaded) {
                this.$api().get(route('api.people.select')).then(response => {
                    this.myPeople = response.data.users;
                    this.myTeams = response.data.teams;
                    this.target.people = this.myPeople.filter(person => person.id == this.$page.props.currentUser.data.id)
                    this.peopleLoaded = 1;
                });
            }

            if (!this.ministriesLoaded) {
                this.$api().get(route('api.ministries.list')).then(response => {
                    this.myMinistries = [
                        {id: 'P', 'name': this.$page.props.labels.pastor},
                        {id: 'O', 'name': this.$page.props.labels.organist},
                        {id: 'M', 'name': this.$page.props.labels.sacristan},
                    ];
                    for (const ministryKey in response.data) {
                        this.myMinistries.push({
                            id: response.data[ministryKey].category,
                            name: response.data[ministryKey].category
                        });
                    }
                    this.ministriesLoaded = true;
                });
            }
        }
    },
    computed: {
        pageTitle() {
            return dayjs(this.myDate).format('MMMM YYYY');
        },
    }
}
</script>
<style scoped>
th, td {
    vertical-align: top;
}

.calendar-full-container {
    display: flex;
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    width: 100%;
    padding: 0;
    font-size: 0.875rem;
}

.calendar-mode-container {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
}

.calendar-topbar-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.calendar-topbar-button {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-height: calc(2.25rem + 2px);
}

.calendar-mode-toggle .mdi {
    line-height: 1;
}

.calendar-mode-toggle .btn-check:checked + .btn {
    color: var(--bs-primary);
    background-color: var(--bs-primary-bg-subtle);
    border-color: var(--bs-primary-border-subtle);
}

@media (max-width: 991.98px) {
    .calendar-full-container {
        font-size: 0.8125rem;
    }

    .calendar-topbar-actions {
        gap: 0.25rem;
    }
}

</style>
