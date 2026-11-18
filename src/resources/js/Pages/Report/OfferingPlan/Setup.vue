<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -
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
    <admin-layout title="Opferplan ausgeben">
        <template v-slot:navbar-left>
            <save-button
                label="Erstellen"
                title="Opferplan ausgeben"
                @click="renderReport"
            />
        </template>
        <form
            method="post"
            :action="route('reports.render', { report: 'offeringPlan' })"
            @submit.prevent="renderReport"
        >
            <form-csrf-token />
            <city-location-filter
                :cities="cities"
                :locations="locations"
                city-label="Opferplan für folgende Kirchengemeinden erstellen"
                location-label="Auf folgende Orte beschränken"
                location-placeholder="Leer lassen für alle Orte"
                v-model:city-model-value="myCities"
                v-model:location-model-value="myLocations"
            />
            <form-date-range-picker
                label="Zeitraum"
                v-model:from="myStart"
                v-model:to="myEnd"
                iso-date
            />
            <br />
            <form-check
                name="includeOfferingCounters"
                v-model="myIncludeOfferingCounters"
                label="Opferzähler mit ausgeben"
            />
            <form-check
                name="highlightEmpty"
                v-model="myHighlightEmpty"
                label="Fehlende Einträge hervorheben"
            />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from '../../../components/Ui/buttons/SaveButton'
import FormCsrfToken from '../../../components/Ui/forms/FormCsrfToken'
import FormDateRangePicker from '../../../components/Ui/forms/FormDateRangePicker'
import FormCheck from '../../../components/Ui/forms/FormCheck.vue'
import CityLocationFilter from '../../../components/Reports/CityLocationFilter.vue'
import { submitReportForm } from '../../../helpers/submitReportForm'
export default {
    name: 'Setup',
    props: ['cities', 'locations'],
    components: { CityLocationFilter, FormCheck, FormDateRangePicker, FormCsrfToken, SaveButton },
    data() {
        const reportYear = moment().startOf('year')
        if (moment().month() >= 6) reportYear.add(1, 'year')

        return {
            myCities: this.cities.length > 0 ? [this.cities[0].id] : null,
            myLocations: [],
            myStart: reportYear.clone(),
            myEnd: reportYear.clone().endOf('year'),
            myIncludeOfferingCounters: false,
            myHighlightEmpty: false,
        }
    },
    methods: {
        /** Submit the offering plan with the selected date range and filters. */
        renderReport() {
            submitReportForm(route('reports.render', { report: 'offeringPlan' }), {
                cities: this.myCities,
                locations: this.myLocations,
                start: this.myStart,
                end: this.myEnd,
                includeOfferingCounters: this.myIncludeOfferingCounters,
                highlightEmpty: this.myHighlightEmpty,
            })
        },
    },
}
</script>

<style scoped></style>
