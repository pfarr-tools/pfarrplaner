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
    <admin-layout title="HTML-Code für Veranstaltungswerbung erstellen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="HTML-Code für Veranstaltungswerbung erstellen"
                         @click="renderReport"/>
        </template>
        <h3>Veranstaltungen</h3>
        <form-selectize name="cities" label="Kirchengemeinden" required aria-required="true"
                        v-model="myForm.cities" :options="cities" multiple/>
        <form-selectize name="eventClass" label="Veranstaltungarten" :options="[
            { id: '*', name: 'Alle Veranstaltungen' },
            { id: 'service', name: 'Nur Gottesdienste' },
            { id: 'baptismalService', name: 'Nur Taufgottesdienste' },
            { id: 'eucharist', name: 'Nur Abendmahlsgottesdienste' },
            { id: 'baptism', name: 'Nur Gottesdienste mit Taufen' },
            { id: 'wedding', name: 'Nur Trauungen' },
            { id: 'funerals', name: 'Nur Beerdigungen' },
            { id: 'event', name: 'Nur andere Veranstaltungen' },
        ]" v-model="myForm.eventClass"/>
        <location-select name="locations" label="Auf folgende Veranstaltungsorte begrenzen"
                         placeholder="Leer lassen für alle Veranstaltungsorte"
                         :locations="locations" v-model="myForm.locations" multiple use-input />
        <form-selectize name="tags" label="Auf folgende Stichworte begrenzen" required aria-required="true"
                        v-model="myForm.tags" :options="tags" id-key="code" multiple/>

        <hr class="my-3"/>
        <h3>Weitere Filter</h3>
        <div class="row">
            <div class="col-md-6">
                <form-input name="maxDays" label="Anzahl der angezeigten Tage" type="number"
                            placeholder="Leer lassen für unbegrenzte Anzahl"
                            v-model="myForm.maxDays"/>
            </div>
            <div class="col-md-6">
                <form-input name="limit" label="Maximale Anzahl der angezeigten Veranstaltungen" type="number"
                            placeholder="Leer lassen für unbegrenzte Anzahl"
                            v-model="myForm.limit"/>
            </div>
        </div>

        <hr class="my-3"/>
        <h3>Ausgabe</h3>
        <form-selectize name="template" label="Vorlage" required aria-required="true"
                        v-model="myForm.template" :settings="myTemplateSelectSettings" />
        <form-input name="cors-origin" label="Aufrufende Website" required aria-required="true"
                    placeholder="z.B. https://www.tailfingen-evangelisch.de"
                    v-model="myForm['cors-origin']"/>

        <hr class="my-3"/>
        <h3>Weitere Einstellungen</h3>
        <form-input name="maxBaptisms" type="number" label="Maximale Anzahl Taufen pro Gottesdienst"
                    v-model="myForm.maxBaptisms"/>


    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker";
import FormCheck from "../../../components/Ui/forms/FormCheck";
import LocationSelect from "../../../components/Ui/elements/LocationSelect.vue";

export default {
    name: "Setup",
    props: ['cities', 'locations', 'layouts', 'tags'],
    components: {LocationSelect, FormCheck, FormDatePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        let myStart = moment();
        let myEnd = moment().add(7, 'days');
        let myTemplates = [];
        let myLayouts = [];

        console.log('layouts', this.layouts);

        for (const layoutKey in this.layouts) {
            myLayouts.push({id: this.layouts[layoutKey].id, name: this.layouts[layoutKey].name});
            for (const templateKey in this.layouts[layoutKey].templates) {
                myTemplates.push({
                    id: this.layouts[layoutKey].id + "." + templateKey,
                    name: this.layouts[layoutKey].templates[templateKey],
                    layout: this.layouts[layoutKey].id,
                    layoutName: this.layouts[layoutKey].name,
                });
            }
        }

        return {
            myForm: {
                cities: this.cities.length ? [this.cities[0].id] : [],
                eventClass: '*',
                locations: [],
                tags: [],
                maxDays: 8,
                limit: null,
                maxBaptisms: 3,
                template: myTemplates.length ? myTemplates[0].id : null,
                'cors-origin': null,
            },
            myTemplates,
            myTemplateSelectSettings: {
                options: myTemplates,
                searchField: ['name', 'layoutName'],
                valueField: 'id',
                labelField: 'name',
                optgroupField: 'layout',
                optgroupLabelField: 'name',
                optgroupValueField: 'id',
                optgroups: myLayouts,
            }
        }
    },
    methods: {
        renderReport() {
            this.$inertia.post(route('reports.render', 'embedWebBuilder'), this.myForm);
        },
    }
}
</script>

<style scoped>

</style>
