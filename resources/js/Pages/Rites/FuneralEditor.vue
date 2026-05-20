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
    <admin-layout :title="'Beerdigung von '+funeral.buried_name" :key="formKey">
        <template #navbar-left>
            <button class="btn btn-primary" @click.prevent="saveFuneral" title="Speichern">
                <span class="d-inline d-md-none mdi mdi-content-save"></span> <span
                class="d-none d-md-inline">Speichern</span>
            </button>&nbsp;
            <button class="btn btn-danger" @click.prevent="deleteFuneral" title="Löschen">
                <span class="d-inline d-md-none mdi mdi-delete"></span> <span class="d-none d-md-inline">Löschen</span>
            </button>
            <div class="dropdown show">
                <button type="button" class="btn btn-light dropdown-toggle ms-1" id="dropdownMenuLink"
                   data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Weitere Aktionen
                </button>

                <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                    <button type="button" class="dropdown-item" @click.prevent.stop="saveInLocalStorage">Datenpaket im Browser sichern</button>
                    <button type="button" class="dropdown-item" @click.prevent.stop="downloadAsJson">Datenpaket auf Festplatte sichern</button>
                </div>
            </div>
        </template>
        <template #after-flash>
            <div v-if="inLocalStorage" class="alert alert-info">
                <div>Eine Kopie dieses Datensatzes wurde im Browser zwischengespeichert. Willst du diese Kopie wiederherstellen?</div>
                <div class="pull-right">
                    <button class="btn btn-info btn-sm" @click.prevent.stop="loadFromLocalStorage">Wiederherstellen</button>
                    <button class="btn btn-danger btn-sm" @click.prevent.stop="deleteFromLocalStorage">Löschen</button>
                </div>
            </div>
        </template>
        <template #tab-headers>
            <tab-headers>
                <tab-header title="Allgemeines" id="home" :active-tab="activeTab" :is-checked-item="true"
                            :check-value="(!myFuneral.needs_dimissorial) || (myFuneral.dimissorial_received)"/>
                <tab-header title="Bestattung" id="funeral" :active-tab="activeTab"
                            :is-checked-item="true"
                            :check-value="myFuneral.text && myFuneral.announcement && myFuneral.processed"/>
                <tab-header title="Angehörige" id="family" :active-tab="activeTab"/>
                <tab-header title="Trauergespräch" id="interview" :active-tab="activeTab" :is-checked-item="true"
                            :check-value="myFuneral.appointment"/>
                <tab-header title="Dateien" id="attachments" :active-tab="activeTab"
                            :count="myFuneral.attachments.length +1"/>
            </tab-headers>
        </template>
        <tabs>
            <tab id="home" :active-tab="activeTab">
                <div class="row">
                    <div class="col-md-6">
                        <form-input name="buried_name" v-model="myFuneral.buried_name"
                                    label="Name" placeholder="Nachname, Vorname"/>
                    </div>
                    <div class="col-md-6">
                        <form-radio-group label="Zu verwendendes Pronomen"
                                          name="pronoun_set"
                                          v-model="myFuneral.pronoun_set"
                                          :items="pronounSetOptions" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <form-input name="birth_name" label="Geburtsname"
                                    help="falls abweichend vom Nachnamen" v-model="myFuneral.birth_name"/>
                    </div>
                    <div class="col-md-6">
                        <form-input label="Rufname" v-model="myFuneral.spoken_name" name="spoken_name"
                                    help="falls abweichend vom kompletten Vornamen"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <form-input label="Beruf" v-model="myFuneral.profession" name="profession"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <form-date-picker name="dob" label="Geburtsdatum"
                                          :config="myDatePickerConfig" v-model="myFuneral.dob"/>
                    </div>
                    <div class="col-md-6">
                        <form-date-picker name="dod" label="Sterbedatum"
                                          :help="relativeDate(myFuneral.dod, moment())+(age ? ' im Alter von '+age+' Jahren' : '')"
                                          :config="myDatePickerConfig" v-model="myFuneral.dod"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <form-input label="Geburtsort" v-model="myFuneral.birth_place" name="birth_place"/>
                    </div>
                    <div class="col-md-6">
                        <form-input label="Sterbeort" v-model="myFuneral.death_place" name="death_place"/>
                    </div>
                </div>
                <hr/>
                <form-input name="buried_address" v-model="myFuneral.buried_address"
                            label="Adresse" help="Straße und Hausnummer"/>
                <div class="row">
                    <div class="col-md-3">
                        <form-input name="buried_zip" v-model="myFuneral.buried_zip"
                                    label="Postleitzahl" type="number"/>
                    </div>
                    <div class="col-md-9">
                        <form-input name="buried_city" v-model="myFuneral.buried_city"
                                    label="Ort"/>
                    </div>
                </div>
                <dimissorial-form-part :parent="myFuneral"/>
            </tab>
            <tab id="funeral" :active-tab="activeTab">
                <fake-table :columns="[2,2,2,3,3]" :headers="['Datum', 'Uhrzeit', 'Ort', $page.props.labels.pastor, '']"
                            collapsed-header="Bestattung">
                    <div class="row p-1">
                        <div class="col-md-2">{{
                                moment(myFuneral.service.date).format('DD.MM.YYYY')
                            }}
                        </div>
                        <div class="col-md-2">{{ myFuneral.service.timeText }}</div>
                        <div class="col-md-2">{{ myFuneral.service.locationText }}</div>
                        <div class="col-md-3">
                            <participants :participants="myFuneral.service.pastors"></participants>
                        </div>
                        <div class="col-md-3 text-end">
                            <inertia-link :href="route('service.edit', funeral.service.slug)"
                                          title="Gottesdienst bearbeiten"
                                          class="btn btn-light">
                                <span class="mdi mdi-pencil"></span> <span
                                class="d-none d-md-inline">Gottesdienst</span>
                            </inertia-link>
                            <inertia-link :href="route('liturgy.editor', funeral.service.slug)"
                                          title="Liturgie bearbeiten"
                                          class="btn btn-light">
                                <span class="mdi mdi-view-list"></span> <span
                                class="d-none d-md-inline">Liturgie</span>
                            </inertia-link>
                            <inertia-link :href="route('service.sermon.editor', funeral.service.slug)"
                                          title="Predigt bearbeiten"
                                          class="btn btn-light">
                                <span class="mdi mdi-microphone"></span> <span
                                class="d-none d-md-inline">Predigt</span>
                            </inertia-link>
                        </div>
                    </div>
                </fake-table>
                <hr/>
                <form-bible-reference-input label="Predigttext" v-model="myFuneral.text" :is-checked-item="true"
                                            :key="referenceCopied" :sources="textSources"/>
                <button v-if="funeral.service.sermon && funeral.service.sermon.reference"
                        class="btn btn-sm btn-light"
                        :title="'Von Predigt übernehmen ('+funeral.service.sermon.reference+')'"
                        @click="setFuneralText(funeral.service.sermon.reference)">Von Predigt übernehmen
                </button>
                <form-radio-group label="Bestattungsart"
                                  name="type"
                                  v-model="myFuneral.type"
                                  :items="funeralTypeOptions"
                                  :inline="false" />
                <div v-if="myFuneral.type == 'Urnenbeisetzung'">
                    <form-date-picker label="Datum der vorhergehenden Trauerfeier" name="wake"
                                      :config="myDatePickerConfig" v-model="myFuneral.wake"/>
                    <form-input label="Ort der vorhergehenden Trauerfeier"
                                v-model="myFuneral.wake_location" name="wake_location"/>
                </div>
                <form-date-picker label="Abkündigen am" :is-checked-item="true"
                                  name="announcement" :config="myDatePickerConfig"
                                  v-model="myFuneral.announcement"/>
                <hr/>
                <form-textarea label="Bestatter" v-model="myFuneral.undertaker" name="undertaker"/>
                <form-textarea label="Nachrufe" v-model="myFuneral.eulogies" name="eulogies"/>
                <form-textarea label="Notizen" v-model="myFuneral.notes" name="notes"/>
                <form-check name="processed" label="Kirchenbucheintrag abgeschlossen"
                            v-model="myFuneral.processed" is-checked-item/>
            </tab>
            <tab id="family" :active-tab="activeTab">
                <form-input name="relative_name" v-model="myFuneral.relative_name"
                            label="Name" placeholder="Nachname, Vorname"/>
                <div class="mb-3">
                    <button @click.prevent="copyAddress" class="btn btn-light">
                        <span class="mdi mdi-content-copy"></span> Adresse übernehmen
                    </button>
                </div>
                <form-input v-model="myFuneral.relative_address" name="relative_address" :key="copied"
                            label="Adresse" help="Straße und Hausnummer"/>
                <div class="row">
                    <div class="col-md-3">
                        <form-input v-model="myFuneral.relative_zip" name="relative_zip" :key="copied"
                                    label="Postleitzahl" type="number"/>
                    </div>
                    <div class="col-md-9">
                        <form-input v-model="myFuneral.relative_city" name="relative_city" :key="copied"
                                    label="Ort"/>
                    </div>
                </div>
                <form-textarea label="Weitere Kontaktdaten" v-model="myFuneral.relative_contact_data"
                               name="relative_contact_data"/>
            </tab>
            <tab id="interview" :active-tab="activeTab">
                <div class="row">
                    <div class="col-md-6">
                        <form-date-picker label="Trauergespräch" :is-checked-item="true"
                                          name="appointment" :config="myDateTimePickerConfig"
                                          v-model="myFuneral.appointment"/>
                    </div>
                    <div class="col-md-6">
                        <form-input label="Ort des Trauergesprächs" name="appointment_address"
                                    :key="appointmentPlaceCopied"
                                    v-model="myFuneral.appointment_address"/>
                        <button class="btn btn-sm btn-light" @click="copyBuriedAddress">Von Adresse übernehmen</button>
                        <button class="btn btn-sm btn-light" @click="copyRelativeAddress">Von Angehörigen übernehmen
                        </button>
                    </div>
                </div>
                <form-textarea label="Anwesende" v-model="myFuneral.attending" name="attending"/>
                <hr/>
                <div v-if="imageAttachments.length > 0" class="d-none d-md-block">
                    <label>Angehängte Bilder</label>
                    <div class="row">
                        <div class="col-md-3 p-2" v-for="(image,imageIndex,imageKey) in imageAttachments"
                             :key="imageKey">
                            <attachment :attachment="image"/>
                        </div>
                    </div>
                    <hr/>
                </div>
                <div class="row">
                    <div class="col-12 text-end">
                        <button v-if="!showStoryEditor" class="btn btn-light" @click="showStoryEditor = true">
                            <span class="mdi mdi-chevron-left"></span> Editor für Lebenslauf einblenden
                        </button>
                        <button v-else class="btn btn-light" @click="showStoryEditor = false">
                            <span class="mdi mdi-chevron-right"></span> Editor für Lebenslauf ausblenden
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div :class="showStoryEditor ? 'col-lg-4': 'col-md-6'">
                        <form-textarea label="Eltern, Herkunftsfamilie" v-model="myFuneral.parents"
                                       name="parents"/>
                        <accordion id="casesAccordion">
                            <accordion-element title="Taufe" icon="mdi mdi-water">
                                <form-textarea label="Taufe" v-model="myFuneral.baptism" name="baptism"/>
                                <form-date-picker label="Taufdatum" v-model="myFuneral.baptism_date" name="baptism_date"
                                                  :help="ageText(myFuneral.baptism_date, myFuneral.dob, 'mit ', 'n')"/>
                            </accordion-element>
                            <accordion-element title="Konfirmation" icon="mdi mdi-cross-outline">
                                <form-textarea label="Konfirmation" v-model="myFuneral.confirmation" name="confirmation"/>
                                <form-date-picker label="Datum der Konfirmation" v-model="myFuneral.confirmation_date"
                                                  name="confirmation_date"
                                                  :help="ageText(myFuneral.confirmation_date, myFuneral.dob, 'mit ', 'n')"/>
                                <form-bible-reference-input label="Denkspruch" v-model="myFuneral.confirmation_text"
                                                            name="confirmation_text"/>
                            </accordion-element>
                            <accordion-element title="Heirat" icon="mdi mdi-ring">
                                <form-textarea label="Ehepartner:in" v-model="myFuneral.spouse" name="spouse"/>
                                <form-date-picker label="Heiratsdatum" v-model="myFuneral.wedding_date"
                                                  name="wedding_date"
                                                  :help="myFuneral.wedding_date ? ageText(myFuneral.dod_spouse || myFuneral.dod, myFuneral.wedding_date, '', ' verheiratet') : ''"/>
                                <form-bible-reference-input label="Trauspruch" v-model="myFuneral.wedding_text"
                                                            name="wedding_text"/>
                                <form-date-picker label="Sterbedatum Ehepartner:in" v-model="myFuneral.dod_spouse"
                                                  name="dod_spouse"
                                                  :help="myFuneral.dod_spouse ? ageText(moment().format('DD.MM.YYYY'), myFuneral.dod_spouse, 'vor ', 'n') : ''"/>
                            </accordion-element>
                            <accordion-element title="Familie" icon="mdi mdi-human-male-female-child">
                                <form-textarea label="Kinder" v-model="myFuneral.children" name="children"/>
                                <form-textarea label="Weitere Hinterbliebene" v-model="myFuneral.further_family"
                                               name="further_family"/>
                            </accordion-element>
                        </accordion>


                        <hr/>
                    </div>
                    <div :class="showStoryEditor ? 'col-lg-4': 'col-md-6'">
                        <form-textarea label="Kindheit, Jugend" v-model="myFuneral.childhood" name="childhood"/>
                        <form-input label="Beruf" v-model="myFuneral.profession" name="profession"/>
                        <form-textarea label="Ausbildung, Beruf" v-model="myFuneral.professional_life"
                                       name="professional_life"/>
                        <form-textarea label="Heirat, Familie" v-model="myFuneral.family" name="family"/>
                        <form-textarea label="Weiterer Lebenslauf" v-model="myFuneral.further_life"
                                       name="further_life"/>
                        <form-textarea label="Lebensende" v-model="myFuneral.death" name="death" :help="endOfLifeText"/>
                        <hr/>
                        <form-textarea label="Prägende Erlebnisse, Hobbies, Interessen"
                                       v-model="myFuneral.events" name="events"/>
                        <form-textarea label="Charakter" v-model="myFuneral.character" name="character"/>
                        <form-textarea label="Glaube, Frömmigkeit, Kirche" v-model="myFuneral.faith"
                                       name="faith"/>
                        <hr/>
                        <form-textarea label="Zitate" v-model="myFuneral.quotes" name="quotes"/>
                    </div>
                    <div v-if="showStoryEditor" class="col-lg-4">
                        <div class="form-group">
                            <label>Lebenslauf</label>
                            <div class="tiptap-toolbar btn-toolbar mb-1" v-if="editorText">
                                <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleBold().run()" :class="{active: editorText.isActive('bold')}" title="Fett"><b>B</b></button>
                                <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleItalic().run()" :class="{active: editorText.isActive('italic')}" title="Kursiv"><i>I</i></button>
                                <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().toggleUnderline().run()" :class="{active: editorText.isActive('underline')}" title="Unterstrichen"><u>U</u></button>
                                <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleHeading({level:1}).run()" :class="{active: editorText.isActive('heading',{level:1})}" title="Überschrift">H1</button>
                                <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().toggleBlockquote().run()" :class="{active: editorText.isActive('blockquote')}" title="Zitat">&ldquo;</button>
                                <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleOrderedList().run()" :class="{active: editorText.isActive('orderedList')}" title="Nummerierte Liste">1.</button>
                                <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().toggleBulletList().run()" :class="{active: editorText.isActive('bulletList')}" title="Aufzählung">&bull;</button>
                                <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().unsetAllMarks().clearNodes().run()" title="Formatierung entfernen">&#10005;</button>
                                <quill-dropdown :key="funeral.id"
                                                label="Texte"
                                                :title="'Textbausteine zur Beerdigung von '+funeral.buried_name"
                                                icon="mdi mdi-grave-stone" :items="funeralDataset"
                                                @input="insertText($event)"/>
                            </div>
                            <editor-content :editor="editorText" class="form-control tiptap-editor" />
                            <text-stats :text="myFuneral.life"/>
                        </div>

                    </div>
                </div>
            </tab>
            <tab id="attachments" :active-tab="activeTab">
                <h3>Angehängte Dateien</h3>
                <attachment-list v-model="myFuneral.attachments" delete-route-name="funeral.detach"
                                 :parent-object="myFuneral" parent-type="funeral" :prevent-empty-list-message="true"
                                 :key="myFuneral.attachments.length"/>
                <fake-attachment :href="route('funeral.form', {funeral: this.myFuneral.id})"
                                 title="Formular für Kirchenregisteramt" extension="pdf"
                                 icon="mdi mdi-file-pdf-box" size="ca. 135 kB"/>

                <hr/>
                <h3>Dateien hinzufügen.</h3>
                <form-file-uploader :parent="myFuneral"
                                    :upload-route="route('funeral.attach', this.myFuneral.id)"
                                    v-model="myFuneral.attachments"/>
            </tab>
        </tabs>
    </admin-layout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';
import TabHeaders from "../../components/Ui/tabs/tabHeaders";
import TabHeader from "../../components/Ui/tabs/tabHeader";
import Tabs from "../../components/Ui/tabs/tabs";
import Tab from "../../components/Ui/tabs/tab";
import FormInput from "../../components/Ui/forms/FormInput";
import FormGroup from "../../components/Ui/forms/FormGroup";
import BasicInfo from "../../components/Service/BasicInfo";
import FakeTable from "../../components/Ui/FakeTable";
import FormTextarea from "../../components/Ui/forms/FormTextarea";
import FormFileUploader from "../../components/Ui/forms/FormFileUploader";
import Attachment from "../../components/Ui/elements/Attachment";
import AttachmentList from "../../components/Ui/elements/AttachmentList";
import ValueCheck from "../../components/Ui/elements/ValueCheck";
import Participants from "../../components/Calendar/Service/Participants";
import FakeAttachment from "../../components/Ui/elements/FakeAttachment";
import RelativeDate from "@pfarr.tools/relative-date";
import FormCheck from "../../components/Ui/forms/FormCheck";
import DimissorialFormPart from "../../components/RiteEditors/DimissorialFormPart";
import TextStats from "../../components/LiturgyEditor/Elements/TextStats";
import FormDatePicker from "../../components/Ui/forms/FormDatePicker";
import NavButton from "../../components/Ui/buttons/NavButton";
import FormBibleReferenceInput from "../../components/Ui/forms/FormBibleReferenceInput";
import FormRadioGroup from "../../components/Ui/forms/FormRadioGroup.vue";
import __ from 'lodash';
import Accordion from "../../components/Ui/accordion/Accordion";
import AccordionElement from "../../components/Ui/accordion/AccordionElement";
import QuillDropdown from "../../components/LiturgyEditor/Editors/Quill/QuillDropdown.vue";


function formatDateValue(value) {
    if (!value) return value;
    if ((typeof value === 'string') && moment(value, 'DD.MM.YYYY', true).isValid()) return value;

    const isoValue = moment(value, moment.ISO_8601, true);
    return isoValue.isValid() ? isoValue.format('DD.MM.YYYY') : value;
}

function formatDateTimeValue(value) {
    if (!value) return value;
    if ((typeof value === 'string') && moment(value, 'DD.MM.YYYY HH:mm', true).isValid()) return value;

    const isoValue = moment(value, moment.ISO_8601, true);
    return isoValue.isValid() ? isoValue.format('DD.MM.YYYY HH:mm') : value;
}

function formatFuneralForForm(funeral) {
    const formData = __.cloneDeep(funeral);

    formData.life = formData.life || '';
    formData.attachments = formData.attachments || [];

    [
        'dob',
        'dod',
        'announcement',
        'wake',
        'dimissorial_requested',
        'dimissorial_received',
        'baptism_date',
        'confirmation_date',
        'wedding_date',
        'dod_spouse',
    ].forEach((key) => {
        formData[key] = formatDateValue(formData[key]);
    });

    formData.appointment = formatDateTimeValue(formData.appointment);

    return formData;
}

export default {
    name: "FuneralEditor",
    components: {
        AccordionElement,
        Accordion,
        FormBibleReferenceInput,
        FormRadioGroup,
        NavButton,
        FormDatePicker,
        TextStats,
        DimissorialFormPart,
        FormCheck,
        FakeAttachment,
        Participants,
        EditorContent,
        ValueCheck,
        AttachmentList,
        Attachment,
        FormFileUploader,
        FormTextarea,
        FakeTable,
        BasicInfo, FormGroup, FormInput, TabHeader, TabHeaders, Tabs, Tab,
        QuillDropdown,
    },
    props: ['funeral', 'pronounSets', 'activeTab'],
    computed: {
        pronounSetOptions() {
            return this.pronounSets.reduce((result, pronounSet) => {
                result[pronounSet.key] = pronounSet.label;
                return result;
            }, {});
        },
        funeralTypeOptions() {
            return {
                'Erdbestattung': 'Erdbestattung',
                'Trauerfeier': 'Trauerfeier',
                'Trauerfeier mit Urnenbeisetzung': 'Trauerfeier mit Urnenbeisetzung',
                'Urnenbeisetzung': 'Urnenbeisetzung',
            };
        },
        age() {
            if ((!this.myFuneral.dod) || (!this.myFuneral.dob)) return null;
            return moment(this.myFuneral.dod, 'DD.MM.YYYY').diff(moment(this.myFuneral.dob, 'DD.MM.YYYY'), 'years');
        },
        imageAttachments() {
            let images = [];
            this.myFuneral.attachments.forEach(attachment => {
                if (attachment.mimeType.substr(0, 6) == 'image/') images.push(attachment);
            });
            return images;
        },
        endOfLifeText() {
            let parts = [];
            let dod = this.myFuneral.dod ? this.dateFromString(this.myFuneral.dod) : null;
            let dob = this.myFuneral.dob ? this.dateFromString(this.myFuneral.dob) : null;
            if (this.myFuneral.dod) parts.push(dod.locale('de').format('dddd, DD.MM.YYYY'));
            if (this.myFuneral.death_place) parts.push(this.myFuneral.death_place);
            if (this.myFuneral.dod) parts.push(String(this.age) + ' Jahre alt');
            if (this.myFuneral.dob && this.myFuneral.dod) parts.push(dod.diff(dob, 'days').toLocaleString('de-DE') + ' Lebenstage');
            if (parts.length == 0) return '';
            return 'Gestorben: ' + parts.join(', ');
        },
        textSources() {
            let sources = {};
            if (this.myFuneral.service.sermon && this.myFuneral.service.sermon.reference) sources['Predigttext'] = this.myFuneral.service.sermon.reference;
            if (this.myFuneral.confirmation_text) sources['Denkspruch'] = this.myFuneral.confirmation_text;
            if (this.myFuneral.wedding_text) sources['Trauspruch'] = this.myFuneral.wedding_text;
            return sources;
        },
    },
    mounted() {
        this.refreshKey++;
        this.$forceUpdate();
    },
    data() {
        const myFuneral = useForm(formatFuneralForForm(this.funeral));

        let ls = this.getLocalStorage();
        let inLocalStorage = (undefined !== ls.funerals[this.funeral.id]);

        return {
            funeralDataset: {
                'Geburtsdatum': 'dob',
                'Sterbedatum': 'dod',
                'Geburtsort': 'birth_place',
                'Sterbeort': 'death_place',
                'Geburtsname': 'birth_name',
                'Rufname': 'spoken_name',
                'Sterbealter': 'age',
            },
            formKey: 0,
            referenceCopied: 0,
            myDatePickerConfig: {
                locale: 'de',
                format: 'DD.MM.YYYY',
                showClear: true,
            },
            myDateTimePickerConfig: {
                locale: 'de',
                format: 'DD.MM.YYYY HH:mm',
                showClear: true,
            },
            showStoryEditor: false,
            myFuneral: myFuneral,
            copied: 0,
            appointmentPlaceCopied: 0,
            editorText: new Editor({
                content: myFuneral.life || '',
                extensions: [
                    StarterKit,
                    Underline,
                    Placeholder.configure({ placeholder: 'Hier kannst du einen Textentwurf für den Lebenslauf schreiben...' }),
                ],
                onUpdate: ({ editor }) => { myFuneral.life = editor.getHTML(); },
            }),
            inLocalStorage,
        }
    },
    beforeUnmount() {
        this.editorText.destroy();
    },
    methods: {
        copyAddress() {
            this.myFuneral.relative_address = this.myFuneral.buried_address;
            this.myFuneral.relative_zip = this.myFuneral.buried_zip;
            this.myFuneral.relative_city = this.myFuneral.buried_city;
            this.copied++;
            this.$forceUpdate();
        },
        copyBuriedAddress() {
            this.myFuneral.appointment_address = this.myFuneral.buried_address + ', ' + this.myFuneral.buried_zip + ' ' + this.myFuneral.buried_city;
            this.appointmentPlaceCopied++;
            this.$forceUpdate();
        },
        copyRelativeAddress() {
            this.myFuneral.appointment_address = this.myFuneral.relative_address + ', ' + this.myFuneral.relative_zip + ' ' + this.myFuneral.relative_city;
            this.appointmentPlaceCopied++;
            this.$forceUpdate();
        },
        prepareFuneralForm() {
            const record = __.cloneDeep(this.myFuneral.data());

            [
                'dob',
                'dod',
                'announcement',
                'wake',
                'dimissorial_requested',
                'dimissorial_received',
                'baptism_date',
                'confirmation_date',
                'wedding_date',
                'dod_spouse',
            ].forEach((key) => {
                record[key] = formatDateValue(record[key]);
            });
            record.appointment = formatDateTimeValue(record.appointment);

            return record;
        },
        saveFuneral() {
            this.myFuneral.transform(() => this.prepareFuneralForm()).patch(route('funerals.update', {modelId: this.myFuneral.id}), {
                errorBag: 'updateFuneral',
            });
        },
        deleteFuneral() {
            if (!confirm('Willst du diese Beerdigung wirklich unwiderruflich löschen?')) return;
            this.myFuneral.delete(route('funerals.destroy', {modelId: this.myFuneral.id}), {
                preserveState: false,
            })
        },
        downloadForm() {
            window.location.href = route('funeral.form', {funeral: this.myFuneral.id});
        },
        relativeDate: RelativeDate,
        insertText(e) {
            var text = null;
            switch (e) {
                case 'dob':
                    text = moment(this.myFuneral.dob, 'DD.MM.YYYY').locale('de').format('LL');
                    break;
                case 'dod':
                    text = moment(this.myFuneral.dod, 'DD.MM.YYYY').locale('de').format('LL');
                    break;
                case 'age':
                    text = this.age.toString();
                    break;
                default:
                    if (this.myFuneral[e]) text = this.myFuneral[e];
            }
            if (text) this.editorText.chain().focus().insertContent(text + ' ').run();
        },
        setFuneralText(t) {
            this.myFuneral.text = t;
            this.referenceCopied++;
        },
        dateFromString(s) {
            if (!s) return false;
            if (s.length != 10) return moment(s);
            return moment(s.substr(6, 4) + '-' + s.substr(3, 2) + '-' + s.substr(0, 2));
        },
        ageText(date, startDate, prefix, suffix) {
            startDate = this.dateFromString(startDate || this.myFuneral.dob);
            date = this.dateFromString(date);
            if (!(startDate && date)) return '';
            suffix = suffix || '';
            let dayDiff = date.diff(startDate, 'days');
            if (dayDiff < 14) return prefix + String(dayDiff) + ' Tage' + suffix;
            if (dayDiff < 60) return prefix + 'ca. ' + String(Math.floor(dayDiff / 7)) + ' Wochen';
            if (dayDiff < 365) return prefix + 'ca. ' + String(date.diff(startDate, 'months')) + ' Monate' + suffix;
            return prefix + String(date.diff(startDate, 'years')) + ' Jahre' + suffix;
        },
        downloadAsJson(exportObj, exportName) {
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(this.myFuneral.data()));
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", exportName + ".json");
            document.body.appendChild(downloadAnchorNode); // required for firefox
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        },
        saveInLocalStorage() {
            let ls = this.getLocalStorage();
            ls.funerals[this.myFuneral.id] = this.myFuneral.data();
            localStorage.pfarrplaner = JSON.stringify(ls);
            this.inLocalStorage = true;
        },
        applyFormData(source) {
            const restored = formatFuneralForForm(source);
            Object.keys(restored).forEach((key) => {
                this.myFuneral[key] = restored[key];
            });
            this.myFuneral.clearErrors();
            this.editorText.commands.setContent(this.myFuneral.life || '', false);
        },
        loadFromLocalStorage() {
            let ls = this.getLocalStorage();
            if (ls.funerals[this.myFuneral.id]) {
                this.applyFormData(ls.funerals[this.myFuneral.id]);
                this.formKey++;
                this.$forceUpdate();
                this.deleteFromLocalStorage();
            }
        },
        deleteFromLocalStorage() {
            let ls = this.getLocalStorage();
            if (ls.funerals[this.myFuneral.id]) delete ls.funerals[this.myFuneral.id];
            localStorage.pfarrplaner = JSON.stringify(ls);
            this.inLocalStorage = false;
        },
        getLocalStorage() {
            let ls = JSON.parse(localStorage.getItem('pfarrplaner')) || {};
            ls.funerals = ls.funerals || {};
            return ls;
        }
    }
}
</script>

<style scoped>

.attachment {
    width: 100%;
    text-align: left;
    margin-bottom: .25rem;
    vertical-align: middle;
}

.mdi-download {
    margin-right: 20px;
    color: gray !important;
}

:deep(.ql-container.ql-snow),
:deep(.ql-container.ql-snow .ql-editor) {
    font-family: inherit !important;
    font-weight: normal;
}



</style>
