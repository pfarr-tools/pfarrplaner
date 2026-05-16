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
    <div class="calendar-settings-panel">
        <div class="calendar-settings-section">
            <div class="calendar-settings-heading">Angezeigte Gemeinden</div>
            <p class="calendar-settings-copy">
                Ziehen Sie Gemeinden zwischen den Listen, um Spalten ein- oder auszublenden und ihre Reihenfolge festzulegen.
            </p>
            <calendar-control-city-sort :cities="cities" />
        </div>

        <div class="calendar-settings-section">
            <div class="calendar-settings-heading">Darstellung</div>
            <button
                type="button"
                class="calendar-settings-toggle"
                :class="mySettings.show_cc_details ? 'calendar-settings-toggle-active' : 'calendar-settings-toggle-inactive'"
                @click="toggleChildChurchDetails"
            >
                <span class="calendar-settings-toggle-icon mdi"
                      :class="mySettings.show_cc_details ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline'"></span>
                <span class="calendar-settings-toggle-body">
                    <strong>Details zur Kinderkirche anzeigen</strong>
                    <small>Thema, Ort und Mitarbeitende direkt in der Gottesdienst-Kachel einblenden</small>
                </span>
            </button>
        </div>
    </div>
</template>

<script>
import CalendarControlCitySort from "../Control/CitySort.vue";

export default {
    name: 'CalendarNavControlSidebar',
    components: {
        CalendarControlCitySort,
    },
    props: ['date', 'cities'],
    inject: ['settings'],
    data() {
        return {
            user: this.$page.props.currentUser.data,
            mySettings: this.settings,
        }
    },
    methods: {
        setSetting(key, value) {
            this.$emit('setSetting', {key, value});
            axios.post(route('setting.set', {user: this.user.id, key: key}), {
                value: value,
            });
        },
        toggleChildChurchDetails() {
            const nextValue = !this.mySettings.show_cc_details;
            this.mySettings.show_cc_details = nextValue;
            this.setSetting('show_cc_details', nextValue);
        }
    }
}
</script>

<style scoped>
.calendar-settings-panel {
    max-height: min(28rem, calc(100vh - 8rem));
    overflow: auto;
    padding: 0.5rem;
}

.calendar-settings-section + .calendar-settings-section {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e9ecef;
}

.calendar-settings-heading {
    margin-bottom: 0.4rem;
    color: #6c757d;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.calendar-settings-copy {
    margin-bottom: 0.75rem;
    color: #6c757d;
    font-size: 0.875rem;
    line-height: 1.35;
}

.calendar-settings-toggle {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    width: 100%;
    padding: 0.65rem 0.75rem;
    border: 1px solid transparent;
    border-radius: 0;
    text-align: left;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.calendar-settings-toggle-active {
    border-color: #0d6efd;
    background: #0d6efd;
    color: #fff;
}

.calendar-settings-toggle-inactive {
    border-color: #dee2e6;
    background: #f8f9fa;
    color: #212529;
}

.calendar-settings-toggle-icon {
    flex: 0 0 auto;
    margin-top: 0.1rem;
    font-size: 1rem;
}

.calendar-settings-toggle-body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.calendar-settings-toggle-body strong {
    font-size: 0.92rem;
    line-height: 1.2;
}

.calendar-settings-toggle-body small {
    color: inherit;
    opacity: 0.85;
    line-height: 1.3;
}
</style>
