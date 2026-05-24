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
    <div class="liturgy-item-psalm-editor">
        <form-input id="psalmEditorTitle" v-model="editedElement.title" label="Titel im Ablaufplan" />
        <div v-if="psalms === null">
            Bitte warten, Liste der Psalmen wird geladen...
        </div>
        <div v-else>
            <form-selectize name="psalm" label="Psalm" :value="selectedPsalm"
                            :options="psalms" :settings="{ searchField: ['name'], }"
                            @input="handlePsalmSelection"/>
            <div v-if="(editedElement.data.psalm.id == -1) || editPsalm">
                <form-input id="psalmTitle" label="Titel des Psalms" v-model="editedElement.data.psalm.title"/>
                <form-textarea id="psalmIntro" label="Einleitende Worte" v-model="editedElement.data.psalm.intro"
                               placeholder="Leer lassen, wenn es keinen spezielle Einleitungstext gibt"/>
                <form-textarea id="psalmText" label="Text" rows="10" v-model="editedElement.data.psalm.text"/>
                <form-textarea id="psalmCopyrights" label="Copyrights" v-model="editedElement.data.psalm.copyrights"/>
                <div class="row">
                    <div class="col-11">
                        <form-textarea id="psalmSongbook" label="Liederbuch" v-model="editedElement.data.psalm.songbook"/>
                    </div>
                    <div class="col-1">
                        <form-input id="psalmSongbookAbbreviation" label="Abkürzung"
                                    v-model="editedElement.data.psalm.songbook_abbreviation"/>
                    </div>
                </div>
                <form-input id="psalmReference" label="Liednummer" v-model="editedElement.data.psalm.reference"/>
                <div class="form-group">
                    <button class="btn btn-sm btn-light" @click.prevent="saveText" v-if="!editPsalm">Als neuen Psalm
                        speichern
                    </button>
                    <button class="btn btn-sm btn-light" @click.prevent="updateText" v-if="editPsalm"
                            :title="psalmIsDirty ? 'Es existieren ungespeicherte Änderungen am Lied.' : ''">
                        <span v-if="psalmIsDirty" class="mdi mdi-alert" style="color:red;"></span>
                        Änderungen am Psalm speichern
                    </button>
                </div>
            </div>
            <div v-else>
                <button class="btn btn-sm btn-light" @click.prevent="editPsalm = true">Psalm bearbeiten</button>
                <div class="liturgical-text-quote">
                    <p v-if="editedElement.data.psalm.title"><b>{{ editedElement.data.psalm.title }}</b></p>
                    <p v-if="editedElement.data.psalm.intro"><i>{{ editedElement.data.psalm.intro }}</i></p>
                    <nl2br v-if="editedElement.data.psalm.text" tag="p" :text="editedElement.data.psalm.text"/>
                </div>
                <text-stats :text="editedElement.data.psalm.text"/>
            </div>
        </div>
    </div>
</template>

<script>
import Nl2br from '../../Ui/Nl2br.vue';
import FormInput from "../../Ui/forms/FormInput.vue";
import FormSelectize from "../../Ui/forms/FormSelectize";
import FormTextarea from "../../Ui/forms/FormTextarea.vue";
import TextStats from "../Elements/TextStats";
import TimeFields from "./Elements/TimeFields";

export default {
    name: "PsalmEditor",
    components: {
        FormTextarea,
        FormInput,
        TimeFields,
        TextStats,
        Nl2br,
        FormSelectize,
    },
    props: {
        element: Object,
        service: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        }
    },
    /**
     * Load existing psalms
     * @returns {Promise<void>}
     */
    async created() {
        const psalms = await this.$api().get(route('api.psalms.index'))
        if (psalms.data) {
            psalms.data.forEach(psalm => {
                psalm['name'] = this.displayTitle(psalm);
            });
            this.psalms = psalms.data;
        }
    },
    data() {
        var emptyPsalm = {
            id: -1,
            title: '',
            intro: '',
            text: '',
            copyrights: '',
            songbook: '',
            songbook_abbreviation: '',
            reference: '',
        };

        if (undefined == this.element.data.psalm) {
            this.element.data = {};
            this.element.data['psalm'] = {};
            this.element.data['psalm'] = emptyPsalm;
        }
        var editedElement = this.element;
        return {
            editedElement: editedElement,
            emptyPsalm: emptyPsalm,
            editPsalm: false,
            psalms: null,
            psalmIsDirty: false,
            selectedPsalm: editedElement.data.psalm.id,
        };
    },
    methods: {
        save: function () {
            this.$inertia.patch(route('liturgy.item.update', {
                service: this.service.id,
                block: this.element.liturgy_block_id,
                item: this.element.id,
            }), this.editedElement, {preserveState: false});
        },
        saveText() {
            this.$api().post(route('api.psalms.store'), this.editedElement.data.psalm).then(response => {
                return response.data;
            }).then(data => {
                this.editPsalm = false;
                this.psalms = data.psalms;
                this.editedElement.data.psalm = data.psalm;
                this.psalmIsDirty = false;
            });
            this.psalmIsDirty = false;
        },
        updateText() {
            this.$api().patch(route('api.psalm.update', {
                psalm: this.editedElement.data.psalm.id,
            }), this.editedElement.data.psalm).then(response => {
                return response.data;
            }).then(data => {
                this.editPsalm = false;
                this.psalms = data.psalms;
                this.editedElement.data.psalm = data.psalm;
                this.psalmIsDirty = false;
            });
            this.psalmIsDirty = false;
        },
        displayTitle(psalm) {
            var title = psalm.title;
            if (psalm.reference) {
                title = psalm.reference + ' ' + title;
            }
            if (psalm.songbook_abbreviation) {
                title = psalm.songbook_abbreviation + ' ' + title;
            } else if (psalm.songbook) {
                title = psalm.songbook + ' ' + title;
            }
            return title;
        },
        quotableText() {
            var text = '';
            var title = this.displayTitle(this.editedElement.data.psalm);
            if (title.trim().length > 0) {
                text = '<b>' + title + "</b>\n\n";
            }
            text = text + this.editedElement.data.psalm.text;

            return text;
        },
        handlePsalmSelection(e) {
            this.selectedPsalm = e;

            var found = false;
            this.psalms.forEach(function (psalm) {
                if (psalm.id == e) found = psalm;
            })
            if (found) this.editedElement.data.psalm = found;
            this.psalmIsDirty = this.editPsalm;
        }
    }
}
</script>

<style scoped>
.liturgy-item-psalm-editor {
    padding: 5px;
}

.liturgical-text-quote {
    padding: 20px;
    border-left: solid 3px lightgray;
    margin: 10px;
}
</style>
