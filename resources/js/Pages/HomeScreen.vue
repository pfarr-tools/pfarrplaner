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
            <div class="btn-group me-1">
                <a class="btn btn-primary" :href="route('calendar')"><span class="mdi mdi-calendar"></span> <span
                    class="d-none d-md-inline">Zum Kalender</span></a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Weitere Optionen aufklappen</span>
                </button>
                <div class="dropdown-menu p-1">
                    <form-date-picker :config="myDatePickerSettings" v-model="myQuickPickerDate"
                                      @input="quickPickDate($event)" @dp-update="updateViewDate"/>
                    <hr/>
                    <div class="px-2 mb-2 text-sm">
                        <nav-button type="secondary btn-sm" icon="mdi mdi-calendar" force-icon
                                    :key="myQuickPickViewDate.toISOString()"
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
                            <div class="text-end">
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

            <create-service-wizard-button :cities="cities" />

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
        <template v-slot:navbar-right>
            <li class="nav-item">
                <inertia-link :href="route('user.profile')" class="nav-link" title="Anzeigeeinstellungen">
                    <span class="mdi mdi-cog"></span> <span class="d-none d-md-inline">Anzeige</span>
                </inertia-link>
            </li>
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
                            class="fw-bold">Hinweis: </span><span v-html="replacement.absence.replacement_notes.replaceAll(/(\r)*\n/g, '<br />')"></span></small></div>
                    </li>
                </ul>
            </div>
            <div v-if="settings.homeScreenConfig.showReplacements && (masteredPools && (masteredPools.length > 0))"
                 class="alert alert-info">
                <div class="text-bold">Du bist aktuell Poolmaster:in für folgende Pools:</div>
                <ul>
                    <li v-for="masteredPool in masteredPools">
                        <b>{{ masteredPool.pool.name }}</b> ({{ moment(masteredPool.start+' 0:00:00').format('DD.MM.YYYY') }} -
                        {{ moment(masteredPool.end+' 23:59:59').format('DD.MM.YYYY') }})<br />
                        <template v-if="masteredPool.current_replacements && (masteredPool.current_replacements.length > 0)">
                        Dort vertrittst du aktuell:
                        <ul>
                            <li v-for="(replacement,replacementIndex) in masteredPool.current_replacements">
                                {{ replacement.absence.user.name }} ({{ replacement.absence.reason }},
                                {{ moment(replacement.from).format('DD.MM.YYYY') }} -
                                {{ moment(replacement.to).format('DD.MM.YYYY') }})
                                <div v-if="replacement.absence.replacement_notes"><small><span
                                    class="fw-bold">Hinweis: </span><span v-html="replacement.absence.replacement_notes.replaceAll(/(\r)*\n/g, '<br />')"></span></small></div>
                            </li>
                        </ul>
                        </template>
                        <template v-else>Dort musst du aktuell niemanden vertreten.</template>
                    </li>
                </ul>
            </div>
        </template>
        <template slot="tab-headers">
            <tab-headers>
                <li v-for="tab in myTabs"
                    :id="tab.key+'Tab'" class="nav-item" @click.prevent.stop="loadTab(tab)">
                    <a class="nav-link" :class="{active: myActiveTab == tab.key}" href="#" role="tab"
                       data-bs-toggle="tab" @click.prevent.stop="loadTab(tab)">
                        {{ tab.title }}
                        <span v-if="tab.count > 0" class="badge"
                              :key="'__'+(tab.count || '')"
                              :class="tab.badgeType ? 'bg-'+tab.badgeType : 'bg-primary'">{{ tab.count }}</span>
                    </a>
                </li>
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
import CreateServiceWizardButton from "../components/Ui/wizards/CreateServiceWizardButton.vue";

export default {
    name: "HomeScreen",
    components: {
        CreateServiceWizardButton,
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
    props: ['user', 'settings', 'activeTab', 'replacements', 'tab', 'tabTitles', 'cities', 'masteredPools'],
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
        }
    },
    async mounted() {
        await this.$api().get(route('api.tab', {
            tab: this.myActiveTab,
        })).then(response => {
            this.myTabs[this.myActiveTab] = response.data.data;
            this.myTabs[this.myActiveTab].loaded = true;
            this.$forceUpdate();
        })

        await Object.keys(this.myTabs).forEach(function (tabKey) {
            this.$api().get(route('api.tab.count', {
                tab: tabKey,
            })).then(response => {
                this.myTabs[response.data.key].count = response.data.count;
                this.$forceUpdate();
            });
        }, this);

        await Object.keys(this.myTabs).forEach(function (tabKey) {
            if (!this.myTabs[tabKey].loaded) {
                this.$api().get(route('api.tab', {
                    tab: tabKey,
                })).then(response => {
                    this.myTabs[tabKey] = response.data.data;
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
