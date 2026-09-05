<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarr.tools/pfarrplaner
  - @version git: $Id$
  -->

<template>
    <admin-layout title="Excel-Tabelle der Gottesdienste erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Excel-Tabelle der Gottesdienste erstellen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'serviceExcelTable'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <city-location-filter
                :cities="cities"
                :locations="locations"
                city-label="Tabelle für folgende Kirchengemeinden erstellen"
                location-label="Auf folgende Orte beschränken"
                location-placeholder="Leer lassen für alle Orte"
                v-model:city-model-value="myCities"
                v-model:location-model-value="myLocations"
            />
            <form-date-range-picker
                label="Zeitraum"
                name-from="start"
                name-to="end"
                v-model:from="myStart"
                v-model:to="myEnd"
                iso-date
            />
            <form-selectize
                name="ministries[]"
                label="Folgende weiteren Dienste mit einschließen"
                :options="ministries"
                v-model="myMinistries"
                multiple
            />
            <form-check
                name="readable_headers"
                label="Lesbare Überschriften"
                v-model="myReadableHeaders"
            />
            <form-check
                name="services_only"
                label="Nur Gottesdienste"
                v-model="myServicesOnly"
            />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormCheck from "../../../components/Ui/forms/FormCheck";
import FormDateRangePicker from "../../../components/Ui/forms/FormDateRangePicker.vue";
import CityLocationFilter from "../../../components/Reports/CityLocationFilter.vue";
import { submitReportForm } from "../../../helpers/submitReportForm";

export default {
    name: "Setup",
    props: ['cities', 'locations', 'ministries', 'savedMinistries', 'readableHeaders', 'servicesOnly', 'start', 'end'],
    components: {
        CityLocationFilter,
        FormCheck,
        FormCsrfToken,
        FormDateRangePicker,
        FormSelectize,
        SaveButton,
    },
    data() {
        return {
            myMinistries: this.savedMinistries || [],
            myCities: this.cities.length > 0 ? [this.cities[0].id] : [],
            myLocations: [],
            myReadableHeaders: this.readableHeaders || 0,
            myServicesOnly: this.servicesOnly ?? 1,
            myStart: this.start,
            myEnd: this.end,
        };
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'serviceExcelTable'}), {
                cities: this.myCities,
                locations: this.myLocations,
                start: this.myStart,
                end: this.myEnd,
                ministries: this.myMinistries,
                readable_headers: this.myReadableHeaders,
                services_only: this.myServicesOnly,
            });
        },
    }
}
</script>
