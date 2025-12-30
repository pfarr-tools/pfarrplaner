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
    <div class="rich-text-editor form-group">
        <label>{{ myLabel }}</label>
        <quill-editor v-model="myValue" :options="quillOptions"
                      class="focused" ref="textEditor"
                      @focus="textEditorActive = true"
                      @blur="textEditorActive = false">
            <div id="toolbar" slot="toolbar">
                <button v-if="mySettings.toolbar.bold" class="ql-bold"></button>
                <button v-if="mySettings.toolbar.italic"  class="ql-italic"></button>
                <button v-if="mySettings.toolbar.underline"  class="ql-underline me-2"></button>
                <button v-if="mySettings.toolbar.header"  class="ql-header" value="1"></button>
                <button v-if="mySettings.toolbar.blockquote"  class="ql-blockquote me-2"></button>
                <span v-if="mySettings.toolbar.formats.length > 0"  class="ql-formats me-2">
                                                    <button class="ql-list" value="ordered"></button>
                                                    <button class="ql-list" value="bullet"></button>
                                                    <button class="ql-indent" value="-1"></button>
                                                    <button class="ql-indent" value="+1"></button>
                                                </span>
                <button v-if="mySettings.toolbar.clean"  class="ql-clean me-2"></button>
            </div>
        </quill-editor>

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
import {NameService} from "../../../../libraries/NameService";


export default {
    name: "RichTextEditor",
    components: {
        QuillDropdownForm, QuillDropdown, quillEditor
    },
    props: ['value', 'settings', 'label'],
    inject: ['lists'],
    data() {
        Quill.register(SmartBreak);

        let quillDefaults = {
            formats: ['break'],
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
            myLabel: this.label || 'Inhalt',
            floatClass,
            mySettings,
            quill: null,
            t: false,
            selectedText: '',
            textEditorActive: false,
            quillOptions: {
                ...quillDefaults,
                ...this.settings.quill || {},
            },

        }
    },
    watch: {
        myValue: {
            handler(newVal) { this.$emit('input', newVal); },
        }
    },
    methods: {
        dummy() {},
        insertText(value, withHtml = false) {
            const quill = this.$refs.textEditor.quill;
            const {index, length} = quill.selection.savedRange;
            value = String(value);
            if (value != '') {
                quill.deleteText(index, length);
                if (withHtml) {
                    quill.clipboard.dangerouslyPasteHTML(value);
                } else {
                    quill.insertText(index, value);
                }
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
