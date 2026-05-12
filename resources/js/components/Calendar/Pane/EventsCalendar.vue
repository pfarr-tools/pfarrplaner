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
    <div class="px-3" style="height: 90vh;">
        <div ref="calendarContainer" style="height: 90vh;"></div>
        <modal :title="selectedEvent.title" v-if="showModal"
               @cancel="closeModal"
               :allow-close="false">
            <div>
                {{ selectedEvent.raw.description }}
            </div>
            <div>
                <span class="mdi mdi-clock"></span> {{ eventDate(selectedEvent) }}
            </div>
            <div>
                <span class="mdi mdi-map-marker"></span> {{ selectedEvent.location }}
            </div>
            <template #additional-buttons>
                <button v-if="!selectedEvent.raw.isRecurring"
                        type="button" class="btn btn-secondary"
                        title="Veranstaltung bearbeiten"
                        @click="editSelectedEvent">Bearbeiten</button>
                <button v-if="selectedEvent.raw.event_class == 'service'"
                        type="button" class="btn btn-secondary"
                        title="Liturgie bearbeiten"
                        @click="$inertia.get(route('liturgy.editor', selectedEvent.raw.event_slug))">
                    <span class="mdi mdi-view-list"></span> Liturgie</button>
                <button v-if="selectedEvent.raw.event_class == 'service'"
                        type="button" class="btn btn-secondary"
                        title="Predigt bearbeiten"
                        @click="$inertia.get(route('service.sermon.editor', selectedEvent.raw.event_slug))">
                    <span class="mdi mdi-microphone"></span> Predigt</button>
                <button v-if="selectedEvent.raw.isRecurring"
                        type="button" class="btn btn-secondary"
                        title="Änderungen wirken sich auf alle Termine der Serie aus. Möglicherweise werden
                        fehlende Termine in der Serie neu erzeugt."
                        @click="editSelectedEvent">Serie bearbeiten</button>
                <button v-if="selectedEvent.raw.isRecurring"
                        type="button" class="btn btn-secondary"
                        title="Änderungen wirken sich nur auf diesen Termin aus. Der Termin wird von der Reihe der Wiederholungen
                               entkoppelt und als eigenständiger, nicht wiederholender Termin angelegt."
                        @click="editSelectedOccurence">Einzeltermin bearbeiten</button>
                <button v-if="selectedEvent.raw.isRecurring"
                        type="button" class="btn btn-danger"
                        title="Dieser Termin wird aus der Reihe der Wiederholungen gelöscht."
                        @click="deleteSelectedOccurence">Einzeltermin löschen</button>
            </template>
        </modal>
    </div>
</template>


<script>
import Calendar from '@toast-ui/calendar';
import '@toast-ui/calendar/dist/toastui-calendar.css';
import Modal from "../../Ui/modals/Modal.vue";
import NavButton from "../../Ui/buttons/NavButton.vue";

export default {
    name: "EventsCalendar",
    props: ['date', 'calendar'],
    components: {
        NavButton,
        Modal,
    },
    data() {
        return {
            calendarInstance: null,
            showModal: false,
            selectedEvent: null,
        }
    },
    mounted() {
        this.calendarInstance = new Calendar(this.$refs.calendarContainer, {
            defaultView: 'month',
            usageStatistics: false,
            useDetailPopup: false,
            month: {
                dayNames: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
                startDayOfWeek: 1,
            },
            template: {
                time(event) {
                    return '<span class="' + (event.isReadOnly ? 'readonly' : '') + '">' +
                        moment(event.start.toDate()).format('HH:mm') + ' ' + event.title + '</span>';
                }
            },
        });

        this.calendarInstance.setDate(new Date(this.date));

        this.calendarInstance.on('clickEvent', this.onClickEvent);

        this.$api().get(route('api.events.range', {
            calendar: this.calendar,
            start: this.calendarInstance.getDateRangeStart().toString(),
            end: this.calendarInstance.getDateRangeEnd().toString(),
        })).then(response => {
            for (let index in response.data.data) {
                if (response.data.data[index].isReadOnly) response.data.data[index].customStyle = {cursor: 'not-allowed'}
            }
            this.calendarInstance.createEvents(response.data.data);
        });
    },
    beforeUnmount() {
        if (this.calendarInstance) {
            this.calendarInstance.off('clickEvent', this.onClickEvent);
            this.calendarInstance.destroy();
        }
    },
    methods: {
        closeModal() {
            this.selectedEvent = null;
            this.showModal = false;
        },
        onClickEvent(e) {
            this.selectedEvent = e.event;
            this.showModal = true;
        },
        editSelectedEvent() {
            let event = this.selectedEvent;
            this.closeModal();
            this.$inertia.get(route('service.edit', {service: event.raw.event_slug}));
        },
        editSelectedOccurence() {
            let event = this.selectedEvent;
            this.closeModal();
            this.$inertia.get(route('occurence.edit', {occurence: event.raw.occurence_id}));
        },
        deleteSelectedOccurence() {
            let event = this.selectedEvent;
            this.closeModal();
            this.$inertia.delete(route('occurence.destroy', {occurence: event.raw.occurence_id}), {preserveState: false});
        },
        eventDate(event) {
            let myStart = moment(event.start.toDate()).locale('de');
            let myEnd = moment(event.end.toDate()).locale('de');
            let dt = myStart.format('LL');
            let sameDate = (myEnd.format('YYYYMMDD') == myStart.format('YYYYMMDD'));

            if (!event.isAllday) {
                dt += ', ' + myStart.format('HH:mm');
                if (!event.end) dt += ' Uhr';
            }
            if ((!event.end) || sameDate) return dt;

            dt += ' - ';
            if (!sameDate) {
                dt = myEnd.format('LL') + ', ';
            }
            if (!event.isAllday) dt += myEnd.format('HH:mm') + ' Uhr';
            return dt.trim();
        }
    }
}
</script>

<style scoped>
:deep(.toastui-calendar-weekday-event-dot) {
    display: none;
}

:deep(.readonly) {
    cursor: not-allowed !important;
}
</style>
