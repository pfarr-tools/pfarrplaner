<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.de
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarrplaner/pfarrplaner
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
    <div class="absences-tab">
        <h2>Urlaubsanträge</h2>
        <div>
            <a class="btn btn-primary" :href="route('absences.index')" title="Urlaubskalender öffnen">Urlaubskalender
                öffnen</a>
        </div>
        <hr/>
        <div v-if="(check.length == 0) && (approve.length == 0)" class="alert alert-info">Zur Zeit gibt es keine zu
            bearbeitenden. Urlaubsanträge.
        </div>
        <div v-else>
            <div v-if="(check.length > 0)">
                <h3>Zur Überprüfung</h3>
                <fake-table :columns="[3,3,3,3]" :headers="['Benutzer', 'Beschreibung', '', '']"
                            collapsed-header="Abwesenheiten">
                    <div class="row mb-3 p-1" v-for="absence in myCheckableAbsences">
                        <div class="col-md-3">
                            <b>{{ absence.user.name }}</b><br/>
                            vom {{ moment(absence.updated_at).locale('de').format('DD.MM.YYYY') }}
                        </div>
                        <div class="col-md-3">
                            <b>{{ absence.reason }}</b><br/>
                            {{ moment(absence.from).locale('de').format('DD.MM.YYYY') }} -
                            {{ moment(absence.to).locale('de').format('DD.MM.YYYY') }}
                        </div>
                        <div class="col-md-3"></div>
                        <div class="col-md-3 text-right">
                            <nav-button v-if="!absence.checking" type="success" icon="mdi mdi-check"
                                        title="Als überprüft markieren" force-icon @click="absence.checking=true; forceChecked(absence)" />
                            <span v-else class="mdi mdi-spin mdi-loading" title="Wird als überprüft markiert..."></span>
                            <nav-button type="primary" icon="mdi mdi-pencil" title="Zur Überprüfung des Antrags" force-icon
                                        @click="editAbsence(absence)"/>
                        </div>
                    </div>
                </fake-table>
            </div>
            <div v-if="(approve.length > 0)">
                <hr v-if="check.length > 0"/>
                <h3>Zur Genehmigung</h3>
                <fake-table :columns="[3,3,3,3]" :headers="['Benutzer', 'Beschreibung', 'Status', '']"
                            collapsed-header="Abwesenheiten">
                    <div class="row mb-3 p-1" v-for="absence in myApprovableAbsences">
                        <div class="col-md-3">
                            <b>{{ absence.user.name }}</b><br/>
                            vom {{ moment(absence.updated_at).locale('de').format('DD.MM.YYYY') }}
                        </div>
                        <div class="col-md-3">
                            <b>{{ absence.reason }}</b><br/>
                            {{ moment(absence.from).locale('de').format('DD.MM.YYYY') }} -
                            {{ moment(absence.to).locale('de').format('DD.MM.YYYY') }}
                        </div>
                        <div class="col-md-3">
                            <checked-process-item check="1">
                                <template slot="positive">
                                    Überprüft <span v-if="absence.checked_at">
                                        am {{ moment(absence.checked_at).locale('de').format('DD.MM.YYYY') }}
                                        um {{ moment(absence.checked_at).locale('de').format('HH:MM') }} Uhr
                                    </span> <span v-if="absence.checked_by">von {{ absence.checked_by.name }}</span>
                                </template>
                            </checked-process-item>
                        </div>
                        <div class="col-md-3 text-right">
                            <nav-button v-if="!absence.approving" type="success" icon="mdi mdi-check"
                                        title="Als genehmigt markieren" force-icon @click="absence.approving=true; forceApproved(absence)" />
                            <span v-else class="mdi mdi-spin mdi-loading" title="Wird als genehmigt markiert..."></span>
                            <nav-button type="primary" icon="mdi mdi-pencil" title="Zur Genehmigung des Antrags" force-icon
                                        @click="editAbsence(absence)"/>
                        </div>
                    </div>
                </fake-table>
            </div>
        </div>
    </div>
</template>

<script>
import FakeTable from "../Ui/FakeTable";
import NavButton from "../Ui/buttons/NavButton";
import CheckedProcessItem from "../Ui/elements/CheckedProcessItem";

export default {
    name: "AbsenceRequestsTab",
    components: {CheckedProcessItem, NavButton, FakeTable},
    props: ['title', 'description', 'user', 'settings', 'check', 'approve', 'count', 'config'],
    data() {
        let myCheckableAbsences =  this.check;
        let myApprovableAbsences = this.approve;

        for (let id in myCheckableAbsences) {
            myCheckableAbsences[id].checking = false;
        }
        for (let id in myApprovableAbsences) {
            myApprovableAbsences[id].approving = false;
        }

        return {
            apiToken: this.$page.props.currentUser.data.api_token,
            myCheckableAbsences,
            myApprovableAbsences,
        }
    },
    methods: {
        editAbsence(absence) {
            this.$inertia.get(route('absence.edit', absence.id));
        },
        forceChecked(absence) {
            axios.post(route('api.absence.set-checked', {
                api_token: this.apiToken,
                absence: absence.id,
            })).then(response => {
                this.myCheckableAbsences = this.myCheckableAbsences.filter(item => item.id != absence.id);
            });
        },
        forceApproved(absence) {
            axios.post(route('api.absence.set-approved', {
                api_token: this.apiToken,
                absence: absence.id,
            })).then(response => {
                this.myApprovableAbsences = this.myApprovableAbsences.filter(item => item.id != absence.id);
            });
        },
    }

}
</script>

<style scoped>

</style>
