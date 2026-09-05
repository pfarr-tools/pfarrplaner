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
    <admin-layout title="Abwesenheit bearbeiten">

        <template #navbar-left>
            <nav-button v-if="role == 'editor'"
                        @click="saveAbsence"
                        type="primary"
                        icon="mdi mdi-content-save"
                        force-icon
                        :disabled="form.processing"
                        title="Abwesenheitseintrag speichern und zur Überprüfung absenden">
                Zur Überprüfung absenden
            </nav-button>

            <save-button v-if="role == 'self-editor'"
                         @click="saveAbsence"
                         :disabled="form.processing"
                         class="btn btn-primary"
                         title="Abwesenheitseintrag speichern"/>

            <nav-button v-if="role == 'admin'"
                        @click="checkAndSave()"
                        type="success"
                        icon="mdi mdi-check"
                        force-icon>
                Zur Genehmigung weiterleiten
            </nav-button>

            <nav-button v-if="role == 'approver'"
                        @click="approveAndSave()"
                        type="success"
                        icon="mdi mdi-check"
                        force-icon>
                Genehmigen
            </nav-button>

            <nav-button v-if="role == 'approver'"
                        @click="returnAndSave()"
                        class="ms-1"
                        type="warning"
                        icon="mdi mdi-undo"
                        force-icon>
                Erneut überprüfen lassen
            </nav-button>

            <nav-button v-if="(role == 'admin') || (role=='approver')"
                        @click="rejectAbsence"
                        class="ms-1"
                        type="danger"
                        icon="mdi mdi-close-octagon"
                        force-icon>
                Ablehnen
            </nav-button>

            <nav-button v-if="mayDelete"
                        @click="deleteAbsence"
                        type="danger"
                        icon="mdi mdi-delete"
                        class="ms-1"
                        force-icon>
                Löschen
            </nav-button>
        </template>


        <template #before-flash>
            <div v-if="form.workflow_status < 10" class="alert"
                 :class="setApproved ? 'alert-success' : 'alert-danger'">

                <checked-process-item  :check="form.workflow_status > 0"
                                      :negative="needsCheckText(form.user.vacation_admins, 'überprüft')">

                    <template #positive>
                        Der Urlaubsantrag wurde
                        <span v-if="form.checked_at">
                            am {{ moment(form.checked_at).locale('de').format('DD.MM.YYYY') }}
                            um {{ moment(form.checked_at).locale('de').format('HH:mm') }} Uhr
                        </span>
                        <span v-if="form.checked_by">von {{ form.checked_by.name }}</span>
                        überprüft und zur Genehmigung weitergeleitet.
                    </template>

                </checked-process-item>

                <div v-if="form.admin_notes && (role=='approver')">
                    <div class="text-bold">Bemerkungen zur Überprüfung:</div>
                    <nl2br class="text-small" :text="form.admin_notes" />
                </div>

                <form-textarea v-if="(form.workflow_status == 0) && (role=='admin')"
                               name="admin_notes"
                               label="Bemerkungen zur Überprüfung"
                               v-model="form.admin_notes"/>

                <checked-process-item :check="form.workflow_status > 1"
                                      :negative="needsCheckText(form.user.vacation_approvers, 'genehmigt')">

                    <template #positive>
                        Der Urlaubsantrag wurde
                        <span v-if="form.approved_at">
                            am {{ moment(form.approved_at).locale('de').format('DD.MM.YYYY') }}
                            um {{ moment(form.approved_at).locale('de').format('HH:mm') }} Uhr
                        </span>
                        <span v-if="form.approved_by">von {{ form.approved_by.name }}</span>
                        genehmigt.
                    </template>

                </checked-process-item>

                <div v-if="(role != 'approver') && form.approver_notes">
                    <div class="text-bold">Bemerkungen zur Genehmigung:</div>
                    <nl2br class="text-small" :text="form.approver_notes" />
                </div>

                <form-textarea v-if="role == 'approver'"
                               label="Bemerkungen zur Genehmigung"
                               v-model="form.approver_notes"
                               name="approver_notes"/>
            </div>
        </template>


        <template #tab-headers>
            <tab-headers>
                <tab-header id="home" :active-tab="myActiveTab" title="Abwesenheit" :switch-handler="true" @tab="switchTab" />
                <tab-header v-if="form.user.needs_replacement" id="replacement" :active-tab="myActiveTab" title="Vertretung" :switch-handler="true" @tab="switchTab" />
                <tab-header id="attachments" :active-tab="myActiveTab" title="Dateien" :count="fileCount" :switch-handler="true" @tab="switchTab" />
            </tab-headers>
        </template>


        <tabs>
            <tab id="home" :active-tab="myActiveTab">

                <!-- ✅ NEW: v-model DateRangeInput -->
                <date-range-input
                    label="Zeitraum"
                    v-model="dateRange"
                    :disabled="!mayEdit"
                />

                <form-input label="Beschreibung"
                            help="Grund der Abwesenheit"
                            name="reason"
                            placeholder="z.B. Urlaub"
                            v-model="form.reason"
                            :disabled="!mayEdit"/>

                <form-check label="Es handelt sich um eine Krankmeldung"
                            name="sick_days"
                            v-model="form.sick_days"
                            :disabled="!mayEdit"/>

                <hr/>

                <form-textarea label="Interne Anmerkungen"
                               v-model="form.internal_notes"
                               name="internal_notes"
                               :disabled="!mayEdit"/>

                <div class="row" v-if="role == 'self-editor'">
                    <div class="col-md-6">
                        <form-check label="Von der zuständigen Stelle genehmigt"
                                    v-model="setApproved"
                                    :is-checked-item="true"/>
                    </div>

                    <div class="col-md-6" v-if="setApproved">
                        <form-date-picker label="Datum der Genehmigung"
                                          v-model="form.approved_at"
                                          :is-checked-item="true"/>
                    </div>
                </div>

            </tab>


            <tab v-if="form.user.needs_replacement" id="replacement" :active-tab="myActiveTab">
                <fake-table :columns="[3,2,4,1]"
                            :headers="['Vertreter:in', 'oder: Pool', 'Zeitraum', '']"
                            collapsed-header="Vertreten durch"
                            class="mt-3"
                            :key="form.replacements.length">

                    <div class="row py-1 fake-table-row"
                         v-for="(replacement, index) in form.replacements"
                         :key="index">

                        <div class="col-md-3">
                            <people-select :people="users"
                                           v-model="replacement.users"
                                           :city="form.user.cities[0]"
                                           :disabled="!mayEdit"/>
                        </div>

                        <div class="col-md-2">
                            <form-selectize :options="form.user.pools"
                                            v-model="replacement.pool_id" />
                        </div>

                        <div class="col-md-4">

                            <!-- ✅ NEW: replacement uses v-model too -->
                            <date-range-input
                                v-model="replacement.range"
                                :disabled="!mayEdit"
                            />

                        </div>

                        <div class="col-md-1 text-end">
                            <button class="btn btn-danger"
                                    @click.prevent="deleteReplacement(index)"
                                    :disabled="!mayEdit">
                                <span class="mdi mdi-delete"></span>
                            </button>
                        </div>

                    </div>

                </fake-table>

                <button class="btn btn-light"
                        @click.prevent="addReplacement"
                        :disabled="!mayEdit">
                    Vertretung hinzufügen
                </button>

                <hr/>

                <form-textarea label="Notizen für die Vertretung"
                               v-model="form.replacement_notes"
                               name="replacement_notes"
                               :disabled="!mayEdit"/>
            </tab>


            <tab id="attachments" :active-tab="myActiveTab">

                <h3>Angehängte Dateien</h3>

                <attachment-list v-model="form.attachments"
                                 delete-route-name="absence.detach"
                                 :parent-object="form"
                                 parent-type="absence"
                                 :key="form.attachments.length"/>

                <hr/>

                <h3>Dateien hinzufügen.</h3>

                <form-file-uploader :parent="form" :no-pixabay="true"
                                    :upload-route="route('absence.attach', form.id)"
                                    v-model="form.attachments"/>
            </tab>

        </tabs>

    </admin-layout>
</template>

<script>
import DateRangeInput from "../../components/Ui/elements/DateRangeInput";
import {clone} from "lodash";
import FormTextarea from "../../components/Ui/forms/FormTextarea.vue";
import CheckedProcessItem from "../../components/Ui/elements/CheckedProcessItem.vue";
import NavButton from "../../components/Ui/buttons/NavButton.vue";
import TabHeaders from "../../components/Ui/tabHeaders.vue";
import TabHeader from "../../components/Ui/tabs/tabHeader.vue";
import Tabs from "../../components/Ui/tabs/tabs.vue";
import Tab from "../../components/Ui/tabs/tab.vue";
import FormInput from "../../components/Ui/forms/FormInput.vue";
import FormCheck from "../../components/Ui/forms/FormCheck.vue";
import FormDatePicker from "../../components/Ui/forms/FormDatePicker.vue";
import FakeTable from "../../components/Ui/FakeTable.vue";
import PeopleSelect from "../../components/Ui/elements/PeopleSelect.vue";
import FormSelectize from "../../components/Ui/forms/FormSelectize.vue";
import AttachmentList from "../../components/Ui/elements/AttachmentList.vue";
import FormFileUploader from "../../components/Ui/forms/FormFileUploader.vue";
import SaveButton from "../../components/Ui/buttons/SaveButton.vue";
import { serializePlannerDateToUtc } from "../../helpers/plannerDates";

export default {
    name: "AbsenceEditor",

    components: {
        FormFileUploader,
        AttachmentList,
        FormSelectize,
        PeopleSelect,
        FakeTable,
        FormDatePicker,
        FormCheck,
        FormInput,
        Tab,
        Tabs,
        TabHeader,
        TabHeaders,
        NavButton,
        CheckedProcessItem,
        FormTextarea,
        DateRangeInput,
        SaveButton,
    },

    props: [
        'absence',
        'users',
        'mayCheck',
        'mayApprove',
        'maySelfAdminister',
        'activeTab'
    ],

    data() {
        let form = this.$inertia.form({
            ...this.absence,
            replacements: this.absence.replacements ?? [],
            attachments: this.absence.attachments ?? [],
        });

        return {
            form,
            setApproved: [2, 11].includes(Number(this.absence.workflow_status)),
            myActiveTab: this.activeTab ?? 'home',
        };
    },

    computed: {
        isPersisted() {
            return !!this.form.id;
        },
        role() {
            if (this.maySelfAdminister) {
                return 'self-editor';
            }

            if (!this.mayEdit) {
                return 'readonly';
            }

            if (this.mayApprove) {
                return 'approver';
            }

            if (this.mayCheck) {
                return 'admin';
            }

            return 'editor';
        },
        mayEdit() {
            const workflowStatus = Number(this.form.workflow_status);

            if (this.maySelfAdminister) {
                return true;
            }

            return (this.mayCheck && workflowStatus === 0)
                || (this.mayApprove && workflowStatus === 1)
                || (workflowStatus <= 0);
        },
        mayDelete() {
            const workflowStatus = Number(this.form.workflow_status);

            return !!(
                this.form.id
                && (
                    ((this.role === 'editor') && (workflowStatus === 0))
                    || (this.role === 'self-editor')
                    || this.mayCheck
                    || this.mayApprove
                )
            );
        },
        // ✅ bridge for new DateRangeInput
        dateRange: {
            get() {
                return [this.form.from, this.form.to];
            },
            set([from, to]) {
                this.form.from = from ? this.serializePlannerDate(from) : null;
                this.form.to = to ? this.serializePlannerDate(to, true) : null;
            }
        },

        fileCount() {
            return this.form.attachments.length;
        }
    },

    methods: {
        serializePlannerDate(value, endOfDay = false) {
            return serializePlannerDateToUtc(value, { endOfDay });
        },
        needsCheckText(users, action) {
            if (!users || !users.length) {
                return `Dieser Antrag muss noch ${action} werden.`;
            }

            return `Dieser Antrag muss noch von ${users.map(user => user.name).join(', ')} ${action} werden.`;
        },

        addReplacement() {
            this.form.replacements.push({
                users: [],
                pool_id: null,
                range: [this.form.from, this.form.to]
            });
        },

        deleteReplacement(index) {
            this.form.replacements.splice(index, 1);
        },

        prepareForm() {
            let record = clone(this.form);

            record.from = this.serializePlannerDate(record.from);
            record.to = this.serializePlannerDate(record.to, true);

            record.replacements = record.replacements.map(r => ({
                ...r,
                from: r.range?.[0] ? this.serializePlannerDate(r.range[0]) : null,
                to: r.range?.[1] ? this.serializePlannerDate(r.range[1], true) : null,
            }));

            if (this.maySelfAdminister) {
                record.workflow_status = this.setApproved ? 11 : 10;
            } else if (this.role === 'editor') {
                record.workflow_status = 0;
            }

            if (record.sick_days && (record.reason === 'Urlaub')) {
                record.reason = 'Krankheit';
            }

            return record;
        },

        switchTab(tab) {
            this.myActiveTab = tab;
        },
        saveAbsence() {
            const routeName = this.isPersisted ? 'absence.update' : 'absence.store';
            const parameters = this.isPersisted ? { absence: this.form.id } : {};

            this.form
                .transform(() => this.prepareForm())
                [this.isPersisted ? 'patch' : 'post'](route(routeName, parameters));
        },
        checkAndSave() {
            this.form.workflow_status = 1;
            this.saveAbsence();
        },
        approveAndSave() {
            this.form.workflow_status = 2;
            this.saveAbsence();
        },
        returnAndSave() {
            this.form.workflow_status = 0;
            this.saveAbsence();
        },
        rejectAbsence() {
            if (!confirm('Willst du diesen Abwesenheitseintrag wirklich ablehnen und in den Papierkorb verschieben?')) return;

            this.$inertia.delete(route('absence.destroy', { absence: this.form.id }), {
                data: {
                    sendRejectionMail: true,
                    month: moment(this.form.from).format('M'),
                    year: moment(this.form.from).format('YYYY'),
                },
            });
        },
        deleteAbsence() {
            if (!confirm('Willst du diese Abwesenheit wirklich in den Papierkorb verschieben? Du kannst sie dort später wiederherstellen.')) return;

            this.$inertia.delete(route('absence.destroy', { absence: this.form.id }), {
                data: {
                    month: moment(this.form.from).format('M'),
                    year: moment(this.form.from).format('YYYY'),
                },
            });
        },
    },
    watch: {
        activeTab(value) {
            this.myActiveTab = value ?? 'home';
        },
        setApproved(value) {
            if (!this.maySelfAdminister) return;

            this.form.workflow_status = value ? 11 : 10;
        }
    }
};
</script>
