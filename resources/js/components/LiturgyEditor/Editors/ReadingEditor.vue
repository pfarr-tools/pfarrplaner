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
    <div class="liturgy-item-reading-editor">
        <div class="form-group">
            <label for="title">Titel im Ablaufplan</label>
            <input class="form-control" v-model="editedElement.title" v-focus/>
        </div>
        <form-textarea v-model="editedElement.data.intro" label="Hinführung zur Lesung" class="mb-1" />
        <form-bible-reference-input v-model="editedElement.data.reference" :sources="textSources" :allow-own-version="true" />
        <div :key="editedElement.data.reference" v-if="editedElement.data.reference ? editedElement.data.reference.includes('[Eigener Text]') : false">
            <form-textarea v-model="editedElement.data.customText" label="Eigener Text" />
            <form-input v-model="editedElement.data.customSource" label="Eigene Quellenangabe" />
        </div>
        <form-check v-model="editedElement.data.showInHandouts" label="Diese Lesung in Liedzetteln usw. mit ausgeben." />
    </div>
</template>

<script>
import TimeFields from "./Elements/TimeFields";
import FormBibleReferenceInput from "../../Ui/forms/FormBibleReferenceInput";
import FormTextarea from "../../Ui/forms/FormTextarea.vue";
import FormCheck from "../../Ui/forms/FormCheck.vue";
import FormInput from "../../Ui/forms/FormInput.vue";
import {getTextSources} from "../../../libraries/TextSources";

export default {
    name: "ReadingEditor",
    components: {FormInput, FormCheck, FormTextarea, FormBibleReferenceInput, TimeFields},
    props: {
        element: Object,
        service: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        }
    },
    data() {
        var e = this.element;

        // initialize missing fields
        e.data = {
            intro: '',
            reference: '',
            customText: '',
            customSource: '',
            fullText: '',
            showInHandouts: false,
            ...e.data,
        };


        let textSources = getTextSources(this.service);

        return {
            editedElement: e,
            textSources,
        };
    },
    mounted() {
        if (this.editedElement.data.reference) this.updateFullText();
    },
    watch: {
        'editedElement.data.reference'() { this.updateFullText(); },
        'editedElement.data.customText'(val) {
            if (this.editedElement.data.reference && this.editedElement.data.reference.includes('[Eigener Text]')) {
                this.editedElement.data.fullText = val || '';
            }
        },
    },
    methods: {
        updateFullText() {
            const ref = this.editedElement.data.reference;
            if (!ref) return;
            if (ref.includes('[Eigener Text]')) {
                this.editedElement.data.fullText = this.editedElement.data.customText || '';
                return;
            }
            const parts = ref.split('[');
            const myReference = parts[0].trim();
            const myVersion = parts.length > 1 ? parts[1].replace(']', '').trim() : '';
            axios.get(route('bible.text', {reference: myReference, version: myVersion}))
                .then(result => { this.editedElement.data.fullText = result.data.text; });
        },
        save: function () {
            this.$inertia.patch(route('liturgy.item.update', {
                service: this.service.id,
                block: this.element.liturgy_block_id,
                item: this.element.id,
            }), this.element, {preserveState: false});
        },
    }
}
</script>

<style scoped>
.liturgy-item-reading-editor {
    padding: 5px;
}
</style>
