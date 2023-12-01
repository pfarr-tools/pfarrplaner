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

<script>

import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'
import 'quill/dist/quill.bubble.css'
import '../../../components/SermonEditor/quill.css'
import {quillEditor} from 'vue-quill-editor';
import {Quill} from "vue-quill-editor/src";
import {lineBreakMatcher, SmartBreak} from '../../../components/LiturgyEditor/Editors/Quill/QuillSmartBreak';


import FormInput from "../../../components/Ui/forms/FormInput.vue";
import FormTextarea from "../../../components/Ui/forms/FormTextarea.vue";
import FormSelectize from "../../../components/Ui/forms/FormSelectize.vue";
import NavButton from "../../../components/Ui/buttons/NavButton.vue";
import SaveButton from "../../../components/Ui/buttons/SaveButton.vue";
import FormGroup from "../../../components/Ui/forms/FormGroup.vue";

export default {
    name: "LiturgicalTextEditor",
    components: {FormGroup, quillEditor, SaveButton, NavButton, FormSelectize, FormTextarea, FormInput},
    props: {text: Object, codes: Array},
    data() {
        Quill.register(SmartBreak);
        return {
            myText: this.text,
            replacementOptions: [
                {id: 'funeral', name: 'Beerdigung'},
                {id: 'baptism', name: 'Taufe'},
                {id: 'wedding', name: 'Trauung'},
            ],
            selectizeSettings: {
                create: function (input) {
                    return {id: input, name: input};
                },
                render: {
                    option_create: function (data, escape) {
                        return '<div class="create">Neuen Code anlegen: <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                    }
                },
            },
            quillOptions: {
                formats: ['break'],
                placeholder: 'Hier Text eingeben...',
                modules: {
                    toolbar: {
                        container: '#toolbar',
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
            },
            textEditorActive: false,
        }
    },
    methods: {
        saveText() {
            if (this.myText.id) {
                this.$inertia.patch(route('admin.text.update', this.myText.id), this.myText);
            } else {
                this.$inertia.post(route('admin.text.store'), this.myText);
            }
        },
        deleteText() {
            if (!confirm('Willst du diesen Text wirklich unwiderruflich löschen?')) return;
            this.$inertia.delete(route('admin.text.destroy', this.myText.id));
        }
    }
}
</script>

<template>
    <admin-layout title="Text bearbeiten">
        <template v-slot:navbar-left>
            <save-button label="Speichern" title="Text speichern" @click="saveText" />
            <nav-button v-if="myText.id" class="ms-1" title="Text löschen" icon="mdi mdi-delete" type="danger" @click="deleteText">Löschen</nav-button>
        </template>
        <form-input label="Titel" v-model="myText.title"/>
        <form-group label="Text">
            <quill-editor v-model="myText.text" :options="quillOptions"
                          class="focused" ref="textEditor"
                          @focus="textEditorActive = true"
                          @blur="textEditorActive = false">
                <div id="toolbar" slot="toolbar"></div>
            </quill-editor>
        </form-group>
        <form-textarea label="Quellenangaben" v-model="myText.source"/>
        <form-textarea label="Anmerkungen" v-model="myText.notice"/>
        <form-selectize label="Code" v-model="myText.agenda_code" :options="codes"
                        :settings="selectizeSettings"></form-selectize>
        <form-selectize label="Enthält Variablen für" v-model="myText.needs_replacement" :options="replacementOptions"/>
    </admin-layout>
</template>

<style scoped>
    #toolbar {
        display: none;
    }
</style>
