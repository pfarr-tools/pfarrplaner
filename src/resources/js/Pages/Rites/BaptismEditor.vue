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
    <admin-layout :title="'Taufe von ' + baptism.candidate_name">
        <template #after-flash>
            <AutoSaveNotice
                :draft-available="Boolean(autoSaveDraft && !autoSaveDraftHandled)"
                :status="autoSaveStatus"
                @restore="restoreAutoSaveDraft"
                @discard="discardAutoSaveDraft"
            />
        </template>
        <template #navbar-left>
            <button
                class="btn btn-primary"
                @click.prevent="saveBaptism"
                title="Speichern"
            >
                <span class="d-inline d-md-none mdi mdi-content-save"></span>
                <span class="d-none d-md-inline">Speichern</span></button
            >&nbsp;
            <button
                v-if="myBaptism.id"
                class="btn btn-danger"
                @click.prevent="deleteBaptism"
                title="Löschen"
            >
                <span class="d-inline d-md-none mdi mdi-delete"></span>
                <span class="d-none d-md-inline">Löschen</span>
            </button>
        </template>
        <template #tab-headers>
            <tab-headers>
                <tab-header
                    title="Allgemeines"
                    id="home"
                    :active-tab="activeTab"
                    :is-checked-item="true"
                    :check-value="!myBaptism.needs_dimissorial || myBaptism.dimissorial_received"
                />
                <tab-header
                    title="Vorbereitung"
                    id="prep"
                    :active-tab="activeTab"
                    :is-checked-item="true"
                    :check-value="prepChecks()"
                />
                <tab-header
                    title="Dateien"
                    id="attachments"
                    :active-tab="activeTab"
                    :count="myBaptism.attachments.length + Object.keys(attachments).length"
                />
            </tab-headers>
        </template>
        <tabs>
            <tab
                id="home"
                :active-tab="activeTab"
            >
                <fieldset>
                    <legend>Gottesdienst</legend>
                    <form-selectize
                        label="Taufgottesdienst"
                        name="service_id"
                        :options="services"
                        id-key="id"
                        title-key="name"
                        help="Leer lassen, um dies als Taufanfrage einzuordnen"
                        :settings="myServicePickerConfig"
                        v-model="myBaptism.service_id"
                    />
                    <form-selectize
                        label="Kirchengemeinde"
                        :options="cities"
                        v-model="myBaptism.city_id"
                        id-key="id"
                        title-key="name"
                        name="city_id"
                    />
                </fieldset>
                <fieldset>
                    <legend>Täufling</legend>
                    <div class="row">
                        <div class="col-md-6">
                            <form-input
                                name="candidate_name"
                                label="Name des Täuflings"
                                v-model="myBaptism.candidate_name"
                            />
                        </div>
                        <div class="col-md-6">
                            <form-group label="Zu verwendendes Pronomen">
                                <div>
                                    <div
                                        class="form-check-inline"
                                        v-for="(pronounSet, pronounSetIndex) in pronounSets"
                                        :key="'pronouns_' + pronounSet.key"
                                    >
                                        <label class="form-check-label">
                                            <input
                                                type="radio"
                                                class="form-check-input"
                                                v-model="myBaptism.pronoun_set"
                                                :value="pronounSet.key"
                                            />{{ pronounSet.label }}
                                        </label>
                                    </div>
                                </div>
                            </form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <form-date-picker
                                label="Geburtsdatum"
                                name="dob"
                                v-model="myBaptism.dob"
                                :config="myDatePickerConfig"
                            />
                        </div>
                        <div class="col-md-6">
                            <form-input
                                label="Geburtsort"
                                name="birth_place"
                                v-model="myBaptism.birth_place"
                            />
                        </div>
                    </div>
                    <form-input
                        label="Adresse"
                        v-model="myBaptism.candidate_address"
                    />
                    <div class="row">
                        <div class="col-md-6">
                            <form-input
                                name="candidate_zip"
                                label="PLZ"
                                v-model="myBaptism.candidate_zip"
                            />
                        </div>
                        <div class="col-md-6">
                            <form-input
                                name="candidate_city"
                                label="Ort"
                                v-model="myBaptism.candidate_city"
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <form-input
                                name="candidate_phone"
                                label="Telefon"
                                v-model="myBaptism.candidate_phone"
                            />
                        </div>
                        <div class="col-md-6">
                            <form-input
                                name="candidate_email"
                                label="E-Mailadresse"
                                v-model="myBaptism.candidate_email"
                                type="email"
                            />
                        </div>
                    </div>
                </fieldset>
                <dimissorial-form-part :parent="myBaptism" />
            </tab>
            <tab
                id="prep"
                :active-tab="activeTab"
            >
                <fieldset id="fsPrep">
                    <legend>Erstkontakt</legend>
                    <div class="row">
                        <div class="col-md-4">
                            <form-input
                                name="first_contact_with"
                                label="Erstkontakt mit"
                                v-model="myBaptism.first_contact_with"
                                is-checked-item="1"
                            />
                        </div>
                        <div class="col-md-4">
                            <form-date-picker
                                label="Datum"
                                :is-checked-item="true"
                                name="first_contact_on"
                                v-model="myBaptism.first_contact_on"
                                :config="myDatePickerConfig"
                            />
                        </div>
                        <div class="col-md-4">
                            <form-date-picker
                                label="Taufgespräch"
                                :is-checked-item="true"
                                name="appointment"
                                v-model="myBaptism.appointment"
                                :config="myDateTimePickerConfig"
                            />
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Wichtige Informationen</legend>
                    <form-bible-reference-input
                        name="text"
                        label="Taufspruch"
                        v-model="myBaptism.text"
                        is-checked-item="1"
                        :sources="{}"
                    />
                    <form-textarea
                        name="notes"
                        label="Notizen aus dem Taufgespräch"
                        v-model="myBaptism.notes"
                    />
                </fieldset>
                <fieldset>
                    <legend>Unterlagen</legend>
                    <div
                        v-if="!hasRegistrationForm"
                        class="mb-3"
                    >
                        <p>
                            Wenn das Anmeldeformular in AHAS Online erstellt wurde, kannst du es
                            hier hochladen:
                        </p>
                        <form-file-uploader
                            :single="true"
                            :parent="myBaptism"
                            :upload-route="route('baptism.attach', this.myBaptism.id)"
                            title="Anmeldeformular"
                            v-model="myBaptism.attachments"
                        />
                    </div>
                    <div v-else>
                        <checked-process-item
                            check="1"
                            positive="Anmeldedaten aufgenommen und Anmeldeformular erstellt"
                        />
                        <form-check
                            label="Anmeldung unterschrieben"
                            v-model="myBaptism.signed"
                            name="signed"
                            is-checked-item="1"
                        />
                        <form-check
                            label="Urkunden erstellt"
                            v-model="myBaptism.docs_ready"
                            name="ready"
                            is-checked-item="1"
                        />
                        <form-input
                            label="Urkunden hinterlegt"
                            v-model="myBaptism.docs_where"
                            name="docs_where"
                            class="mt-3"
                            help="Wo sind die Unterlagen hinterlegt?"
                            is-checked-item="1"
                        />
                    </div>
                    <form-check
                        name="processed"
                        label="Kirchenbucheintrag abgeschlossen"
                        v-model="myBaptism.processed"
                        is-checked-item
                    />
                </fieldset>
            </tab>
            <tab
                id="attachments"
                :active-tab="activeTab"
            >
                <fieldset>
                    <legend>Angehängte Dateien</legend>
                    <fake-attachment
                        v-for="(attachment, attachmentIndex) in attachments"
                        :title="attachment.title"
                        :icon="attachment.icon"
                        :description="attachment.description"
                        :extension="attachment.extension"
                        :key="'_auto_attachment_' + attachmentIndex"
                        :use-inertia="attachment.hasSetup"
                        :href="
                            route(
                                attachment.hasSetup ? 'auto-attachment.setup' : 'auto-attachment',
                                {
                                    type: 'baptism',
                                    attachment: attachment.key,
                                    attachable: myBaptism.id,
                                },
                            )
                        "
                    />
                    <attachment-list
                        v-model="myBaptism.attachments"
                        delete-route-name="baptism.detach"
                        :parent-object="myBaptism"
                        parent-type="baptism"
                        :upload-route="route('baptism.attach', myBaptism.id)"
                        chunk-upload-route="/api/attachment-uploads"
                        :chunk-target="{
                            target_type: 'App\\Models\\Rites\\Baptism',
                            target_id: myBaptism.id,
                        }"
                        :key="myBaptism.attachments.length"
                    />
                </fieldset>
                <fieldset>
                    <legend>Hinweis</legend>
                    <div class="mt-2">
                        <small
                            >Das Anmeldeformular zur Taufe bitte nicht hier, sondern im Register
                            "Vorbereitung" hochladen.</small
                        >
                    </div>
                </fieldset>
            </tab>
        </tabs>
    </admin-layout>
</template>

<script>
import { useForm } from '@inertiajs/vue3'
import __ from 'lodash'
import AutoSaveNotice from '../../components/RiteEditors/AutoSaveNotice.vue'
import { clearRiteDraft, getRiteDraft, saveRiteDraft } from '../../helpers/riteAutoSave'
import TabHeaders from '../../components/Ui/tabs/tabHeaders'
import TabHeader from '../../components/Ui/tabs/tabHeader'
import FormGroup from '../../components/Ui/forms/FormGroup'
import FormInput from '../../components/Ui/forms/FormInput'
import Tab from '../../components/Ui/tabs/tab'
import Tabs from '../../components/Ui/tabs/tabs'
import FormCheck from '../../components/Ui/forms/FormCheck'
import FormSelectize from '../../components/Ui/forms/FormSelectize'
import AttachmentList from '../../components/Ui/elements/AttachmentList'
import CheckedProcessItem from '../../components/Ui/elements/CheckedProcessItem'
import FormTextarea from '../../components/Ui/forms/FormTextarea'
import DimissorialFormPart from '../../components/RiteEditors/DimissorialFormPart'
import FormBibleReferenceInput from '../../components/Ui/forms/FormBibleReferenceInput'
import FakeAttachment from '../../components/Ui/elements/FakeAttachment.vue'
import FormDatePicker from '../../components/Ui/forms/FormDatePicker'

/**
 * Formats a date value for the baptism form.
 * @param {*} value Date value.
 * @returns {*} Formatted date value.
 */
function formatDateValue(value) {
    if (!value) return value
    if (typeof value === 'string' && moment(value, 'DD.MM.YYYY', true).isValid()) return value
    return moment(value).format('DD.MM.YYYY')
}

/**
 * Formats a date and time value for the baptism form.
 * @param {*} value Date and time value.
 * @returns {*} Formatted date and time value.
 */
function formatDateTimeValue(value) {
    if (!value) return value
    if (typeof value === 'string' && moment(value, 'DD.MM.YYYY HH:mm', true).isValid()) return value
    return moment(value).format('DD.MM.YYYY HH:mm')
}

/**
 * Prepares a baptism record for editing.
 * @param {Object} baptism Baptism record.
 * @param {string} currentUserName Current user's name.
 * @returns {Object} Formatted baptism form data.
 */
function formatBaptismForForm(baptism, currentUserName) {
    const formData = __.cloneDeep(baptism)
    formData.attachments = formData.attachments || []
    formData.dob = formatDateValue(formData.dob)
    formData.first_contact_on = formData.first_contact_on
        ? formatDateValue(formData.first_contact_on)
        : moment().format('DD.MM.YYYY')
    formData.appointment = formatDateTimeValue(formData.appointment)
    formData.dimissorial_requested = formatDateValue(formData.dimissorial_requested)
    formData.dimissorial_received = formatDateValue(formData.dimissorial_received)
    formData.first_contact_with = formData.first_contact_with || currentUserName

    return formData
}

export default {
    name: 'BaptismEditor',
    components: {
        AutoSaveNotice,
        FakeAttachment,
        FormBibleReferenceInput,
        FormDatePicker,
        DimissorialFormPart,
        FormTextarea,
        CheckedProcessItem,
        AttachmentList,
        FormSelectize,
        FormCheck,
        Tabs,
        Tab,
        FormInput,
        FormGroup,
        TabHeader,
        TabHeaders,
    },
    props: ['baptism', 'services', 'cities', 'pronounSets', 'attachments'],
    data() {
        const myBaptism = useForm(
            formatBaptismForForm(this.baptism, this.$page.props.currentUser.data.name),
        )
        const autoSaveDraft = getRiteDraft('baptisms', this.baptism.id)
        return {
            myDatePickerConfig: {
                locale: 'de',
                format: 'DD.MM.YYYY',
                showClear: true,
            },
            myDateTimePickerConfig: {
                locale: 'de',
                format: 'DD.MM.YYYY HH:mm',
                showClear: true,
                sideBySide: true,
            },
            myServicePickerConfig: {
                searchField: ['name'],
                allowEmptyOption: true,
                showEmptyOptionInDropdown: true,
                emptyOptionLabel: 'noch nicht gewählt (Taufanfrage)',
                optgroupField: 'category',
                optgroupLabelField: 'groupName',
                optgroupValueField: 'groupName',
                optgroups: [
                    { groupName: 'Taufgottesdienste' },
                    { groupName: 'Andere Gottesdienste' },
                ],
            },
            activeTab: 'home',
            myBaptism: myBaptism,
            autoSaveDraft,
            autoSaveDraftHandled: !autoSaveDraft,
            autoSaveTimer: null,
            autoSavePending: false,
            autoSaveQueued: false,
            autoSaveStatus: '',
            hasUnsavedChanges: false,
        }
    },
    computed: {
        autoSaveSnapshot() {
            return JSON.stringify(this.prepareBaptismForm())
        },
        hasRegistrationForm() {
            var found = false
            this.myBaptism.attachments.forEach((attachment) => {
                found = found || attachment.title == 'Anmeldeformular'
            })
            return found
        },
    },
    watch: {
        autoSaveSnapshot(snapshot) {
            this.queueAutoSave(snapshot)
        },
    },
    mounted() {
        window.addEventListener('online', this.retryAutoSave)
    },
    beforeUnmount() {
        window.removeEventListener('online', this.retryAutoSave)
        window.clearTimeout(this.autoSaveTimer)
        if (this.hasUnsavedChanges) this.persistAutoSaveDraft(this.autoSaveSnapshot)
    },
    methods: {
        persistAutoSaveDraft(snapshot) {
            const data = JSON.parse(snapshot)
            saveRiteDraft('baptisms', this.myBaptism.id, data)
            this.autoSaveDraft = { data, updatedAt: new Date().toISOString() }
        },
        queueAutoSave(snapshot) {
            if (!this.autoSaveDraftHandled) return
            this.hasUnsavedChanges = true
            this.persistAutoSaveDraft(snapshot)
            if (!window.navigator.onLine) {
                this.autoSaveStatus =
                    'Änderungen sind auf diesem Gerät gesichert und werden bei Verbindung synchronisiert.'
                return
            }
            this.autoSaveStatus = 'Änderungen werden gespeichert …'
            window.clearTimeout(this.autoSaveTimer)
            this.autoSaveTimer = window.setTimeout(() => this.syncAutoSave(snapshot), 1000)
        },
        async syncAutoSave(snapshot) {
            if (this.autoSavePending) {
                this.autoSaveQueued = true
                return
            }
            this.autoSavePending = true
            try {
                await window.axios.patch(
                    window.route('baptisms.update', { modelId: this.myBaptism.id }),
                    { ...JSON.parse(snapshot), _autosave: true },
                    { timeout: 10000 },
                )
                if (clearRiteDraft('baptisms', this.myBaptism.id, snapshot)) {
                    this.autoSaveDraft = null
                    this.hasUnsavedChanges = false
                    this.autoSaveStatus = 'Alle Änderungen gespeichert.'
                }
            } catch (error) {
                this.autoSaveStatus =
                    'Änderungen sind auf diesem Gerät gesichert; Synchronisierung fehlgeschlagen.'
                if (!error.response && window.navigator.onLine) {
                    this.autoSaveTimer = window.setTimeout(
                        () => this.syncAutoSave(this.autoSaveSnapshot),
                        5000,
                    )
                }
            } finally {
                this.autoSavePending = false
                if (this.autoSaveQueued) {
                    this.autoSaveQueued = false
                    this.syncAutoSave(this.autoSaveSnapshot)
                }
            }
        },
        retryAutoSave() {
            if (!this.autoSaveDraftHandled || !this.autoSaveDraft) return
            this.syncAutoSave(JSON.stringify(this.autoSaveDraft.data))
        },
        restoreAutoSaveDraft() {
            if (!this.autoSaveDraft) return
            this.autoSaveDraftHandled = true
            const restored = formatBaptismForForm(
                { ...this.myBaptism.data(), ...this.autoSaveDraft.data },
                this.$page.props.currentUser.data.name,
            )
            Object.keys(restored).forEach((key) => {
                if (!['id', 'attachments'].includes(key)) this.myBaptism[key] = restored[key]
            })
            this.myBaptism.clearErrors()
            this.autoSaveStatus = 'Entwurf wiederhergestellt.'
        },
        discardAutoSaveDraft() {
            if (this.autoSaveDraft) {
                clearRiteDraft(
                    'baptisms',
                    this.myBaptism.id,
                    JSON.stringify(this.autoSaveDraft.data),
                )
            }
            this.autoSaveDraft = null
            this.autoSaveDraftHandled = true
        },
        prepChecks() {
            return (
                this.myBaptism.first_contact_with &&
                this.myBaptism.first_contact_with &&
                this.myBaptism.appointment &&
                this.myBaptism.registered &&
                this.myBaptism.signed &&
                this.myBaptism.docs_ready &&
                this.myBaptism.docs_where &&
                this.myBaptism.text &&
                this.myBaptism.processed
            )
        },
        prepareBaptismForm() {
            const record = __.cloneDeep(this.myBaptism.data())
            record.dob = formatDateValue(record.dob)
            record.first_contact_on = formatDateValue(record.first_contact_on)
            record.dimissorial_requested = formatDateValue(record.dimissorial_requested)
            record.dimissorial_received = formatDateValue(record.dimissorial_received)
            record.appointment = formatDateTimeValue(record.appointment)

            return record
        },
        saveBaptism() {
            this.myBaptism
                .transform(() => this.prepareBaptismForm())
                .patch(route('baptisms.update', { modelId: this.myBaptism.id }), {
                    errorBag: 'updateBaptism',
                })
        },
        deleteBaptism() {
            if (
                !confirm(
                    'Willst du diese Taufe wirklich in den Papierkorb verschieben? Du kannst sie dort später wiederherstellen.',
                )
            )
                return
            this.$inertia.delete(route('baptisms.destroy', { modelId: this.myBaptism.id }))
        },
    },
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
</style>
