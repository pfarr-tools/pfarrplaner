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
    <div class="form-bible-reference-input">
        <form-group :name="name" :id="myId" :label="label" :help="help" pre-label="book-bible" :required="required" :is-checked-item="isCheckedItem" :value="myValue">
            <div class="input-group mb-3">
                <div v-if="Object.keys(myOptions).length > 0" class="input-group-prepend">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" @click="dropDownVisible = !dropDownVisible">
                        <span class="mdi mdi-book-open-variant"></span></button>
                    <div class="dropdown-menu" :style="{display : dropDownVisible ? 'block' : 'none'}">
                        <a v-for="(option,optionIndex) in myOptions" class="dropdown-item" @click.prevent.stop="setTextFromList(option.id); dropDownVisible = false;" :key="optionIndex">{{ option.name }}</a>
                    </div>
                </div>
                <input type="text" class="form-control" :aria-label="label" :name="name" :value="myReference" @input="handleInput" :key="valueChanged">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" @click="bibleDropDownVisible = !bibleDropDownVisible">
                        {{ myVersion }}</button>
                    <div class="dropdown-menu" :style="{display : bibleDropDownVisible ? 'block' : 'none'}">
                        <a v-for="(option,optionIndex) in availableVersions" class="dropdown-item" @click.prevent.stop="setVersion(option); dropDownVisible = false;" :key="optionIndex">{{ option }}</a>
                    </div>
                </div>
            </div>
        </form-group>
        <small class="form-text text-muted mt-0 p-0" :title="myBibleText">
            <span v-if="myBibleTextLoading" class="mdi mdi-spin mdi-loading" title="Bibeltext wird geladen..."></span>
            <span v-else>{{ myBibleText }}</span>
            <span v-if="(!myBibleTextLoading) && (myBibleText) && (clipboard)" class="mdi mdi-content-copy" @click.prevent.stop="copyToClipboard"
                                                       title="Klicken, um den Text in die Zwischenablage zu kopieren"></span>
        </small>
    </div>
</template>

<script>
import FormGroup from "./FormGroup";
import ValueCheck from "../elements/ValueCheck";
import BibleReference from "../../LiturgyEditor/Elements/BibleReference";
import __ from 'lodash';
import FormSelectize from "./FormSelectize";
import FormInput from "./FormInput";
import Selectize from 'vue2-selectize';

export default {
    name: "FormBibleReferenceInput",
    components: {FormInput, FormSelectize, BibleReference, ValueCheck, FormGroup, Selectize},
    props: {
        label: String,
        id: String,
        type: {
            type: String,
            default: 'text',
        },
        required: {
            type: Boolean,
            default: false,
        },
        name: String,
        value: {
            type: null,
        },
        help: String,
        placeholder: String,
        autofocus: Boolean,
        disabled: {
            type: Boolean,
            default: false,
        },
        isCheckedItem: {
            type: null,
            default: false,
        },
        sources: {
            type: Object,
            default() { return {}; },
        },
        clipboard: {
            type: Boolean,
            default: true,
        },
        fullText: {
            type: Boolean,
            default: false,
        },
        version: {
            type: String,
            default: '',
        },
        noVersion: {
            type: Boolean,
            default: false,
        }
    },
    mounted() {
        if (this.myId == '') this.myId = this._uid;
        if (this.value) this.bibleText(this);
    },
    data() {
        this.sources = this.sources || {};

        let myOptions = [];
        for (const sourceKey in this.sources) {
            myOptions.push({ id: this.sources[sourceKey], name: sourceKey+': '+this.sources[sourceKey]});
        }

        let myValue = this.value || '';
        if (!myValue.includes('[')) myValue += ' ['+(this.version || this.$page.props.bible.versions[0] || '')+']';
        let parts = myValue.split('[');
        let myVersion = parts[1].replace(']', '').trim();
        let myReference = parts[0].trim();


        return {
            errors: this.$page.props.errors,
            error: this.$page.props.errors[this.name] || false,
            myId: this.id || '',
            myValue,
            myBibleText: '',
            myBibleTextLoading: false,
            myReference,
            myVersion,
            availableVersions: this.$page.props.bible.versions,
            component: this,
            myOptions,
            mySettings: {
                create: function(input) {
                    return {id: input, name: input};
                },
                options: myOptions,
                labelField: 'name',
                valueField: 'id',
                searchFields: ['name', 'id'],
            },
            valueChanged: 0,
            dropDownVisible: false,
            bibleDropDownVisible: false,
        }
    },
    watch: {
        value: {
            handler: function (newVal) {
                if (this.required) {
                    this.error = this.$page.props.errors[this.name] = newVal ? '' : 'Dieses Feld darf nicht leer bleiben.';
                    this.$forceUpdate();
                }
            }
        }
    },
    methods: {
        bibleText: __.debounce((component) => {
            if (!component.myValue.includes(' ')) return;
            if (!component.myValue.includes(',')) return;
            component.myBibleText = '';
            component.myBibleTextLoading = true;
            axios.get(route('bible.text', {reference: component.myReference, version: component.myVersion}))
                .then(result => {
                    component.myBibleText = result.data.text;
                    component.myReference = result.data.reference;
                    component.myBibleTextLoading = false;
                    if (component.myReference != result.data.reference.correctedReference) {
                        component.myReference = result.data.reference.correctedReference;
                        component.setNewValue(false);
                    }
                });
        }, 1000),
        copyToClipboard() {
            const cb = navigator.clipboard;
            cb.writeText(this.myBibleText+" ("+this.myReference+')').then(result => {});
        },
        setTextFromList(e) {
            this.myReference = e;
            this.valueChanged++;
            this.setNewValue();
        },
        setVersion(e) {
            this.myVersion = e;
            this.valueChanged++;
            this.setNewValue();
        },
        setNewValue(fetchBibleText = true) {
            this.myValue = this.myReference+' ['+this.myVersion+']';
            this.returnInput();
            if (fetchBibleText) this.bibleText(this);
        },
        handleInput(e) {
            this.myReference = e.target.value;
            this.setNewValue();
        },
        returnInput() {
            this.$emit('input', this.fullText ? this.myBibleText+" \n("+this.myReference+')' : this.myValue);
        }
    },
}
</script>

<style scoped>
    .form-bible-reference-input {
        margin-bottom: 1rem;
    }

    span.mdi-book-open-variant {
        font-size: .9em;
    }
</style>
