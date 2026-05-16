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
import draggable from 'vuedraggable'
import EventBus from "../../../plugins/EventBus";
import {CalendarNewSortOrderEvent} from "../../../events/CalendarNewSortOrderEvent";

export default {
    name: 'CalendarCitySelect',
    components: {
        draggable,
    },
    inject: ['settings'],
    props: {
        cities: {
            type: Array,
            default: () => [],
        },
    },
    computed: {
        visibleCount() {
            return this.cityItems.filter(city => city.visible).length;
        },
    },
    data() {
        const hiddenCities = Object.values(this.$page.props.currentUser.data.hiddenCities);
        return {
            cityItems: [
                ...Object.values(this.cities).map(city => ({...city, visible: true})),
                ...hiddenCities.map(city => ({...city, visible: false})),
            ],
            user: this.$page.props.currentUser.data,
        }
    },
    methods: {
        toggleCity(cityId) {
            const city = this.cityItems.find(city => city.id === cityId);
            if (!city) return;
            city.visible = !city.visible;
            this.persistState();
        },
        isVisible(cityId) {
            return !!this.cityItems.find(city => city.id === cityId)?.visible;
        },
        persistState() {
            const visibleCities = this.cityItems.filter(city => city.visible);
            EventBus.publish(new CalendarNewSortOrderEvent(visibleCities));

            const ids = visibleCities.map(city => city.id);
            axios.post(route('setting.set', {user: this.user.id, key: 'sorted_cities'}), {
                value: ids.join(',')
            });
        },
        toggleChildChurchDetails() {
            const nextValue = !this.settings.show_cc_details;
            this.settings.show_cc_details = nextValue;
            axios.post(route('setting.set', {user: this.user.id, key: 'show_cc_details'}), {
                value: nextValue,
            });
        },
    }
};
</script>

<template>
    <div class="calendar-city-select dropdown" data-bs-auto-close="outside">
        <button
            class="btn btn-sm btn-outline-secondary dropdown-toggle calendar-filter-trigger"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            title="Kirchengemeinden auswählen"
        >
            <span class="mdi mdi-church"></span>
            <span class="d-none d-md-inline">Kirchengemeinden</span>
            <span class="badge text-bg-secondary">{{ visibleCount }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0 calendar-filter-menu">
            <div class="calendar-filter-panel" @click.stop>
                <div class="calendar-filter-heading">Kirchengemeinden</div>
                <p class="calendar-filter-copy">
                    Ziehen Sie die Einträge in die gewünschte Reihenfolge. Ein Klick schaltet die Anzeige im Kalender ein oder aus.
                </p>
                <draggable
                    :list="cityItems"
                    item-key="id"
                    handle=".calendar-city-drag-handle"
                    class="calendar-city-list"
                    @change="persistState"
                >
                    <template #item="{ element: city }">
                        <button
                            type="button"
                            class="calendar-city-toggle"
                            :class="city.visible ? 'calendar-city-toggle-active' : 'calendar-city-toggle-inactive'"
                            @click="toggleCity(city.id)"
                        >
                            <span class="calendar-city-drag-handle mdi mdi-drag-vertical"></span>
                            <span class="calendar-city-toggle-icon mdi"
                                  :class="city.visible ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline'"></span>
                            <span class="calendar-city-toggle-label">{{ city.name }}</span>
                        </button>
                    </template>
                </draggable>

                <div class="calendar-filter-section">
                    <div class="calendar-filter-heading">Darstellung</div>
                    <button
                        type="button"
                        class="calendar-city-toggle"
                        :class="settings.show_cc_details ? 'calendar-city-toggle-active' : 'calendar-city-toggle-inactive'"
                        @click="toggleChildChurchDetails"
                    >
                        <span class="calendar-city-toggle-icon mdi"
                              :class="settings.show_cc_details ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline'"></span>
                        <span class="calendar-city-toggle-label">
                            Details zur Kinderkirche anzeigen
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-filter-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: calc(2.25rem + 2px);
}

.calendar-filter-menu {
    width: 25rem;
    max-width: min(25rem, calc(100vw - 1rem));
}

.calendar-filter-panel {
    max-height: min(34rem, calc(100vh - 5rem));
    overflow: auto;
    padding: 0.5rem;
}

.calendar-filter-heading {
    margin-bottom: 0.4rem;
    color: #6c757d;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.calendar-filter-copy {
    margin-bottom: 0.75rem;
    color: #6c757d;
    font-size: 0.875rem;
    line-height: 1.35;
}

.calendar-filter-section {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e9ecef;
}

.calendar-city-list:empty::after {
    content: "Keine Einträge";
    display: block;
    padding: 0.55rem 0.7rem;
    border: 1px dashed #ced4da;
    color: #6c757d;
    background: #f8f9fa;
}

.calendar-city-toggle {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    width: 100%;
    margin-bottom: 0.35rem;
    padding: 0.55rem 0.7rem;
    border: 1px solid transparent;
    border-radius: 0;
    text-align: left;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.calendar-city-toggle:last-child {
    margin-bottom: 0;
}

.calendar-city-toggle-active {
    border-color: var(--bs-primary);
    background: var(--bs-primary);
    color: #fff;
}

.calendar-city-toggle-inactive {
    border-color: #dee2e6;
    background: #f8f9fa;
    color: #212529;
}

.calendar-city-drag-handle {
    flex: 0 0 auto;
    cursor: move;
    opacity: 0.75;
}

.calendar-city-toggle-icon {
    flex: 0 0 auto;
    font-size: 1rem;
}

.calendar-city-toggle-label {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 0.92rem;
    font-weight: 600;
    line-height: 1.2;
}
</style>
