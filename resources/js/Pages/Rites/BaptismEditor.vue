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
    <admin-layout :title="'Taufe von '+baptism.candidate_name">
        <template #navbar-left>
            <button class="btn btn-primary" @click.prevent="saveBaptism" title="Speichern">
                <span class="d-inline d-md-none mdi mdi-content-save"></span> <span class="d-none d-md-inline">Speichern</span>
            </button>&nbsp;
            <button v-if="myBaptism.id" class="btn btn-danger" @click.prevent="deleteBaptism" title="Löschen">
                <span class="d-inline d-md-none mdi mdi-delete"></span> <span class="d-none d-md-inline">Löschen</span>
            </button>
        </template>
        <template #tab-headers>
            <tab-headers>
                <tab-header title="Allgemeines" id="home" :active-tab="activeTab" :is-checked-item="true"
                            :check-value="(!myBaptism.needs_dimissorial) || (myBaptism.dimissorial_received)"/>
                <tab-header title="Vorbereitung" id="prep" :active-tab="activeTab" :is-checked-item="true"
                            :check-value="prepChecks()"/>
                <tab-header title="Dateien" id="attachments" :active-tab="activeTab"
                            :count="myBaptism.attachments.length+Object.keys(attachments).length"/>
            </tab-headers>
        </template>
        <tabs>
            <tab id="home" :active-tab="activeTab">
                <fieldset>
                    <legend>Gottesdienst</legend>
                    <form-selectize label="Taufgottesdienst"
                                    name="service_id"
                                    :options="services"
                                    id-key="id" title-key="name"
                                    help="Leer lassen, um dies als Taufanfrage einzuordnen"
                                    :settings="myServicePickerConfig"
                                    v-model="myBaptism.service_id"/>
                    <form-selectize label="Kirchengemeinde" :options="cities" v-model="myBaptism.city_id"
                                    id-key="id" title-key="name"
                                    name="city_id"/>
                </fieldset>
                <fieldset>
                    <legend>Täufling</legend>
                    <div class="row">
                        <div class="col-md-6">
                            <form-input name="candidate_name"
                                        label="Name des Täuflings" v-model="myBaptism.candidate_name"/>
                        </div>
                        <div class="col-md-6">
                            <form-group label="Zu verwendendes Pronomen">
                                <div>
                                    <div class="form-check-inline"
                                         v-for="(pronounSet, pronounSetIndex) in pronounSets"
                                         :key="'pronouns_'+pronounSet.key">
                                        <label class="form-check-label">
                                            <input type="radio" class="form-check-input"
                                                   v-model="myBaptism.pronoun_set"
                                                   :value="pronounSet.key">{{ pronounSet.label }}
                                        </label>
                                    </div>
                                </div>
                            </form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <form-date-picker label="Geburtsdatum" name="dob"
                                              v-model="myBaptism.dob" :config="myDatePickerConfig"/>
                        </div>
                        <div class="col-md-6">
                            <form-input label="Geburtsort" name="birth_place"
                                        v-model="myBaptism.birth_place" />
                        </div>
                    </div>
                    <form-input label="Adresse" v-model="myBaptism.candidate_address"/>
                    <div class="row">
                        <div class="col-md-6">
                            <form-input name="candidate_zip" label="PLZ" v-model="myBaptism.candidate_zip"/>
                        </div>
                        <div class="col-md-6">
                            <form-input name="candidate_city" label="Ort" v-model="myBaptism.candidate_city"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <form-input name="candidate_phone" label="Telefon" v-model="myBaptism.candidate_phone"/>
                        </div>
                        <div class="col-md-6">
                            <form-input name="candidate_email" label="E-Mailadresse" v-model="myBaptism.candidate_email"
                                        type="email"/>
                        </div>
                    </div>
                </fieldset>
                <dimissorial-form-part :parent="myBaptism"/>
            </tab>
            <tab id="prep" :active-tab="activeTab">
                <fieldset id="fsPrep">
                    <legend>Erstkontakt</legend>
                    <div class="row">
                        <div class="col-md-4">
                            <form-input name="first_contact_with"
                                        label="Erstkontakt mit" v-model="myBaptism.first_contact_with"
                                        is-checked-item="1"/>
                        </div>
                        <div class="col-md-4">
                            <form-date-picker label="Datum" :is-checked-item="true" name="first_contact_on"
                                              v-model="myBaptism.first_contact_on" :config="myDatePickerConfig"/>
                        </div>
                        <div class="col-md-4">
                            <form-date-picker label="Taufgespräch" :is-checked-item="true" name="appointment"
                                              v-model="myBaptism.appointment" :config="myDateTimePickerConfig"/>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Wichtige Informationen</legend>
                    <form-bible-reference-input name="text" label="Taufspruch" v-model="myBaptism.text" is-checked-item="1"
                        :sources="{}"/>
                    <form-textarea name="notes" label="Notizen aus dem Taufgespräch" v-model="myBaptism.notes"/>
                </fieldset>
                <fieldset>
                    <legend>Unterlagen</legend>
                    <div v-if="!hasRegistrationForm" class="mb-3">
                        <p>Wenn das Anmeldeformular in AHAS Online erstellt wurde, kannst du es hier hochladen:</p>
                        <form-file-uploader :parent="myBaptism"
                                            :upload-route="route('baptism.attach', this.myBaptism.id)"
                                            title="Anmeldeformular"
                                            v-model="myBaptism.attachments"/>
                    </div>
                    <div v-else>
                        <checked-process-item check="1"
                                              positive="Anmeldedaten aufgenommen und Anmeldeformular erstellt"/>
                        <form-check label="Anmeldung unterschrieben" v-model="myBaptism.signed" name="signed"
                                    is-checked-item="1"/>
                        <form-check label="Urkunden erstellt" v-model="myBaptism.docs_ready" name="ready"
                                    is-checked-item="1"/>
                        <form-input label="Urkunden hinterlegt" v-model="myBaptism.docs_where" name="docs_where"
                                    class="mt-3"
                                    help="Wo sind die Unterlagen hinterlegt?"
                                    is-checked-item="1"/>
                    </div>
                    <form-check name="processed" label="Kirchenbucheintrag abgeschlossen" v-model="myBaptism.processed"
                                is-checked-item/>
                </fieldset>
            </tab>
            <tab id="attachments" :active-tab="activeTab">
                <fieldset>
                    <legend>Angehängte Dateien</legend>
                    <fake-attachment v-for="(attachment,attachmentIndex) in attachments" :title="attachment.title" :icon="attachment.icon"
                                     :description="attachment.description" :extension="attachment.extension"
                                     :key="'_auto_attachment_'+attachmentIndex"
                                     :use-inertia="attachment.hasSetup"
                                     :href="route(
                                         attachment.hasSetup ? 'auto-attachment.setup' : 'auto-attachment',
                                         {type: 'baptism', attachment: attachment.key, attachable:myBaptism.id})" />
                    <attachment-list v-model="myBaptism.attachments" delete-route-name="baptism.detach"
                                     :parent-object="myBaptism" parent-type="baptism"
                                     :key="myBaptism.attachments.length"/>
                </fieldset>
                <fieldset>
                    <legend>Dateien hinzufügen</legend>
                    <form-file-uploader :parent="myBaptism"
                                        :upload-route="route('baptism.attach', this.myBaptism.id)"
                                        v-model="myBaptism.attachments"/>
                    <div class="mt-2"><small>Das Anmeldeformular zur Taufe bitte nicht hier, sondern im Register
                        "Vorbereitung" hochladen.</small></div>
                </fieldset>
            </tab>
        </tabs>
    </admin-layout>

</template>

<script>
import { useForm } from "@inertiajs/vue3";
import __ from 'lodash';
import TabHeaders from "../../components/Ui/tabs/tabHeaders";
import TabHeader from "../../components/Ui/tabs/tabHeader";
import FormGroup from "../../components/Ui/forms/FormGroup";
import FormInput from "../../components/Ui/forms/FormInput";
import Tab from "../../components/Ui/tabs/tab";
import Tabs from "../../components/Ui/tabs/tabs";
import FormCheck from "../../components/Ui/forms/FormCheck";
import FormSelectize from "../../components/Ui/forms/FormSelectize";
import AttachmentList from "../../components/Ui/elements/AttachmentList";
import FormFileUploader from "../../components/Ui/forms/FormFileUploader";
import CheckedProcessItem from "../../components/Ui/elements/CheckedProcessItem";
import FormTextarea from "../../components/Ui/forms/FormTextarea";
import DimissorialFormPart from "../../components/RiteEditors/DimissorialFormPart";
import FormBibleReferenceInput from "../../components/Ui/forms/FormBibleReferenceInput";
import FakeAttachment from "../../components/Ui/elements/FakeAttachment.vue";
import FormDatePicker from "../../components/Ui/forms/FormDatePicker";

function formatDateValue(value) {
    if (!value) return value;
    if ((typeof value === 'string') && moment(value, 'DD.MM.YYYY', true).isValid()) return value;
    return moment(value).format('DD.MM.YYYY');
}

function formatDateTimeValue(value) {
    if (!value) return value;
    if ((typeof value === 'string') && moment(value, 'DD.MM.YYYY HH:mm', true).isValid()) return value;
    return moment(value).format('DD.MM.YYYY HH:mm');
}

function formatBaptismForForm(baptism, currentUserName) {
    const formData = __.cloneDeep(baptism);
    formData.attachments = formData.attachments || [];
    formData.dob = formatDateValue(formData.dob);
    formData.first_contact_on = formData.first_contact_on ? formatDateValue(formData.first_contact_on) : moment().format('DD.MM.YYYY');
    formData.appointment = formatDateTimeValue(formData.appointment);
    formData.dimissorial_requested = formatDateValue(formData.dimissorial_requested);
    formData.dimissorial_received = formatDateValue(formData.dimissorial_received);
    formData.first_contact_with = formData.first_contact_with || currentUserName;

    return formData;
}

export default {
    name: "BaptismEditor",
    components: {
        FakeAttachment,
        FormBibleReferenceInput,
        FormDatePicker,
        DimissorialFormPart,
        FormTextarea,
        CheckedProcessItem,
        FormFileUploader,
        AttachmentList,
        FormSelectize,
        FormCheck, Tabs, Tab, FormInput, FormGroup, TabHeader, TabHeaders,
    },
    props: ['baptism', 'services', 'cities', 'pronounSets', 'attachments'],
    data() {
        const myBaptism = useForm(formatBaptismForForm(this.baptism, this.$page.props.currentUser.data.name));
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
                optgroups: [{groupName: 'Taufgottesdienste'}, {groupName: 'Andere Gottesdienste'}],
            },
            activeTab: 'home',
            myBaptism: myBaptism,
        }
    },
    computed: {
        hasRegistrationForm() {
            var found = false;
            this.myBaptism.attachments.forEach(attachment => {
                found = found || (attachment.title == 'Anmeldeformular');
            });
            return found;
        },
    },
    methods: {
        prepChecks() {
            return (this.myBaptism.first_contact_with)
                && (this.myBaptism.first_contact_with)
                && (this.myBaptism.appointment)
                && (this.myBaptism.registered)
                && (this.myBaptism.signed)
                && (this.myBaptism.docs_ready)
                && (this.myBaptism.docs_where)
                && (this.myBaptism.text)
                && (this.myBaptism.processed);
        },
        prepareBaptismForm() {
            const record = __.cloneDeep(this.myBaptism.data());
            record.dob = formatDateValue(record.dob);
            record.first_contact_on = formatDateValue(record.first_contact_on);
            record.dimissorial_requested = formatDateValue(record.dimissorial_requested);
            record.dimissorial_received = formatDateValue(record.dimissorial_received);
            record.appointment = formatDateTimeValue(record.appointment);

            return record;
        },
        saveBaptism() {
            this.myBaptism.transform(() => this.prepareBaptismForm()).patch(route('baptisms.update', {modelId: this.myBaptism.id}), {
                errorBag: 'updateBaptism',
            });
        },
        deleteBaptism() {
            if (!confirm('Willst du diese Taufe wirklich in den Papierkorb verschieben? Du kannst sie dort später wiederherstellen.')) return;
            this.$inertia.delete(route('baptisms.destroy', {modelId: this.myBaptism.id}));
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

</style>
