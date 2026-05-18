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
    <div class="sermon-editor h-100">
        <admin-layout title="Predigt bearbeiten" no-content-scroll>
            <template #navbar-left>
                <button class="btn btn-primary" @click.prevent="saveSermon">Speichern</button>&nbsp;
                <a class="btn btn-light" v-if="editedSermon.id"
                              :href="route('sermon.reader', {sermon: editedSermon.id})" target="_blank"
                              title="Zur Leseansicht">
                    <span class="mdi mdi-text-box-outline"></span> Zur Leseansicht
                </a>
            </template>
            <template #navbar-right>
                <div class="btn-group calendar-mode-toggle" role="group" aria-label="Ansicht umschalten" v-if="service.isEditable">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false" title="Gottesdienst bearbeiten">
                            <span class="mdi mdi-pencil me-1"></span>
                            <span class="d-none d-xl-inline">Bearbeiten</span>
                            <span class="ms-1 badge badge-primary" v-if="services.length">{{ services.length }}</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li v-for="svc in services" :key="svc.slug">
                                <inertia-link class="dropdown-item" :href="route('service.edit', {service: svc.slug})">
                                    {{ svc.titleText }}, {{ moment(svc.date).format('DD.MM.YYYY') }}, {{ svc.locationText }}
                                </inertia-link>
                            </li>
                        </ul>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false" title="Liturgie bearbeiten">
                            <span class="mdi mdi-view-list me-1"></span>
                            <span class="d-none d-xl-inline">Liturgie</span>
                            <span class="ms-1 badge badge-primary" v-if="services.length">{{ services.length }}</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li v-for="svc in services" :key="svc.slug">
                                <inertia-link class="dropdown-item" :href="route('liturgy.editor', {service: svc.slug})">
                                    {{ svc.titleText }}, {{ moment(svc.date).format('DD.MM.YYYY') }}, {{ svc.locationText }}
                                </inertia-link>
                            </li>
                        </ul>
                    </div>
                    <button class="btn btn-secondary" href="#">
                        <span class="mdi mdi-microphone me-1"></span>
                        <span class="d-none d-xl-inline">Predigt</span>
                    </button>
                </div>
            </template>
            <form @submit.prevent="saveSermon" id="formSermon" class="sermon-editor__form">
                <div class="row g-3 sermon-editor__layout">
                    <div class="col-md-4 d-flex flex-column min-h-0 sermon-editor__column">
                        <div class="overflow-auto min-h-0 h-100 ">
                                <card class="shadow-sm sermon-editor__card">
                                <div class="card-header bg-white border-bottom">
                                    Predigtinformationen
                                </div>
                                <card-body class="sermon-editor__sidebar">
                                    <form-input id="sermonTitle" label="Titel" v-model="editedSermon.title" />
                                    <form-input id="sermonSubtitle" label="Untertitel" v-model="editedSermon.subtitle"/>
                                    <form-input id="sermonSeries" label="Reihe" v-model="editedSermon.series"/>
                                    <form-textarea id="sermonSummary" label="Zusammenfassung" v-model="editedSermon.summary" class="mb-2"/>
                                    <form-bible-reference-input name="reference" :key="referenceCopied"
                                                                label="Predigttext"
                                                                v-model="editedSermon.reference" :sources="textSources" />
                                    <div v-if="!(editedSermon.id)" class="alert alert-info">
                                        Du musst die Predigt erst einmal speichern, um ein Bild hinzufügen zu können.
                                    </div>
                                    <div v-else>
                                        <form-image-attacher
                                            :attach-route="route('sermon.image.attach', {model: editedSermon.id})"
                                            :detach-route="route('sermon.image.detach', {model: editedSermon.id})"
                                            label="Bild zur Predigt" :handle-paste="true"
                                            v-model="editedSermon.image" width="1024" height="768"
                                        />
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input" type="checkbox" id="inputCCLicense"
                                               v-model="editedSermon.cc_license" value="1"/>
                                        <label class="form-check-label" for="inputCCLicense">Predigt und Materialien unter
                                            der CC-BY-SA 4.0 Lizenz freigeben</label>
                                    </div>
                                </card-body>
                            </card>

                        </div>
                    </div>
                    <div class="col-md-8 d-flex flex-column min-h-0 overflow-hidden sermon-editor__column">
                        <card class="h-100 shadow-sm sermon-editor__card overflow-hidden">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex flex-wrap align-items-center gap-2" v-if="editorText">
                                    <strong class="me-2">Predigttext</strong>
                                </div>
                            </div>
                            <card-body class="d-flex flex-column min-h-0 p-0">
                                <div class="tiptap-toolbar btn-toolbar flex-wrap" v-if="editorText">
                                    <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleBold().run()" :class="{active: editorText.isActive('bold')}" title="Fett"><b>B</b></button>
                                    <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleItalic().run()" :class="{active: editorText.isActive('italic')}" title="Kursiv"><i>I</i></button>
                                    <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().toggleUnderline().run()" :class="{active: editorText.isActive('underline')}" title="Unterstrichen"><u>U</u></button>
                                    <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleHeading({level:1}).run()" :class="{active: editorText.isActive('heading',{level:1})}" title="Überschrift">H1</button>
                                    <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().toggleBlockquote().run()" :class="{active: editorText.isActive('blockquote')}" title="Zitat">&ldquo;</button>
                                    <button class="btn btn-sm btn-outline-secondary me-1" @click.prevent="editorText.chain().focus().toggleOrderedList().run()" :class="{active: editorText.isActive('orderedList')}" title="Nummerierte Liste">1.</button>
                                    <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().toggleBulletList().run()" :class="{active: editorText.isActive('bulletList')}" title="Aufzählung">&bull;</button>
                                    <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="editorText.chain().focus().unsetAllMarks().clearNodes().run()" title="Formatierung entfernen">&#10005;</button>
                                    <button class="btn btn-sm btn-outline-secondary me-2" @click.prevent="insertBible()" title="Bibeltext hinzufügen"><span class="mdi mdi-book-open-variant"></span></button>
                                    <template v-if="funerals.length">
                                        <button v-for="(funeral, funeralIndex, funeralKey) in funerals" type="light" class="btn btn-sm btn-outline-secondary me-2"
                                                :key="funeralKey" :title="'Lebenslauf von '+funeral.buried_name+' einfügen'"
                                                @click="insertFuneralStory(funeral)">
                                            <span class="mdi mdi-text me-1"></span> {{ funeral.buried_name }}
                                        </button>
                                    </template>
                                    <div class="ms-auto d-flex align-items-center">
                                        <text-stats :text="editedSermon.text" :key="textUpdated" speech-time-label="ca. "/>
                                    </div>
                                </div>
                                <div class="editor-scroll-area">
                                    <editor-content :editor="editorText" class="form-control tiptap-editor border-0 rounded-0" />
                                </div>
                            </card-body>
                        </card>
                    </div>
                </div>
            </form>
        </admin-layout>
    </div>
</template>

<script>
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';

import FormFileUploader from "../components/Ui/forms/FormFileUploader";
import FormImageAttacher from "../components/Ui/forms/FormImageAttacher";
import TextStats from "../components/LiturgyEditor/Elements/TextStats";
import Card from "../components/Ui/cards/card";
import CardBody from "../components/Ui/cards/cardBody";
import FormBibleReferenceInput from "../components/Ui/forms/FormBibleReferenceInput";
import NavButton from "../components/Ui/buttons/NavButton";
import {getTextSources} from "../libraries/TextSources";
import FormTextarea from "../components/Ui/forms/FormTextarea.vue";
import FormInput from "../components/Ui/forms/FormInput.vue";

export default {
    name: "sermonEditor",
    components: {
        FormInput,
        FormTextarea,
        NavButton,
        FormBibleReferenceInput,
        CardBody,
        Card,
        TextStats,
        FormImageAttacher,
        FormFileUploader,
        EditorContent,
    },
    props: {
        sermon: Object,
        services: Array,
        service: {
            type: Object,
            default: null,
        },
    },
    computed: {
        funerals() {
            let f = [];
            this.services.forEach(s => {
                if (s.funerals.length > 0) {
                    s.funerals.forEach(funeral => {
                        f.push(funeral);
                    });
                }
            });
            return f;
        }
    },
    data() {
        var emptySermon = {
            title: '',
            subtitle: '',
            reference: '',
            series: '',
            summary: '',
            text: '',
            image: '',
            notes_header: '',
            key_points: '',
            questions: '',
            literature: '',
            audio_recording: '',
            video_url: '',
            external_url: '',
            cc_license: false,
            permit_handouts: false,
        };
        var editedSermon = this.sermon ? this.sermon : emptySermon
        if (null === editedSermon.text) editedSermon.text = '';

        let allServices = this.services || [this.service];
        let textSources = {};
        allServices.forEach(thisService => {
            const theseTextSources = getTextSources(thisService);
            textSources = {
                ...theseTextSources,
                ...textSources,
            }
        });

        return {
            referenceCopied: 0,
            textUpdated: 0,
            editedSermon: editedSermon,
            fileUpload: null,
            removeImage: false,
            textSources,
            editorText: new Editor({
                content: editedSermon.text,
                extensions: [
                    StarterKit,
                    Underline,
                    Placeholder.configure({ placeholder: 'Schreibe hier den Text deiner Predigt hin...' }),
                ],
                onUpdate: ({ editor }) => { editedSermon.text = editor.getHTML(); this.textUpdated++; },
            }),
            editorLiterature: new Editor({
                content: editedSermon.literature,
                extensions: [
                    StarterKit,
                    Placeholder.configure({ placeholder: 'Hier gibt es Platz z.B. für eine Literaturliste...' }),
                ],
                onUpdate: ({ editor }) => { editedSermon.literature = editor.getHTML(); },
            }),
        }
    },
    beforeUnmount() {
        this.editorText.destroy();
        this.editorLiterature.destroy();
    },
    methods: {
        saveSermon() {
            let formData = new FormData(document.getElementById('formSermon'));
            for (const [key, value] of Object.entries(this.editedSermon)) {
                if (key != 'image') formData.append(key, value || '');
            }
            if (!formData.has('cc_license')) formData.append('cc_license', 0);
            if (!formData.has('permit_handouts')) formData.append('permit_handouts', 0);
            if (this.removeImage) formData.append('remove_image', 1);
            if (this.fileUpload) {
                formData.append('image', this.fileUpload);
            }
            if (undefined === this.editedSermon.id) {
                this.$inertia.post(route('sermon.store', {service: this.service.slug}), formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    preserveState: false,
                });
            } else {
                formData.append('_method', 'PATCH');
                this.$inertia.post(route('sermon.update', {sermon: this.editedSermon.id}), formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    preserveState: false,
                });
            }
        },
        uncoupleService(service) {
            if ((this.services.length > 1) || confirm('Diese Predigt ist nur mit einem Gottesdienst verbunden. Wenn du diese Verbindung trennst, wird die Predigt gelöscht. Willst du das wirklich?')) {
                this.$inertia.delete(route('sermon.uncouple', {service: service.slug}), {preserveState: false});
            }
        },
        setImage(event) {
            this.fileUpload = event.target.files[0];
        },
        insertText(editor, text) {
            if (text) editor.chain().focus().insertContent(text).run();
        },
        insertBible() {
            var reference = window.prompt('Welche Bibelstelle möchtest du einfügen?');
            axios.get(route('bible.text', {
                reference: reference,
                showReference: 1,
                showVerseNumbers: 0
            })).then(result => {
                if (result.data.text) {
                    this.insertText(this.editorText, result.data.text);
                } else {
                    alert('Zu dieser Stellenangabe konnte kein Text gefunden werden.');
                }
            });
        },
        setSermonReference(ref) {
            this.editedSermon.reference = ref;
            this.referenceCopied++;
        },
        insertFuneralStory(funeral) {
            this.editorText.chain().focus().insertContent(funeral.life || '').run();
        }
    }
}
</script>

<style scoped>
.min-h-0 { min-height: 0 !important; }

.sermon-editor,
.sermon-editor :deep(main.admin-content) {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.sermon-editor__form {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.sermon-editor__layout {
    flex: 1 1 auto;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.sermon-editor__column {
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.sermon-editor__card {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.sermon-editor__sidebar {
    background-color: #f8f9fa;
    flex: 1 1 auto;
    overflow-y: auto;
}

.editor-scroll-area {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
}

:deep(.tiptap-editor) {
    min-height: 100%;
    overflow: hidden;
}

:deep(.ProseMirror) {
    font-family: inherit;
    font-weight: normal;
    min-height: 100%;
    outline: none;
    padding: 1rem;
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}

@media (max-width: 767.98px) {
    .sermon-editor__form,
    .sermon-editor__layout {
        height: auto;
        overflow: visible;
    }

    .sermon-editor__column,
    .sermon-editor__sidebar,
    .editor-scroll-area {
        height: auto;
        overflow: visible;
    }

    .sermon-editor__card {
        height: auto;
    }
}
</style>
