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
    <div class="liturgy-text-editor form-group">
        <label>{{ myLabel }}</label>
        <div class="tiptap-toolbar btn-toolbar mb-1" v-if="editor">
            <button v-if="mySettings.toolbar.bold" class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="editor.chain().focus().toggleBold().run()"
                    :class="{active: editor.isActive('bold')}" title="Fett"><b>B</b></button>
            <button v-if="mySettings.toolbar.italic" class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="editor.chain().focus().toggleItalic().run()"
                    :class="{active: editor.isActive('italic')}" title="Kursiv"><i>I</i></button>
            <button v-if="mySettings.toolbar.underline" class="btn btn-sm btn-outline-secondary me-2"
                    @click.prevent="editor.chain().focus().toggleUnderline().run()"
                    :class="{active: editor.isActive('underline')}" title="Unterstrichen"><u>U</u></button>
            <button v-if="mySettings.toolbar.header" class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="editor.chain().focus().toggleHeading({level:1}).run()"
                    :class="{active: editor.isActive('heading',{level:1})}" title="Überschrift">H1</button>
            <button v-if="mySettings.toolbar.blockquote" class="btn btn-sm btn-outline-secondary me-2"
                    @click.prevent="editor.chain().focus().toggleBlockquote().run()"
                    :class="{active: editor.isActive('blockquote')}" title="Zitat">&ldquo;</button>
            <template v-if="mySettings.toolbar.formats && mySettings.toolbar.formats.length > 0">
                <button class="btn btn-sm btn-outline-secondary me-1"
                        @click.prevent="editor.chain().focus().toggleOrderedList().run()"
                        :class="{active: editor.isActive('orderedList')}" title="Nummerierte Liste">1.</button>
                <button class="btn btn-sm btn-outline-secondary me-2"
                        @click.prevent="editor.chain().focus().toggleBulletList().run()"
                        :class="{active: editor.isActive('bulletList')}" title="Aufzählung">&bull;</button>
            </template>
            <button v-if="mySettings.toolbar.clean" class="btn btn-sm btn-outline-secondary me-2"
                    @click.prevent="editor.chain().focus().unsetAllMarks().clearNodes().run()"
                    title="Formatierung entfernen">&#10005;</button>
            <button class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="dialogs.insertWord = true"
                    title="Aus Worddokument importieren"><span class="mdi mdi-file-word"></span> Word</button>
            <button class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="dialogs.insertBible = true"
                    title="Bibeltext hinzufügen"><span class="mdi mdi-book-open-variant"></span> Bibel</button>
            <button class="btn btn-sm btn-outline-secondary me-2"
                    @click.prevent="dialogs.insertLiturgic = true"
                    title="Liturgischen Text hinzufügen"><span class="mdi mdi-text"></span> Lit. Texte</button>

            <quill-dropdown v-for="funeralDataset in funeralDataSets" :key="funeralDataset.funeral.id"
                            :label="funeralDataset.funeral.buried_name"
                            :title="'Textbausteine zur Beerdigung von '+funeralDataset.funeral.buried_name"
                            icon="mdi mdi-grave-stone" :items="funeralDataset.data"
                            @input="insertText($event)"/>

            <quill-dropdown v-for="baptismDataset in baptismDataSets" :key="baptismDataset.baptism.id"
                            :label="baptismDataset.baptism.candidate_name"
                            :title="'Textbausteine zur Taufe von '+baptismDataset.baptism.candidate_name"
                            icon="mdi mdi-water" :items="baptismDataset.data"
                            @input="insertText($event)"/>

            <quill-dropdown v-for="weddingDataset in weddingDataSets" :key="weddingDataset.wedding.id"
                            :label="weddingDataset.wedding.spouse1_name+' &amp; '+weddingDataset.wedding.spouse2_name"
                            :title="'Textbausteine zur Trauung von '+weddingDataset.wedding.spouse1_name+' und '+weddingDataset.wedding.spouse2_name"
                            icon="mdi mdi-ring" :items="weddingDataset.data"
                            @input="insertText($event)"/>
        </div>
        <editor-content :editor="editor" class="form-control tiptap-editor" />

        <insert-liturgic-text-dialog v-if="dialogs.insertLiturgic" class="dialog" :service="service"
                                     @input="dialogs.insertLiturgic = false; insertText($event, true)"/>
        <insert-bible-text-dialog v-if="dialogs.insertBible" class="dialog" :service="service"
                                  @input="dialogs.insertBible = false; insertText($event)" />
        <insert-word-document-dialog v-if="dialogs.insertWord" class="dialog"
                                     @input="dialogs.insertWord = false; insertText($event)" />
    </div>
</template>


<script>
import { markRaw } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';
import RelativeDate from "@pfarr.tools/relative-date";
import QuillDropdown from "../Quill/QuillDropdown.vue";
import InsertLiturgicTextDialog from "../Dialogs/InsertLiturgicTextDialog.vue";
import InsertBibleTextDialog from "../Dialogs/InsertBibleTextDialog.vue";
import InsertWordDocumentDialog from "../Dialogs/InsertWordDocumentDialog.vue";
import {NameService} from "../../../../libraries/NameService";


export default {
    name: "LiturgyTextEditor",
    components: {
        InsertWordDocumentDialog,
        InsertBibleTextDialog,
        InsertLiturgicTextDialog,
        QuillDropdown,
        EditorContent,
    },
    emits: ['update:modelValue'],
    props: ['service', 'modelValue', 'settings', 'label'],
    inject: ['lists'],
    data() {
        let funeralDataSets = [];
        this.service.funerals.forEach(funeral => {
            funeralDataSets.push({
                funeral: funeral,
                data: {
                    'Geburtsdatum': moment(funeral.dob).locale('de').format('LL'),
                    'Sterbedatum': moment(funeral.dod).locale('de').format('LL'),
                    'Sterbedatum (relativ)': RelativeDate(moment(funeral.dod).format('DD.MM.YYYY'), moment(this.service.date).format('DD.MM.YYYY')),
                    'Sterbealter': funeral.age,
                    'Lebenszeit in Tagen': moment(funeral.dod).diff(moment(funeral.dob), 'days').toLocaleString('de-DE'),
                    'Geburtsort': funeral.birth_place,
                    'Sterbeort': funeral.death_place,
                    'Geburtsname': funeral.birth_name,
                    'Rufname': funeral.spoken_name,
                }
            })
        });

        let baptismDataSets = []
        this.service.baptisms.forEach(baptism => {
            let nameSet = new NameService(baptism.candidate_name);
            baptismDataSets.push({
                baptism: baptism,
                data: {
                    'Name': nameSet.name,
                    'Vorname': nameSet.first,
                    'Nachname': nameSet.last,
                }
            })
        });

        let weddingDataSets = []
        this.service.weddings.forEach(wedding => {
            let nameSet = [wedding.spouse1_name.split(','), wedding.spouse2_name.split(',')];
            weddingDataSets.push({
                wedding: wedding,
                data: {
                    'Name 1': nameSet[0][1].trim()+' '+nameSet[0][0].trim(),
                    'Vorname 1': nameSet[0][1].trim(),
                    'Nachname 1': nameSet[0][0].trim(),
                    'Name 2': nameSet[1][1].trim()+' '+nameSet[1][0].trim(),
                    'Vorname 2': nameSet[1][1].trim(),
                    'Nachname 2': nameSet[1][0].trim(),
                }
            })
        });

        let defaultSettings = {
            toolbar: {
                bold: false,
                italic: false,
                underline: false,
                header: false,
                blockquote: false,
                formats: {},
            },
        };
        let mySettings = { ...defaultSettings, ...this.settings };

        return {
            myLabel: this.label || 'Inhalt',
            mySettings,
            dialogs: {
                insertLiturgic: false,
                insertBible: false,
                insertWord: false,
            },
            funeralDataSets,
            baptismDataSets,
            weddingDataSets,
            editor: markRaw(new Editor({
                content: this.modelValue || '',
                extensions: [
                    StarterKit,
                    Underline,
                    Placeholder.configure({ placeholder: 'Hier Text eingeben...' }),
                ],
                onUpdate: ({ editor }) => {
                    this.$emit('update:modelValue', editor.getHTML());
                },
            })),
        }
    },
    watch: {
        modelValue(v) {
            if (v !== this.editor.getHTML()) this.editor.commands.setContent(v || '');
        },
    },
    beforeUnmount() {
        this.editor.destroy();
    },
    methods: {
        insertText(value, withHtml = false) {
            if (!value) return;
            this.editor.chain().focus().insertContent(String(value)).run();
        },
    }
}
</script>

<style scoped>
.tiptap-editor :deep(.ProseMirror) {
    min-height: 80px;
    outline: none;
    font-family: inherit;
    font-weight: normal;
}
:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}
.dialog {
    min-height: 70vh;
}
</style>
