<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
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
    <div class="liturgy-text-editor">
        <quill-editor v-model="myValue" :options="quillOptions"
                      class="focused" ref="textEditor"
                      @focus="textEditorActive = true"
                      @blur="textEditorActive = false">
            <div id="toolbar" slot="toolbar">
                <button v-if="mySettings.toolbar.bold" class="ql-bold"></button>
                <button v-if="mySettings.toolbar.italic"  class="ql-italic"></button>
                <button v-if="mySettings.toolbar.underline"  class="ql-underline mr-2"></button>
                <button v-if="mySettings.toolbar.header"  class="ql-header" value="1"></button>
                <button v-if="mySettings.toolbar.blockquote"  class="ql-blockquote mr-2"></button>
                <span v-if="mySettings.toolbar.formats.length > 0"  class="ql-formats mr-2">
                                                    <button class="ql-list" value="ordered"></button>
                                                    <button class="ql-list" value="bullet"></button>
                                                    <button class="ql-indent" value="-1"></button>
                                                    <button class="ql-indent" value="+1"></button>
                                                </span>
                <button v-if="mySettings.toolbar.clean"  class="ql-clean mr-2"></button>
                <button class="ql-importword quill-mdi-button  quill-text-button" :class="floatClass"
                        @click="dialogs.insertWord = true"
                        title="Aus Worddokument importieren"><span
                    class="mdi mdi-file-word"></span> Word
                </button>
                <button class="ql-insertbible quill-mdi-button  quill-text-button" :class="floatClass"
                        @click="dialogs.insertBible = true"
                        title="Bibeltext hinzufügen"><span
                    class="mdi mdi-book-open-variant"></span> Bibel
                </button>
                <button class="ql-inserttext quill-mdi-button  quill-text-button" :class="floatClass"
                        @click="dialogs.insertLiturgic = true"
                        title="Liturgischen Text hinzufügen"><span
                    class="mdi mdi-text"></span> Lit. Texte
                </button>

                <quill-dropdown v-for="funeralDataset in funeralDataSets" :key="funeralDataset.funeral.id"
                                :label="funeralDataset.funeral.buried_name"
                                :title="'Textbausteine zur Beerdigung von '+funeralDataset.funeral.buried_name"
                                icon="mdi mdi-grave-stone" :items="funeralDataset.data"
                                :class="floatClass" @input="insertText($event)"/>

                <quill-dropdown v-for="baptismDataset in baptismDataSets" :key="baptismDataset.baptism.id"
                                :label="baptismDataset.baptism.candidate_name"
                                :title="'Textbausteine zur Taufe von '+baptismDataset.baptism.candidate_name"
                                icon="mdi mdi-water" :items="baptismDataset.data"
                                :class="floatClass" @input="insertText($event)"/>

                <quill-dropdown v-for="weddingDataset in weddingDataSets" :key="weddingDataset.wedding.id"
                                :label="weddingDataset.wedding.spouse1_name+' &amp; '+weddingDataset.wedding.spouse2_name"
                                :title="'Textbausteine zur Trauung von '+weddingDataset.wedding.spouse1_name+' und '+weddingDataset.wedding.spouse2_name"
                                icon="mdi mdi-ring" :items="weddingDataset.data"
                                :class="floatClass" @input="insertText($event)"/>
            </div>
        </quill-editor>

        <insert-liturgic-text-dialog v-if="dialogs.insertLiturgic" class="dialog"
                                     @input="dialogs.insertLiturgic = false; insertText($event)"/>
        <insert-bible-text-dialog v-if="dialogs.insertBible"  class="dialog" :service="service"
                                  @input="dialogs.insertBible = false; insertText($event)" />
        <insert-word-document-dialog v-if="dialogs.insertWord" class="dialog"
                                     @input="dialogs.insertWord = false; insertText($event)" />
    </div>
</template>


<script>
import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'
import 'quill/dist/quill.bubble.css'
import '../../../SermonEditor/quill.css'

import {quillEditor} from 'vue-quill-editor';
import {Quill} from "vue-quill-editor/src";
import RelativeDate from "../../../../libraries/RelativeDate";
import QuillDropdown from "../Quill/QuillDropdown.vue";
import QuillDropdownForm from "../Quill/QuillDropdownForm.vue";
import {lineBreakMatcher, SmartBreak} from '../Quill/QuillSmartBreak';
import InsertLiturgicTextDialog from "../Dialogs/InsertLiturgicTextDialog.vue";
import InsertBibleTextDialog from "../Dialogs/InsertBibleTextDialog.vue";
import InsertWordDocumentDialog from "../Dialogs/InsertWordDocumentDialog.vue";


export default {
    name: "LiturgyTextEditor",
    components: {
        InsertWordDocumentDialog,
        InsertBibleTextDialog,
        InsertLiturgicTextDialog,
        QuillDropdownForm, QuillDropdown, quillEditor
    },
    props: ['service', 'value', 'settings'],
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
            let nameSet = baptism.candidate_name.split(',');
            baptismDataSets.push({
                baptism: baptism,
                data: {
                    'Name': nameSet[1].trim()+' '+nameSet[0].trim(),
                    'Vorname': nameSet[1].trim(),
                    'Nachname': nameSet[0].trim(),
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

        Quill.register(SmartBreak);

        let quillDefaults = {
            formats: [],
            placeholder: 'Hier Text eingeben...',
            modules: {
                toolbar: {
                    container: '#toolbar',
                    handlers: {
                        inserttext: this.dummy,
                        insertbible: this.dummy,
                        importword: this.dummy,
                        insertfuneraltext: this.quillShowInsertFuneralTextDialog,
                        custom: this.clickHandler,
                    }
                },
                clipboard: {
                    matchers: [["BR", lineBreakMatcher]],
                    matchVisual: false,
                },
                keyboard: {
                    bindings: {
                        linebreak: {
                            key: 13,
                            shiftKey: true,
                            handler: function (range) {
                                const currentLeaf = this.quill.getLeaf(range.index)[0];
                                const nextLeaf = this.quill.getLeaf(range.index + 1)[0];
                                this.quill.insertEmbed(range.index, "break", true, "user");
                                // Insert a second break if:
                                // At the end of the editor, OR next leaf has a different parent (<p>)
                                if (nextLeaf === null || currentLeaf.parent !== nextLeaf.parent) {
                                    this.quill.insertEmbed(range.index, "break", true, "user");
                                }
                                // Now that we've inserted a line break, move the cursor forward
                                this.quill.setSelection(range.index + 1, Quill.sources.SILENT);
                            }
                        }
                    }
                }
            }
        };

        let defaultSettings = {
            toolbar: {
                bold: false,
                italic: false,
                underline: false,
                header: false,
                blockquote: false,
                formats: {},
            },
            quill: {},
        }

        let mySettings = {
            ...defaultSettings,
            ...this.settings,
        };

        delete mySettings.quill;

        let floatClass = '';
        if (mySettings.toolbar.bold
            || mySettings.toolbar.italic
            || mySettings.toolbar.underline
            || mySettings.toolbar.header
            || mySettings.toolbar.blockquote
            || mySettings.toolbar.formats.length) {
            floatClass = 'float-right';
        }


        return {
            myValue: this.value,
            floatClass,
            mySettings,
            dialogs: {
                insertLiturgic: false,
                insertBible: false,
                insertWord: false,
            },
            quill: null,
            t: false,
            selectedText: '',
            funeralDataSets,
            baptismDataSets,
            weddingDataSets,
            textEditorActive: false,
            quillOptions: {
                ...quillDefaults,
                ...this.settings.quill || {},
            },

        }
    },
    methods: {
        dummy() {},
        insertText(value) {
            const quill = this.$refs.textEditor.quill;
            const {index, length} = quill.selection.savedRange;
            value = String(value);
            if (value != '') {
                quill.deleteText(index, length);
                quill.insertText(index, value);
            }
            quill.setSelection(index + value.length);
        },
    }
}
</script>

<style scoped>
.ql-toolbar .quill-mdi-button {
    padding-top: 1px;
}

.ql-toolbar .quill-text-button {
    width: auto !important;
}

.ql-toolbar button {
    font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
    font-size: 14px;
    font-weight: 500;
}

>>> .ql-container.ql-snow,
>>> .ql-container.ql-snow .ql-editor {
    font-family: inherit !important;
    font-weight: normal;
}

>>> .quill-dropdown-form .ql-picker-options {
    min-width: 500px;
}

>>> .quill-dropdown-form.float-right .ql-picker-options {
    right: 0;
}

.dialog {
    min-height: 70vh;
}

</style>
