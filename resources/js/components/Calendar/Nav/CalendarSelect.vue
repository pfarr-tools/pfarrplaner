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

<script>
export default {
    name: 'CalendarSelect',
    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },
        calendars: {
            type: Array,
            default: () => [],
        },
    },
    emits: ['update:modelValue'],
    computed: {
        normalizedValue() {
            return Array.isArray(this.modelValue) ? this.modelValue : [];
        },
        selectedCount() {
            return this.normalizedValue.length;
        },
        groupedCalendars() {
            const groups = {};
            this.calendars.filter(Boolean).forEach((item) => {
                const groupName = item.group || 'Kalender';
                if (!groups[groupName]) groups[groupName] = [];
                groups[groupName].push(item);
            });

            return Object.entries(groups).map(([name, items]) => ({
                name,
                items,
            }));
        },
    },
    methods: {
        isSelected(calendarId) {
            return this.normalizedValue.includes(calendarId);
        },
        toggleCalendar(calendarId) {
            const current = [...this.normalizedValue];
            const index = current.indexOf(calendarId);

            if (index === -1) {
                current.push(calendarId);
            } else {
                current.splice(index, 1);
            }

            this.$emit('update:modelValue', current);
        },
    },
};
</script>

<template>
    <div class="calendar-select dropdown" data-bs-auto-close="outside">
        <button
            class="btn btn-outline-secondary dropdown-toggle calendar-select-trigger"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            title="Kalenderfilter öffnen"
        >
            <span class="mdi mdi-calendar-multiple"></span>
            <span class="d-none d-md-inline">Kalender</span>
            <span class="badge text-bg-secondary">{{ selectedCount }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0 calendar-select-menu">
            <div class="calendar-select-panel" @click.stop>
                <div v-for="group in groupedCalendars" :key="group.name" class="calendar-group">
                    <div class="calendar-group-heading">{{ group.name }}</div>
                    <button
                        v-for="calendar in group.items"
                        :key="calendar.id"
                        type="button"
                        class="calendar-toggle"
                        :class="isSelected(calendar.id) ? 'calendar-toggle-active' : 'calendar-toggle-inactive'"
                        @click="toggleCalendar(calendar.id)"
                    >
                        <span class="calendar-toggle-icon mdi" :class="isSelected(calendar.id) ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline'"></span>
                        <span class="calendar-toggle-label">{{ calendar.name }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-select-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.calendar-select-menu {
    width: 24rem;
    max-width: min(24rem, calc(100vw - 1rem));
}

.calendar-select-panel {
    max-height: min(28rem, calc(100vh - 8rem));
    overflow: auto;
    padding: 0.5rem;
}

.calendar-group + .calendar-group {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e9ecef;
}

.calendar-group-heading {
    margin-bottom: 0.4rem;
    color: #6c757d;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.calendar-toggle {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    width: 100%;
    margin-bottom: 0.35rem;
    padding: 0.55rem 0.7rem;
    border: 1px solid transparent;
    border-radius: 0.5rem;
    text-align: left;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.calendar-toggle:last-child {
    margin-bottom: 0;
}

.calendar-toggle-active {
    border-color: #0d6efd;
    background: #0d6efd;
    color: #fff;
}

.calendar-toggle-inactive {
    border-color: #dee2e6;
    background: #f8f9fa;
    color: #212529;
}

.calendar-toggle-icon {
    flex: 0 0 auto;
    font-size: 1rem;
}

.calendar-toggle-label {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 0.92rem;
    font-weight: 600;
    line-height: 1.2;
}
</style>
