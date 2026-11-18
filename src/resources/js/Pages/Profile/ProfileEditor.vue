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
    <admin-layout title="Mein Profil">
        <template #navbar-left>
            <button
                class="btn btn-primary"
                title="Profil speichern"
                @click="saveProfile"
            >
                <span class="d-inline d-md-none mdi mdi-content-save"></span>
                <span class="d-none d-md-inline">Speichern</span>
            </button>
        </template>
        <template #tab-headers>
            <TabHeaders>
                <TabHeader
                    title="Profil"
                    id="profile"
                    :active-tab="activeTab"
                />
                <TabHeader
                    title="Sicherheit"
                    id="security"
                    :active-tab="activeTab"
                />
                <TabHeader
                    title="E-Mailbenachrichtigungen"
                    id="subscriptions"
                    :active-tab="activeTab"
                />
                <TabHeader
                    title="Startseite"
                    id="homeScreenConfiguration"
                    :active-tab="activeTab"
                />
                <TabHeader
                    title="Verbundene Kalender"
                    id="calendars"
                    :active-tab="activeTab"
                    :count="calendarConnections.length"
                    :disabled="user.email.slice(-8) != '@elkw.de'"
                    disabled-title="Diese Funktion ist nur für Benutzer mit einem ELKW-Konto verfügbar."
                />
                <TabHeader
                    title="Externe Inhalte"
                    id="externalContent"
                    :active-tab="activeTab"
                />
            </TabHeaders>
        </template>
        <Tabs>
            <Tab
                id="profile"
                :active-tab="activeTab"
            >
                <ProfileTab :user="user" />
            </Tab>
            <Tab
                id="security"
                :active-tab="activeTab"
            >
                <SecurityTab
                    :user="user"
                    :two-factor-enabled="twoFactorEnabled"
                    :two-factor-pending="twoFactorPending"
                    :passkeys="passkeys"
                />
            </Tab>
            <Tab
                id="subscriptions"
                :active-tab="activeTab"
            >
                <SubscriptionsTab
                    :cities="cities"
                    :subscriptions="subscriptions"
                />
            </Tab>
            <Tab
                id="homeScreenConfiguration"
                :active-tab="activeTab"
            >
                <HomeScreenConfigurationTab
                    :available-tabs="availableTabs"
                    :cities="cities"
                    :home-screen-tabs-config="myHomeScreenTabsConfig"
                    :locations="locations"
                    :ministries="ministries"
                    :settings="settings"
                />
            </Tab>
            <Tab
                id="calendars"
                :active-tab="activeTab"
            >
                <CalendarConnectionsTab
                    :user="user"
                    :calendar-connections="calendarConnections"
                />
            </Tab>
            <Tab
                id="externalContent"
                :active-tab="activeTab"
            >
                <ExternalContentTab :user="editedUser" />
            </Tab>
        </Tabs>
    </admin-layout>
</template>

<script>
import TabHeaders from '../../components/Ui/tabs/tabHeaders'
import TabHeader from '../../components/Ui/tabs/tabHeader'
import Tabs from '../../components/Ui/tabs/tabs'
import Tab from '../../components/Ui/tabs/tab'
import ProfileTab from '../../components/Profile/Tabs/ProfileTab'
import SecurityTab from '../../components/Profile/Tabs/SecurityTab'
import CalendarConnectionsTab from '../../components/Profile/Tabs/CalendarConnectionsTab'
import SubscriptionsTab from '../../components/Profile/Tabs/SubscriptionsTab'
import HomeScreenConfigurationTab from '../../components/Profile/Tabs/HomeScreenConfigurationTab'
import ExternalContentTab from '../../components/Profile/Tabs/ExternalContentTab'

/** Normalize the optional home-screen configuration before editor use. */
function normalizeHomeScreenTabsConfig(config) {
    if (!config || typeof config !== 'object' || Array.isArray(config)) {
        return { tabs: [] }
    }

    if (!Array.isArray(config.tabs)) {
        config.tabs = []
    }

    return config
}

export default {
    name: 'ProfileEditor',
    props: [
        'tab',
        'user',
        'calendarConnections',
        'cities',
        'subscriptions',
        'availableTabs',
        'homeScreenTabsConfig',
        'locations',
        'ministries',
        'settings',
        'twoFactorEnabled',
        'twoFactorPending',
        'passkeys',
    ],
    components: {
        ExternalContentTab,
        HomeScreenConfigurationTab,
        SubscriptionsTab,
        CalendarConnectionsTab,
        SecurityTab,
        ProfileTab,
        Tab,
        Tabs,
        TabHeader,
        TabHeaders,
    },
    data() {
        return {
            activeTab: this.tab || 'profile',
            editedUser: this.user,
            myHomeScreenTabsConfig: normalizeHomeScreenTabsConfig(this.homeScreenTabsConfig),
        }
    },
    methods: {
        saveProfile() {
            const result = {
                email: this.user.email,
                first_name: this.user.first_name,
                last_name: this.user.last_name,
                office: this.user.office,
                address: this.user.address,
                phone: this.user.phone,
                subscriptions: this.subscriptions,
                homeScreenTabsConfig: this.myHomeScreenTabsConfig,
                settings: this.settings,
                own_website: this.user.own_website,
                own_podcast_title: this.user.own_podcast_title,
                own_podcast_url: this.user.own_podcast_url,
                own_podcast_spotify: this.user.own_podcast_spotify,
                own_podcast_itunes: this.user.own_podcast_itunes,
            }
            this.$inertia.patch(window.route('user.profile.save'), result)
        },
    },
}
</script>

<style scoped></style>
