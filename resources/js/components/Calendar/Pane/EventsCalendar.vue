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
    <div class="events-month">
        <div v-if="loading" class="events-grid-wrapper events-loading">
            <div class="events-grid-frame">
                <div class="events-weekdays">
                    <div class="week-header"></div>
                    <div v-for="weekday in weekdayLabels" :key="'loading_'+weekday" class="weekday-header">
                        {{ weekday }}
                    </div>
                </div>
                <div class="events-weeks">
                    <div v-for="week in 6" :key="'loading_week_'+week" class="events-week-row">
                        <div class="week-number-cell skeleton-week-number"></div>
                        <div v-for="day in 7" :key="'loading_day_'+week+'_'+day" class="day-cell">
                            <div class="day-shell">
                                <div class="skeleton-day-number"></div>
                                <div class="skeleton-event" v-for="eventIndex in 3" :key="'skeleton_event_'+week+'_'+day+'_'+eventIndex"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="events-grid-wrapper">
            <div class="events-grid-frame">
                <div class="events-weekdays">
                    <div class="week-header">KW</div>
                    <div v-for="weekday in weekdayLabels" :key="weekday" class="weekday-header">
                        {{ weekday }}
                    </div>
                </div>
                <div class="events-weeks">
                    <div v-for="week in weeks" :key="'week_'+week.weekNumber+'_'+week.days[0].key" class="events-week-row">
                        <div class="week-number-cell">
                            {{ week.weekNumber }}
                        </div>
                        <div
                            v-for="day in week.days"
                            :key="day.key"
                            class="day-cell"
                            :class="dayCellClasses(day)"
                            title="Klicken, um hier eine neue Veranstaltung anzulegen"
                            @click="openCreateModal(day.key)"
                        >
                            <div class="day-shell">
                                <div class="day-header">
                                    <span class="day-number">{{ day.label }}</span>
                                    <span v-if="isToday(day.date)" class="badge text-bg-primary">Heute</span>
                                </div>
                                <div class="day-events">
                                    <button
                                        v-for="event in visibleEvents(day.key)"
                                        :key="event.id+'_'+event.start"
                                        type="button"
                                        class="event-card"
                                        :class="{'event-readonly': event.isReadOnly}"
                                        :style="eventStyle(event)"
                                        :title="eventTooltip(event)"
                                        @click.stop
                                    >
                                        <span class="event-headline">
                                            <span class="event-time">{{ eventTimeLabel(event) }}</span>
                                            <span class="event-title">{{ event.title }}</span>
                                        </span>
                                        <span class="event-location">{{ eventLocationLabel(event) }}</span>
                                        <div class="event-overlay">
                                            <div class="event-overlay-buttons">
                                                <button
                                                    v-if="!event.raw.isRecurring"
                                                    type="button"
                                                    class="btn btn-primary btn-sm"
                                                    title="Veranstaltung bearbeiten"
                                                    @click.stop="editEvent(event)"
                                                >
                                                    <span class="mdi mdi-pencil"></span>
                                                </button>
                                                <button
                                                    v-if="event.raw.event_class == 'service'"
                                                    type="button"
                                                    class="btn btn-light btn-sm"
                                                    title="Liturgie bearbeiten"
                                                    @click.stop="$inertia.get(route('liturgy.editor', event.raw.event_slug))"
                                                >
                                                    <span class="mdi mdi-view-list"></span>
                                                </button>
                                                <button
                                                    v-if="event.raw.event_class == 'service'"
                                                    type="button"
                                                    class="btn btn-light btn-sm"
                                                    title="Predigt bearbeiten"
                                                    @click.stop="$inertia.get(route('service.sermon.editor', event.raw.event_slug))"
                                                >
                                                    <span class="mdi mdi-microphone"></span>
                                                </button>
                                                <button
                                                    v-if="event.raw.isRecurring"
                                                    type="button"
                                                    class="btn btn-primary btn-sm"
                                                    title="Serie bearbeiten"
                                                    @click.stop="editEvent(event)"
                                                >
                                                    <span class="mdi mdi-pencil"></span>
                                                </button>
                                                <button
                                                    v-if="event.raw.isRecurring"
                                                    type="button"
                                                    class="btn btn-light btn-sm"
                                                    title="Einzeltermin bearbeiten"
                                                    @click.stop="editOccurence(event)"
                                                >
                                                    <span class="mdi mdi-calendar-edit"></span>
                                                </button>
                                                <button
                                                    v-if="event.raw.isRecurring"
                                                    type="button"
                                                    class="btn btn-danger btn-sm"
                                                    title="Einzeltermin löschen"
                                                    @click.stop="deleteOccurence(event)"
                                                >
                                                    <span class="mdi mdi-delete"></span>
                                                </button>
                                                <button
                                                    v-if="!event.raw.isRecurring"
                                                    type="button"
                                                    class="btn btn-danger btn-sm"
                                                    title="Veranstaltung löschen"
                                                    @click.stop="deleteEvent(event)"
                                                >
                                                    <span class="mdi mdi-delete"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                <div v-if="visibleEvents(day.key).length === 0" class="day-create-hint">
                                    <span class="mdi mdi-plus-circle-outline"></span>
                                    Klicken zum Anlegen
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <modal
            v-if="createModalVisible"
            title="Veranstaltung anlegen"
            @cancel="closeCreateModal"
            :allow-close="false"
            max-width="26rem"
        >
            <div class="mb-3">
                {{ createModalDateLabel }}
            </div>
            <div class="create-city-buttons">
                <button
                    v-for="city in createCityOptions"
                    :key="city.id"
                    type="button"
                    class="btn btn-outline-primary text-start"
                    @click="createEventAtDay(city.id)"
                >
                    {{ city.name }}
                </button>
            </div>
        </modal>
    </div>
</template>

<script>
import dayjs from 'dayjs';
import isoWeek from 'dayjs/plugin/isoWeek';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import 'dayjs/locale/de';
import Modal from "../../Ui/modals/Modal.vue";

dayjs.extend(isoWeek);
dayjs.extend(localizedFormat);
dayjs.locale('de');

export default {
    name: "EventsCalendar",
    props: {
        date: String,
        calendar: {
            type: Array,
            default: () => [],
        },
        writableCities: {
            type: Array,
            default: () => [],
        },
    },
    components: {
        Modal,
    },
    data() {
        return {
            loading: false,
            events: [],
            createModalVisible: false,
            selectedCreateDay: null,
        }
    },
    mounted() {
        this.loadEvents();
    },
    watch: {
        date() {
            this.loadEvents();
        },
        calendar() {
            this.loadEvents();
        },
    },
    methods: {
        closeCreateModal() {
            this.createModalVisible = false;
            this.selectedCreateDay = null;
        },
        openCreateModal(dayKey) {
            if (!this.createCityOptions.length) return;
            this.selectedCreateDay = dayKey;
            this.createModalVisible = true;
        },
        loadEvents() {
            this.loading = true;
            this.events = [];

            if (!this.calendarFilter) {
                this.loading = false;
                return;
            }

            this.$api().get(route('api.events.range', {
                calendar: this.calendarFilter,
                start: this.visibleRange.start.format('YYYY-MM-DD HH:mm:ss'),
                end: this.visibleRange.end.format('YYYY-MM-DD HH:mm:ss'),
            })).then(response => {
                this.events = response.data.data || [];
                this.loading = false;
            }).catch(() => {
                this.loading = false;
            });
        },
        visibleEvents(dayKey) {
            return this.eventsByDay[dayKey] || [];
        },
        dayCellClasses(day) {
            return {
                'outside-month': !day.inMonth,
                'today': this.isToday(day.date),
                'has-events': (this.eventsByDay[day.key] || []).length > 0,
            };
        },
        isToday(date) {
            return date.isSame(dayjs(), 'day');
        },
        eventStyle(event) {
            const customStyle = event.customStyle || {};
            const backgroundColor = customStyle.backgroundColor || '#eff3f7';
            const borderColor = customStyle.borderColor || backgroundColor;
            const color = customStyle.color || '#212529';

            return {
                '--event-bg': backgroundColor,
                '--event-border': borderColor,
                '--event-color': color,
            };
        },
        eventTooltip(event) {
            const parts = [event.title];
            const timeLabel = this.eventTimeLabel(event);
            if (timeLabel) parts.push(timeLabel);
            if (event.location) parts.push(event.location);
            return parts.join(' | ');
        },
        eventTimeLabel(event) {
            if (event.isAllday) return 'Ganztägig';

            const start = dayjs(event.start).locale('de');
            if (!start.isValid()) return '';
            return start.format('HH:mm');
        },
        eventLocationLabel(event) {
            return event.location || '\u00A0';
        },
        editEvent(event) {
            this.$inertia.get(route('service.edit', {service: event.raw.event_slug}));
        },
        editOccurence(event) {
            this.$inertia.get(route('occurence.edit', {occurence: event.raw.occurence_id}));
        },
        deleteOccurence(event) {
            this.$inertia.delete(route('occurence.destroy', {occurence: event.raw.occurence_id}), {preserveState: false});
        },
        deleteEvent(event) {
            this.$inertia.delete(route('service.destroy', {service: event.raw.event_slug}), {preserveState: false});
        },
        createEventAtDay(cityId) {
            if (!cityId || !this.selectedCreateDay) return;
            this.createModalVisible = false;
            this.$inertia.get(route('event.create', {
                filter: 'city:' + cityId,
                date: this.selectedCreateDay,
            }));
        },
    },
    computed: {
        monthDate() {
            return dayjs(this.date + '-01').locale('de');
        },
        calendarFilter() {
            return this.calendar.filter(Boolean).join(',');
        },
        createCityOptions() {
            return this.writableCities
                .filter(city => !city.is_org)
                .map(city => ({ id: city.id, name: city.name }));
        },
        createModalDateLabel() {
            if (!this.selectedCreateDay) return '';
            return dayjs(this.selectedCreateDay).locale('de').format('dddd, DD. MMMM YYYY');
        },
        visibleRange() {
            return {
                start: this.monthDate.startOf('month').startOf('isoWeek'),
                end: this.monthDate.endOf('month').endOf('isoWeek'),
            };
        },
        weekdayLabels() {
            const firstDay = dayjs().startOf('isoWeek');
            return Array.from({length: 7}, (_, index) => firstDay.add(index, 'day').format('dd'));
        },
        weeks() {
            const days = [];
            let cursor = this.visibleRange.start;

            while (cursor.isBefore(this.visibleRange.end) || cursor.isSame(this.visibleRange.end, 'day')) {
                days.push({
                    key: cursor.format('YYYY-MM-DD'),
                    label: cursor.format('D'),
                    date: cursor,
                    inMonth: cursor.month() === this.monthDate.month(),
                });
                cursor = cursor.add(1, 'day');
            }

            const weeks = [];
            for (let i = 0; i < days.length; i += 7) {
                weeks.push({
                    weekNumber: days[i].date.isoWeek(),
                    days: days.slice(i, i + 7),
                });
            }
            return weeks;
        },
        eventsByDay() {
            return this.events.reduce((result, event) => {
                const dayKey = dayjs(event.start).format('YYYY-MM-DD');
                if (!result[dayKey]) result[dayKey] = [];
                result[dayKey].push(event);
                return result;
            }, {});
        },
    }
}
</script>

<style scoped>
.events-month {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    height: 100%;
    min-height: 0;
    padding: 0.5rem;
}

.events-grid-wrapper {
    flex: 1 1 auto;
    height: 100%;
    min-height: 0;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #fff;
    overflow: hidden;
}

.events-grid-frame {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    min-width: 70rem;
}

.events-weekdays {
    display: grid;
    grid-template-columns: 3.25rem repeat(7, minmax(0, 1fr));
    flex: 0 0 auto;
    border-bottom: 1px solid #dee2e6;
}

.events-weeks {
    display: grid;
    grid-template-rows: repeat(6, minmax(max-content, 1fr));
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    overflow: auto;
}

.events-week-row {
    display: grid;
    grid-template-columns: 3.25rem repeat(7, minmax(0, 1fr));
    align-items: stretch;
    min-height: 0;
}

.week-header,
.weekday-header {
    padding: 0.75rem 0.5rem;
    background: #f8f9fa;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #5c6b77;
}

.week-header {
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 1px solid #dee2e6;
}

.week-number-cell {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 0.55rem 0.15rem;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    color: #6c757d;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1;
}

.skeleton-week-number {
    background: #f8f9fa;
}

.day-cell {
    min-width: 0;
    min-height: 7rem;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    background: #fff;
    overflow: hidden;
    cursor: pointer;
}

.day-shell {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-height: 100%;
    width: 100%;
    min-width: 0;
    padding: 0.4rem;
    box-sizing: border-box;
}

.day-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    width: 100%;
    min-width: 0;
}

.day-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #f1f3f5;
    color: #495057;
    font-size: 0.78rem;
    font-weight: 700;
}

.day-events {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    width: 100%;
    min-width: 0;
}

.day-create-hint {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: auto;
    color: #adb5bd;
    font-size: 0.72rem;
    line-height: 1.1;
    opacity: 0.8;
    pointer-events: none;
}

.event-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.1rem;
    position: relative;
    width: 100%;
    max-width: 100%;
    min-height: 2.65rem;
    padding: 0.25rem 0.4rem;
    border: 1px solid var(--event-border);
    border-left-width: 4px;
    border-radius: 0.4rem;
    background: var(--event-bg);
    color: var(--event-color);
    text-align: left;
    box-shadow: 0 1px 1px rgb(15 23 42 / 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
}

.event-headline {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
    width: 100%;
    min-width: 0;
}

.event-card:hover,
.event-card:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgb(15 23 42 / 0.12);
}

.event-readonly {
    cursor: not-allowed;
}

.event-time {
    flex: 0 0 auto;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    opacity: 0.85;
}

.event-title {
    display: block;
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    font-size: 0.76rem;
    font-weight: 600;
    line-height: 1.2;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.event-location {
    display: block;
    overflow: hidden;
    width: 100%;
    min-width: 0;
    min-height: 0.8rem;
    font-size: 0.68rem;
    line-height: 1.15;
    opacity: 0.8;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.event-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem;
    background: rgb(33 37 41 / 0.55);
    opacity: 0;
    transition: opacity 0.15s ease;
}

.event-card:hover .event-overlay,
.event-card:focus-within .event-overlay {
    opacity: 1;
}

.event-overlay-buttons {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
}

.outside-month {
    background: #fbfcfd;
}

.outside-month .day-number {
    background: transparent;
    color: #9aa4af;
}

.today {
    background-image: linear-gradient(180deg, rgb(13 110 253 / 0.04), transparent 28%);
}

.today .day-number {
    background: #0d6efd;
    color: #fff;
}

.has-events {
    background-color: #fff;
}

.events-loading .day-cell {
    background: #fff;
}

.events-week-row > .day-cell:last-child {
    border-right: 0;
}

.create-city-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.create-city-buttons .btn {
    width: 100%;
}

.skeleton-day-number,
.skeleton-event {
    border-radius: 0.45rem;
    background: linear-gradient(90deg, #f3f5f7 25%, #e7ebef 37%, #f3f5f7 63%);
    background-size: 400% 100%;
    animation: skeleton-shimmer 1.4s ease infinite;
}

.skeleton-day-number {
    width: 2.5rem;
    height: 1.75rem;
}

.skeleton-event {
    width: 100%;
    height: 3rem;
}

@keyframes skeleton-shimmer {
    0% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0 50%;
    }
}

@media (max-width: 991.98px) {
    .events-month {
        padding: 0.25rem;
    }

    .events-weekdays,
    .events-grid-frame {
        min-width: 52rem;
    }

    .events-weekdays,
    .events-week-row {
        grid-template-columns: 2.6rem repeat(7, minmax(0, 1fr));
    }

    .day-cell {
        min-height: 6rem;
    }

    .day-shell {
        padding: 0.35rem;
    }

    .event-title,
    .event-location {
        font-size: 0.66rem;
    }
}
</style>
