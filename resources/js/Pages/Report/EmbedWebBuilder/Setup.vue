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
        <div class="row">
            <div class="col-md-6">
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
                    <form-input name="adChannelCode" label="Nur aktiv auf folgendem Werbekanal beworbene Veranstaltungen anzeigen"
                                v-model="myForm.adChannelCode"/>
                    <location-select name="locations" label="Auf folgende Veranstaltungsorte begrenzen"
                                     placeholder="Leer lassen für alle Veranstaltungsorte"
                                     :locations="locations" v-model="myForm.locations" multiple use-input />
                    <tag-select name="tags" label="Auf folgende Stichworte begrenzen" return="id"
                                v-model="myForm.tags" :tags="tags"  />

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

                    <div v-if="selectedTemplate && (undefined !== selectedTemplate.fields)" :key="selectedTemplate.id">
                        <hr class="my-3"/>
                        <h3>Weitere Einstellungen</h3>
                        <div v-for="(field, fieldKey) in selectedTemplate.fields" :key="index">
                            <form-input v-if="['text', 'number', 'password'].includes(field.type)"
                                        :name="'options['+fieldKey+']'" :label="field.label"
                                        :type="field.type" v-model="myForm.options[fieldKey]"/>
                        </div>
                    </div>

            </div>
            <div class="col-md-6">
                <div v-if="myRenderedCode.url && myForm['cors-origin']" :key="myRenderedCode.url">
                    <form-textarea label="HTML-Code zum Kopieren" v-model="myRenderedCode.html" :rows="20"/>
                    <nav-button type="light" icon="mdi mdi-content-copy" force-icon
                                @click="copyToClipboard"
                                title="In die Zwischenablage kopieren">Kopieren
                    </nav-button>
                </div>
                <div v-else class="alert alert-warning">
                    Es wurde noch kein Code zum Kopieren erstellt.
                    <b v-if="!myForm['cors-origin']">Das Feld "Aufrufende Website" muss dazu zwingend ausgefüllt werden.</b>
                </div>
            </div>
        </div>

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
import TagSelect from "../../../components/Ui/elements/TagSelect.vue";
import FormTextarea from "../../../components/Ui/forms/FormTextarea.vue";
import NavButton from "../../../components/Ui/buttons/NavButton.vue";
import __ from 'lodash';


export default {
    name: "Setup",
    props: ['cities', 'locations', 'layouts', 'tags'],
    components: {
        NavButton, FormTextarea,
        TagSelect,
        LocationSelect, FormCheck, FormDatePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    computed: {
        selectedTemplate() {
            if (!this.myForm.template) return null;
            return this.myTemplates.filter(item => item.id === this.myForm.template)[0] || null;
        }
    },
    data() {
        let myStart = moment();
        let myEnd = moment().add(7, 'days');
        let myTemplates = [];
        let myLayouts = [];

        for (const layoutKey in this.layouts) {
            myLayouts.push({id: this.layouts[layoutKey].id, name: this.layouts[layoutKey].name});
            for (const templateKey in this.layouts[layoutKey].templates) {
                myTemplates.push({
                    id: this.layouts[layoutKey].id + "." + templateKey,
                    layout: this.layouts[layoutKey].id,
                    layoutName: this.layouts[layoutKey].name,
                    ...this.layouts[layoutKey].templates[templateKey],
                });
            }
        }

        return {
            myForm: {
                cities: this.cities.length ? [this.cities[0].id] : [],
                eventClass: '*',
                adChannelCode: '',
                locations: [],
                tags: [],
                maxDays: 8,
                limit: null,
                template: myTemplates.length ? myTemplates[0].id : null,
                'cors-origin': null,
                options: {},
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
            },
            myRenderedCode: {
                url: '',
                html: '',
                randomId: '',
            },
        }
    },
    watch: {
        myForm: {
            deep: true,
            handler: function(newVal) {
                if (this.myForm['cors-origin']) this.doRender(this);
            }
        },
        'myForm.cities': {
            handler: function(newVal) {
                this.setCORSUrl(newVal);
                this.$forceUpdate();
            }
        }
    },
    created() {
        this.setCORSUrl();
        if (this.myForm['cors-origin']) this.renderReport();
    },
    methods: {
        doRender: __.debounce(function (component) {
            component.renderReport();
        }, 1000),
        renderReport() {
            this.$api().post(route('reports.render', 'embedWebBuilder'), this.myForm)
                .then(response => {
                    this.myRenderedCode = response.data;
                });
        },
        setCORSUrl(newVal = null) {
            if (!this.myForm['cors-origin']) this.myForm['cors-origin'] = this.findFirstUrl();
            this.$forceUpdate();
        },
        findFirstUrl() {
            let found = '';
            this.cities.forEach(city => {
                //console.log(city.id, (Object.values(this.myForm.cities).map(item => String(item)).includes(String(city.id))), city.homepage);
                if ((Object.values(this.myForm.cities).map(item => String(item)).includes(String(city.id))) && (city.homepage)) {
                    if ('' === found) found = city.homepage;
                }
            });
            return found;
        },
        copyToClipboard() {
            const cb = navigator.clipboard;
            cb.writeText(this.myRenderedCode.html).then(result => {
            });
        }
    }
}
</script>

<style scoped>

</style>
