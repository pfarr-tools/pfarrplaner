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
    <admin-layout :title="'Willkommen, '+(user.first_name ? user.first_name : user.name)+'!'">
        <template slot="navbar-left">
            <div class="btn-group mr-1">
                <a class="btn btn-primary" :href="route('calendar')"><span class="mdi mdi-calendar"></span> <span
                    class="d-none d-md-inline">Zum Kalender</span></a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Weitere Optionen aufklappen</span>
                </button>
                <div class="dropdown-menu p-1">
                    <form-date-picker :config="myDatePickerSettings" v-model="myQuickPickerDate"
                                      @input="quickPickDate($event)" @dp-update="updateViewDate"/>
                    <hr/>
                    <div class="px-2 mb-2 text-sm">
                        <nav-button type="secondary btn-sm" icon="mdi mdi-calendar" force-icon
                                    :key="myQuickPickViewDate"
                                    :title="quickPickerMonthText+' im Kalender öffnen'"
                                    @click="openCalendar">{{ quickPickerMonthText }} öffnen
                        </nav-button>
                    </div>
                    <div v-if="myQuickPickerLoading" class="text-center text-muted" style="font-size: 4em;">
                        <span class="mdi mdi-spin mdi-loading"></span>
                    </div>
                    <div v-if="(!myQuickPickerLoading) && (myQuickPickerServices.length > 0)"
                         :key="myQuickPickerChanges" class="px-2">
                        <div v-for="service in myQuickPickerServices" class="mb-2" style="font-size: .8em;">
                            <div class="text-bold">{{ service.timeText }} {{ service.titleText }}</div>
                            <div class="text-sm text-muted">{{ service.locationText }}</div>
                            <div class="text-right">
                                <nav-button type="primary btn-sm" icon="mdi mdi-pencil" force-icon
                                            title="Gottesdienst bearbeiten" @click="editService(service)"/>
                                <nav-button type="light  btn-sm" icon="mdi mdi-view-list" force-icon
                                            title="Liturgie bearbeiten" @click="editLiturgy(service)"/>
                                <nav-button type="light btn-sm" icon="mdi mdi-microphone" force-icon
                                            title="Predigt bearbeiten" @click="editSermon(service)"/>
                            </div>
                        </div>
                    </div>
                    <div v-if="(!myQuickPickerLoading) && (myQuickPickerServices.length == 0)"
                         :key="myQuickPickerChanges" class="text-sm text-muted px-2 mb-2">
                        An diesem Tag sind noch keine Gottesdienste geplant.
                    </div>
                </div>
            </div>

            <button v-if="cities.length > 0"
                    class="btn btn-light" href="#" @click.prevent.stop="createServiceWizard.show = true">
                <span class="mdi mdi-church"></span>
                <span class="d-none d-md-inline">Gottesdienst anlegen...</span>
            </button>

            <inertia-link v-if="config.wizardButtons == '1'" class="btn btn-light" :href="route('baptisms.create')">
                <span class="mdi mdi-water"></span>
                <span class="d-none d-md-inline">Taufe anlegen...</span></inertia-link>&nbsp;
            <inertia-link v-if="config.wizardButtons == '1'" class="btn btn-light" :href="route('funerals.wizard')">
                <span class="mdi mdi-grave-stone"></span>
                <span class="d-none d-md-inline">Beerdigung anlegen...</span></inertia-link>&nbsp;
            <a v-if="config.wizardButtons == '1'" class="btn btn-light" :href="route('weddings.wizard')"><span
                class="mdi mdi-ring"></span>
                <span class="d-none d-md-inline">Trauung anlegen...</span></a>&nbsp;
        </template>
        <template slot="before-flash">
            <div v-if="settings.homeScreenConfig.showReplacements && (replacements.length > 0)"
                 class="alert alert-info">
                <div class="text-bold">Du vertrittst aktuell:</div>
                <ul>
                    <li v-for="(replacement,replacementIndex) in replacements">
                        {{ replacement.absence.user.name }} ({{ replacement.absence.reason }},
                        {{ moment(replacement.from).format('DD.MM.YYYY') }} -
                        {{ moment(replacement.to).format('DD.MM.YYYY') }})
                        <div v-if="replacement.absence.replacement_notes"><small><span
                            class="text-bold">Hinweis: </span>{{ replacement.absence.replacement_notes }}</small></div>
                    </li>
                </ul>
            </div>
        </template>
        <template slot="tab-headers">
            <tab-headers>
                <li v-for="tab in myTabs"
                    :id="tab.key+'Tab'" class="nav-item" @click.prevent.stop="loadTab(tab)">
                    <a class="nav-link" :class="{active: myActiveTab == tab.key}" href="#" role="tab"
                       data-toggle="tab" @click.prevent.stop="loadTab(tab)">
                        {{ tab.title }}
                        <span v-if="tab.count > 0" class="badge"
                              :key="tab.count"
                              :class="tab.badgeType ? 'badge-'+tab.badgeType : 'badge-primary'">{{ tab.count }}</span>
                    </a>
                </li>
                <div class="ml-auto d-inline tab-setup">
                    <a :href="route('user.profile', {tab: 'homeScreenConfiguration'})"
                       class="p-2 pl-3 tab-setup ml-auto"
                       title="Angezeigte Reiter konfigurieren"><span class="mdi mdi-cog"></span>
                        <span class="d-none d-md-inline">Anzeige</span>
                    </a>
                </div>
            </tab-headers>
        </template>
        <div v-if="myTabsConfig.tabs.length == 0" class="alert alert-info">
            Dieser Startbildschirm ist noch ziemlich leer. In deinem Profil kannst du einstellen, was du hier sehen
            möchtest.
            <div>
                <a class="btn btn-primary" :href="route('user.profile', {tab: 'homeScreenConfiguration'})">Profil
                    bearbeiten</a>
            </div>
        </div>
        <modal v-if="createServiceWizard.show"
               min-height="80vh" max-height="80vh"
               @close="createServiceFromWizard"
               @cancel="createServiceWizard.show = false"
               title="Gottesdienst anlegen" close-button-label="Anlegen">
            <form-selectize label="Für Kirchengemeinde" :options="cities" v-model="createServiceWizard.city"/>
            <form-date-picker name="date" label="Datum und Uhrzeit" v-model="createServiceWizard.date"
                              :config="createServiceWizard.pickerConfig" iso-date/>
        </modal>
        <tabs>
            <tab v-for="tab in myTabs" :id="tab.key" :key="tab.key" :active-tab="myActiveTab">
                <component v-if="tab.loaded" :is="tabComponent(tab)" v-bind="tab"
                           :user="user" :settings="settings"
                           :config="settings.homeScreenTabsConfig.tabs[tab.index].config"/>
                <div v-else class="tab-loader">
                    <span class="mdi mdi-spin mdi-loading"></span>
                </div>
            </tab>
        </tabs>
    </admin-layout>
</template>

<script>
import TabHeaders from "../components/Ui/tabs/tabHeaders";
import TabHeader from "../components/Ui/tabs/tabHeader";
import Tabs from "../components/Ui/tabs/tabs";
import Tab from "../components/Ui/tabs/tab";
import AdminTab from "../components/HomeScreen/AdminTab";
import AdminBackupTab from "../components/HomeScreen/AdminBackupTab.vue";
import AbsencesTab from "../components/HomeScreen/AbsencesTab";
import AbsenceRequestsTab from "../components/HomeScreen/AbsenceRequestsTab";
import BaptismsTab from "../components/HomeScreen/BaptismsTab";
import CasesTab from "../components/HomeScreen/CasesTab";
import FuneralsTab from "../components/HomeScreen/FuneralsTab";
import MissingEntriesTab from "../components/HomeScreen/MissingEntriesTab";
import NextOfferingsTab from "../components/HomeScreen/NextOfferingsTab";
import NextServicesTab from "../components/HomeScreen/NextServicesTab";
import RegistrationsTab from "../components/HomeScreen/RegistrationsTab";
import StreamingTab from "../components/HomeScreen/StreamingTab";
import WeddingsTab from "../components/HomeScreen/WeddingsTab";
import FormDatePicker from "../components/Ui/forms/FormDatePicker.vue";
import NavButton from "../components/Ui/buttons/NavButton.vue";
import Modal from "../components/Ui/modals/Modal.vue";
import FormSelectize from "../components/Ui/forms/FormSelectize.vue";

export default {
    name: "HomeScreen",
    components: {
        FormSelectize,
        Modal,
        NavButton,
        FormDatePicker,
        TabHeader,
        TabHeaders,
        Tabs,
        Tab,
        AdminTab,
        AdminBackupTab,
        AbsencesTab,
        AbsenceRequestsTab,
        BaptismsTab,
        CasesTab,
        FuneralsTab,
        MissingEntriesTab,
        NextOfferingsTab,
        NextServicesTab,
        RegistrationsTab,
        StreamingTab,
        WeddingsTab,
    },
    props: ['user', 'settings', 'activeTab', 'replacements', 'tab', 'tabTitles', 'cities'],
    created() {
        var index = 0;
        this.myTabsConfig.tabs.forEach(function (tab, tabIndex) {
            this.myTabs[tab.type + tabIndex] = {
                title: this.tabTitles[tabIndex],
                key: tab.type + tabIndex,
                description: '',
                count: 0,
                tabObject: tab,
                loaded: false,
            }
        }, this);
    },
    computed: {
        quickPickerMonthText() {
            return this.myQuickPickViewDate.locale('de').format('MMMM YYYY');
        },
    },
    data() {
        let myTabNames = this.settings.homeScreenTabs ? this.settings.homeScreenTabs.split(',') : [];
        return {
            apiToken: this.$page.props.currentUser.data.api_token,
            myUser: this.user,
            config: this.settings.homeScreenConfig || {},
            myTabNames: myTabNames,
            myTabsConfig: this.settings.homeScreenTabsConfig,
            myTabs: {},
            myActiveTab: this.activeTab || (this.settings.homeScreenTabsConfig.tabs[0] ? this.settings.homeScreenTabsConfig.tabs[0].type + '0' : null),
            myDatePickerSettings: {
                inline: true,
                format: 'L',
                locale: 'de',
            },
            myQuickPickerDate: moment().locale('de').format('DD.MM.YYYY'),
            myQuickPickViewDate: moment(),
            myQuickPickerServices: [],
            myQuickPickerChanges: 0,
            myQuickPickerLoading: true,
            createServiceWizard: {
                show: false,
                city: this.cities.length ? this.cities[0].id : null,
                date: null,
                pickerConfig: {
                    locale: 'de',
                    format: 'DD.MM.YYYY',
                    showClear: true,
                },
            },
        }
    },
    async mounted() {
        await axios.get(route('api.tab', {
            api_token: this.apiToken,
            tab: this.myActiveTab,
        })).then(response => {
            this.myTabs[this.myActiveTab] = response.data;
            this.myTabs[this.myActiveTab].loaded = true;
            this.$forceUpdate();
        })

        await Object.keys(this.myTabs).forEach(function (tabKey) {
            axios.get(route('api.tab.count', {
                api_token: this.apiToken,
                tab: tabKey,
            })).then(response => {
                this.myTabs[response.data.key].count = response.data.count;
                this.$forceUpdate();
            });
        }, this);

        await Object.keys(this.myTabs).forEach(function (tabKey) {
            if (!this.myTabs[tabKey].loaded) {
                axios.get(route('api.tab', {
                    api_token: this.apiToken,
                    tab: tabKey,
                })).then(response => {
                    this.myTabs[tabKey] = response.data;
                    this.myTabs[tabKey].loaded = true;
                    this.$forceUpdate();
                });
            }
        }, this);

        this.quickPickDate(this.myQuickPickerDate);
    },
    methods: {
        tabComponent(tab) {
            return tab.type.charAt(0).toUpperCase() + tab.type.slice(1) + 'Tab';
        },
        loadTab(tab) {
            this.myActiveTab = tab.key;
            this.$forceUpdate();
        },
        quickPickDate(d) {
            this.myQuickPickerDate = d;
            this.myQuickPickerLoading = true;
            axios.get(route('api.calendar.quick-pick', {
                api_token: this.apiToken,
                date: this.myQuickPickerDate,
            })).then(response => {
                this.myQuickPickerServices = response.data;
                this.myQuickPickerLoading = false;
                this.myQuickPickerChanges++;
                this.$forceUpdate();
            });
        },
        updateViewDate(e) {
            this.myQuickPickViewDate = e.viewDate;
            this.$forceUpdate();
        },
        openCalendar() {
            this.$inertia.get(route('calendar', {date: this.myQuickPickViewDate.format('YYYY-MM')}));
        },
        editService(service) {
            this.$inertia.get(route('service.edit', {service: service.slug}));
        },
        editLiturgy(service) {
            this.$inertia.get(route('liturgy.editor', {service: service.slug}));
        },
        editSermon(service) {
            this.$inertia.get(route('service.sermon.editor', {service: service.slug}));
        },
        createServiceFromWizard() {
            if (!this.createServiceWizard.city) return;
            if (!this.createServiceWizard.date) return;
            this.createServiceWizard.show = false;
            this.$inertia.get(route('service.create', {
                city: this.createServiceWizard.city,
                date: this.createServiceWizard.date.substring(0, 10),
            }));
        }
    }
}
</script>

<style scoped>
.card-header {
    padding-bottom: 0 !important;
    background-color: #fcfcfc;
}

ul.nav.nav-tabs {
    margin-bottom: 0;
    border-bottom-width: 0;
}

.tab-setup a {
    font-size: .7em;
    color: #c3c3c3 !important;
}

.tab-setup a:hover {
    color: darkgray !important;
    text-decoration: none;
}

.alert a {
    text-decoration: none;
}

.tab-loader {
    width: 100%;
    margin-top: 30vh;
    font-size: 8em;
    text-align: center;
    color: lightgray;
}

</style>
