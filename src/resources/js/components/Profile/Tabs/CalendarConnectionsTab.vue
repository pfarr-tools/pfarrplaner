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
    <div class="calendar-connections-tab">
        <div v-if="calendarConnections.length == 0" class="alert alert-info">Du hast noch keine externen Kalender verbunden.</div>
        <div class="alert alert-info">
            Um einen Kalender mit Outlook zu verbinden, wird das kostenlose Outlook-Addin <i>Outlook CalDav Synchronizer</i>
            benötigt. Du kannst es <a href="http://caldavsynchronizer.org/download-2/" target="_blank">hier herunterladen</a>.
            <a href="/docs/Eine%20Kalenderverbindung%20mit%20Outlook%20einrichten.pdf">Hier</a>
            findest du eine Anleitung zum Einrichten der Verbindung in Outlook.
        </div>
        <div class="mb-2">
            <inertia-link v-if="createRoute" class="btn btn-light" title="Neuen Kalender verbinden"
                          :href="createRoute">
                <span class="d-inline d-md-none mdi mdi-calendar-plus"></span><span class="d-none d-md-inline">Neue Verbindung anlegen</span>
            </inertia-link>
            <button v-else class="btn btn-light" title="Neue Kalenderverbindungen sind in dieser Installation nicht freigeschaltet" disabled>
                <span class="d-inline d-md-none mdi mdi-calendar-plus"></span><span class="d-none d-md-inline">Neue Verbindung anlegen</span>
            </button>
        </div>
        <fake-table v-if="calendarConnections.length >0"
                    :columns="[5,5,2]" collapsed-header="Kalender"
                    :headers="['Titel', 'URL', '']" >
            <div class="row p-1" v-for="(calendarConnection, key) in calendarConnections" :key="key">
                <div class="col-md-5">{{ calendarConnection.title }}</div>
                <div class="col-md-5"><copyable-code :content="calendarConnection.uri" /></div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary btn-sm" @click="editConnection(calendarConnection)"
                            title="Verbindung bearbeiten">
                        <span class="mdi mdi-pencil"></span>
                    </button>
                    <button class="btn btn-danger btn-sm" @click="deleteConnection(calendarConnection)"
                            title="Verbindung löschen">
                        <span class="mdi mdi-delete"></span>
                    </button>
                </div>
            </div>
        </fake-table>
    </div>
</template>

<script>
import FakeTable from "../../Ui/FakeTable";
import CopyableCode from "../../Ui/CopyableCode.vue";
export default {
    name: "CalendarConnectionsTab",
    components: {CopyableCode, FakeTable},
    props: ['user', 'calendarConnections'],
    computed: {
        createRoute() {
            return route().has('calendarConnection.create') ? route('calendarConnection.create') : null;
        },
    },
    methods: {
        editConnection(calendarConnection) {
            this.$inertia.get(route('calendarConnection.edit', calendarConnection.id));
        },
        deleteConnection(calendarConnection) {
            if (confirm('Willst du diese Verbindung wirklich löschen?')) {
                this.$inertia.delete(route('calendarConnection.destroy', calendarConnection.id));
            }
        },
    }
}
</script>

<style scoped>
    .alert-info a, .alert-info a:visited, .alert-info a:focus {
        color: black;
    }
</style>
