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
    <admin-layout :title="getTitle()">
        <template #navbar-left>
            <save-button @click="saveCity" />
            <nav-button v-if="canDelete" class="ms-1" type="danger" icon="mdi mdi-delete" @click="deleteCity">Löschen</nav-button>
        </template>
        <template #tab-headers>
            <tab-headers>
                <tab-header id="home" title="Allgemeines" :active-tab="activeTab"/>
                <tab-header v-if="!myCity.is_org" id="offerings" title="Opfer" :active-tab="activeTab"/>
                <tab-header v-if="!myCity.is_org" id="parishes" title="Pfarrämter" :active-tab="activeTab"/>
                <tab-header v-if="!myCity.is_org" id="streaming" title="Streaming" :active-tab="activeTab"/>
                <tab-header v-if="!myCity.is_org" id="locations" title="Veranstaltungsorte" :active-tab="activeTab"/>
                <tab-header v-if="!myCity.is_org" id="integrations" title="Weitere Integrationen" :active-tab="activeTab"/>
                <tab-header v-if="!myCity.is_org" id="ads" title="Werbung" :active-tab="activeTab"/>
            </tab-headers>
        </template>
        <tabs>
            <tab id="home" :active-tab="activeTab">
                <form-input name="name" label="Name der Kirchengemeinde" v-model="myCity.name" autofocus/>
                <form-input name="offical_name" label="Offizielle Bezeichnung" v-model="myCity.official_name"/>
                <form-check class="mt-2" name="is_org" label="Diese Kirchengemeinde ist eine Sammelgemeinde für mehrere Einzelgemeinden"
                            v-model="myCity.is_org"/>
                <form-selectize v-if="myCity.is_org" name="childIds[]" label="Zugehörige Einzelgemeinden" v-model="myCity.childIds"
                                :options="Object.values(possibleChildren)" multiple />


                <form-input class="mt-2" name="homepage" label="Homepage der Kirchengemeinde" v-model="myCity.homepage"/>
                <form-image-attacher v-if="city.id"
                                     v-model="myCity.logo" label="Logo der Kirchengemeinde"
                                     :attach-route="route('city.attach', {city: myCity.id, field: 'logo'})"
                                     :detach-route="route('city.detach', {city: myCity.id, field: 'logo'})"/>
                <div v-else class="form-group">
                    <label>Logo der Kirchengemeinde</label>
                    Die Kirchengemeinde muss erst gespeichert werden, bevor ein Logo hochgeladen werden kann.
                </div>
                <form-selectize v-if="ministriesLoaded" multiple
                                name="default_ministries" label="Diese Dienste immer mit anzeigen" v-model="myCity.default_ministries"
                                :options="myMinistries" />
                <div v-else><span class="mdi mdi-spin mdi-loading"></span> Diensteliste wird geladen...</div>
            </tab>
            <tab v-if="!myCity.is_org" id="offerings" :active-tab="activeTab">
                <form-input name="default_offering_goal" label="Opferzweck, wenn nicht angegeben"
                            v-model="myCity.default_offering_goal"/>
                <form-input name="default_offering_description" label="Opferbeschreibung bei leerem Opferzweck"
                            v-model="myCity.default_offering_description"/>
                <form-input name="default_funeral_offering_goal" label="Opferzweck für Beerdigungen"
                            v-model="myCity.default_funeral_offering_goal"/>
                <form-input name="default_funeral_offering_description" label="Opferbeschreibung bei Beerdigungen"
                            v-model="myCity.default_funeral_offering_description"/>
                <form-input name="default_wedding_offering_goal" label="Opferzweck für Trauungen"
                            v-model="myCity.default_wedding_offering_goal"/>
                <form-input name="default_wedding_offering_description" label="Opferbeschreibung bei Trauungen"
                            v-model="myCity.default_wedding_offering_description"/>
                <form-input name="default_offering_url" label="Allgemeine Spendenseite"
                            v-model="myCity.default_offering_url"/>
                <form-input name="iban" label="Bankkonto (IBAN)"
                            v-model="myCity.iban"/>
                <form-input name="bic" label="Bankkonto (BIC)"
                            v-model="myCity.bic"/>
            </tab>
            <tab v-if="!myCity.is_org" id="streaming" :active-tab="activeTab">
                <form-input name="youtube_channel_url" label="URL für den YouTube-Kanal"
                            v-model="myCity.youtube_channel_url"/>
                <form-selectize name="youtube_active_stream_id" v-model="myCity.youtube_active_stream_id"
                                label="Streamschlüssel für die aktive Sendung" :options="streamOptions"/>
                <form-selectize name="youtube_passive_stream_id" v-model="myCity.youtube_passive_stream_id"
                                label="Streamschlüssel für inaktive Sendungen" :options="streamOptions"/>
                <form-check name="youtube_auto_startstop" v-model="myCity.youtube_auto_startstop"
                            label="Sendungen automatisch starten und stoppen "/>
                <form-check name="youtube_self_declared_for_children"
                            v-model="myCity.youtube_self_declared_for_children"
                            label="Sendungen als Kindersendungen markieren"/>
                <form-input type="number" name="youtube_cutoff_days" v-model="myCity.youtube_cutoff_days"
                            label="Aufzeichnungen auf Youtube nach __ Tagen automatisch auf privat schalten"/>
            </tab>
            <tab v-if="!myCity.is_org" id="locations" :active-tab="activeTab">
                <model-index-list :records="city.locations" :can-create="true" title="Veranstaltungsorte" label-by="name"
                                  create-label="Neuer Veranstaltungsort" :create-route="route('admin.locations.create', {city: city.id})"
                                  delete-route-name="admin.location.destroy" edit-route-name="admin.location.edit"
                                  model-label="Veranstaltungsort"/>
            </tab>
            <tab v-if="!myCity.is_org" id="parishes" :active-tab="activeTab">
                <model-index-list :records="city.parishes" :can-create="true" title="Pfarrämter" label-by="name"
                                  create-label="Neues Pfarramt" :create-route="route('admin.parishes.create', {city: city.id})"
                                  delete-route-name="admin.parish.destroy" edit-route-name="admin.parish.edit"
                                  model-label="Pfarramt"/>
            </tab>
            <tab v-if="!myCity.is_org" id="integrations" :active-tab="activeTab">
                <accordion id="integrationsAccordion2">
                    <accordion-element title="KonfiApp" image="/img/external/konfiapp.png">
                        <p>Die <a href="https://konfiapp.de" target="_blank">KonfiApp</a> von Philipp Dormann bietet
                            viele Möglichkeiten, mit Konfis in Kontakt zu bleiben.</p>
                        <h5>Der Pfarrplaner bietet aktuell folgende Integrationsmöglichkeiten:</h5>
                        <ul>
                            <li>Im Pfarrplaner angelegte Gottesdienste können einem Veranstaltungstyp in der
                                KonfiApp
                                zugewiesen werden. Beim Speichern wird dann automatisch ein passender QR-Code in der
                                KonfiApp angelegt.
                            </li>
                        </ul>
                        <p>Für die Integration der KonfiApp ist ein API-Schlüssel erforderlich. Dieser kann im
                            Verwaltungsbereich der KonfiApp über folgenden Link angelegt werden:
                            <a href="https://verwaltung.konfiapp.de/administration/api-tokens/" target="_blank">https://verwaltung.konfiapp.de/administration/api-tokens/</a>.
                            Der dort erstellte Schlüssel muss in das untenstehende Eingabefeld kopiert werden. In
                            der
                            anschließenden Übersicht in der KonfiApp können für den Schlüssel
                            sogenannte "Scopes" aktiviert werden. Folgende Scopes sind für das Funktionieren der
                            Integration erforderlich:</p>
                        <p>
                            <span class="badge bg-secondary">veranstaltungen.read</span>
                            <span class="badge bg-secondary">veranstaltungen.qr.read</span>
                            <span class="badge bg-secondary">veranstaltungen.qr.create</span>
                            <span class="badge bg-secondary">veranstaltungen.qr.delete</span>
                        </p>
                        <form-input name="konfiapp_apikey" label="API-Schlüssel für die KonfiApp"
                                    v-model="myCity.konfiapp_apikey"/>
                        <konfi-app-event-type-select v-if="myCity.konfiapp_apikey" name="konfiapp_event_type"
                                                     label="Veranstaltungsart in der KonfiApp"
                                                     :city="myCity" v-model="myCity.konfiapp_default_type"/>
                    </accordion-element>
                    <accordion-element title="CommuniApp" image="/img/external/communiapp.png">
                        <p>Die <a href="https://www.communiapp.de" target="_blank">CommuniApp</a> bietet viele
                            Möglichkeiten, als Gemeinde in Kontakt zu bleiben.</p>
                        <h5>Der Pfarrplaner bietet aktuell folgende Integrationsmöglichkeiten:</h5>
                        <ul>
                            <li>Im Pfarrplaner angelegte Gottesdienste können automatisch in der CommuniApp angelegt
                                werden. Bei dieser Integration können auch weitere Termine aus Outlook bzw. aus dem
                                OnlinePlaner verwendet werden.
                            </li>
                        </ul>
                        <p>Für die Integration der CommuniApp ist ein API-Schlüssel erforderlich. Dieser kann im
                            Verwaltungsbereich der CommuniApp unter Admin > Integrationen > Rest-Api angelegt werden.
                            Der dort erstellte Schlüssel muss in das untenstehende Eingabefeld kopiert werden. </p>
                        <form-textarea name="communiapp_token" v-model="myCity.communiapp_token"
                                       label="Zugangstoken für die CommuniApp"/>
                        <form-input name="communiapp_default_group_id" v-model="myCity.communiapp_default_group_id"
                                    label="Gruppen-ID der Hauptgruppe"/>
                        <form-input name="communiapp_url" v-model="myCity.communiapp_url" label="URL der App"/>
                    </accordion-element>
                </accordion>
            </tab>
            <tab v-if="!myCity.is_org" id="ads" :active-tab="activeTab">
                <model-index-list :records="city.ad_channels" :can-create="true" title="Werbekanäle"
                                  create-label="Neuer Werbekanal" :create-route="route('admin.adchannels.create', {city: city.id})"
                                  delete-route-name="admin.adchannel.destroy" edit-route-name="admin.adchannel.edit"
                    model-label="Werbekanal"/>
            </tab>
        </tabs>
    </admin-layout>
</template>

<script>
import Card from "../../../components/Ui/cards/card";
import CardHeader from "../../../components/Ui/cards/cardHeader";
import CardBody from "../../../components/Ui/cards/cardBody";
import TabHeaders from "../../../components/Ui/tabs/tabHeaders";
import TabHeader from "../../../components/Ui/tabs/tabHeader";
import Tabs from "../../../components/Ui/tabs/tabs";
import Tab from "../../../components/Ui/tabs/tab";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCheck from "../../../components/Ui/forms/FormCheck";
import FormImageAttacher from "../../../components/Ui/forms/FormImageAttacher";
import FormTextarea from "../../../components/Ui/forms/FormTextarea";
import KonfiAppEventTypeSelect from "../../../components/Ui/elements/KonfiAppEventTypeSelect";
import Accordion from "../../../components/Ui/accordion/Accordion.vue";
import AccordionElement from "../../../components/Ui/accordion/AccordionElement.vue";
import ModelIndexList from "../../../components/Admin/ModelIndexList.vue";
import ModelIndexPage from "../../../components/Admin/ModelIndexPage.vue";
import NavButton from "../../../components/Ui/buttons/NavButton.vue";
import SaveButton from "../../../components/Ui/buttons/SaveButton.vue";

export default {
    name: "Editor",
    components: {
        SaveButton,
        NavButton,
        ModelIndexPage,
        ModelIndexList,
        AccordionElement,
        Accordion,
        KonfiAppEventTypeSelect,
        FormTextarea,
        FormImageAttacher,
        FormCheck, FormSelectize, FormInput, Tab, Tabs, TabHeader, TabHeaders, CardBody, CardHeader, Card
    },
    props: ['city', 'streams', 'ministries', 'tab', 'canDelete', 'possibleChildren'],
    created() {
        this.$api().get(route('api.ministries.list')).then(response => {
            for (const ministryKey in response.data) {
                this.myMinistries.push({id: response.data[ministryKey].category, name: response.data[ministryKey].category});
            }
            this.ministriesLoaded = true;
        });

    },
    data() {
        var streamOptions = [];
        for (var streamKey in this.streams) {
            streamOptions.push({id: streamKey, name: this.streams[streamKey]});
        }

        let myCity = {
            is_org: false,
            children: [],
            ...this.city
        };
        myCity.childIds = [];
        myCity.children.forEach(child => myCity.childIds.push(child.id));


        return {
            apiToken: this.$page.props.currentUser.data.api_token,
            myMinistries: [],
            ministriesLoaded: false,
            myCity,
            activeTab: this.tab || 'home',
            streamOptions: streamOptions,
        }
    },
    methods: {
        getTitle() {
            return this.city.id ? 'Kirchengemeinde "' + this.city.name + '" bearbeiten' : 'Neue Kirchengemeinde anlegen';
        },
        saveCity() {
            // new admin route
            if (this.city.id) {
                this.$inertia.patch(route('admin.city.update', {modelId: this.city.id}), this.myCity);
            } else {
                this.$inertia.post(route('admin.cities.store'), this.myCity);
            }
        },
        deleteCity() {
            if (!this.canDelete) return;
            if (confirm('Willst du diese Kirchengemeinde wirklich unwiderruflich löschen?')) {
                this.$inertia.delete(route('admin.city.destroy', this.city.id));
            }
        }
    }
}
</script>

<style scoped>

</style>
