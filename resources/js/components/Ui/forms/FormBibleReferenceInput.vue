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
        <form-group :name="name" :id="myId" :input-id="`${myId}Input`" :label="label" :help="help" pre-label="book-bible"
                    :required="required" :is-checked-item="isCheckedItem" :value="currentValue" v-slot="field">
            <div class="input-group">
                <div v-if="myOptions.length > 0" class="dropdown">
                    <button ref="sourceToggle" class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false" title="Vorschlag übernehmen">
                        <span class="mdi mdi-book-open-variant" aria-hidden="true"></span>
                        <span class="visually-hidden">Vorschlag übernehmen</span>
                    </button>
                    <div class="dropdown-menu">
                        <button v-for="(option, optionIndex) in myOptions" :key="optionIndex" type="button" class="dropdown-item"
                                @click="setTextFromList(option.id)">{{ option.name }}</button>
                    </div>
                </div>
                <input :id="field.fieldId" type="text" class="form-control" :class="{'is-invalid': field.error}"
                       :aria-label="label" :aria-invalid="field.error ? 'true' : 'false'"
                       :aria-describedby="field.describedBy || undefined" :name="name" :value="myReference"
                       :disabled="disabled" :required="required" @input="handleInput">
                <div v-if="!noVersion" class="dropdown">
                    <button ref="versionToggle" class="btn btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ myVersion }}
                    </button>
                    <div class="dropdown-menu">
                        <button v-for="(option, optionIndex) in availableVersions" :key="optionIndex" type="button" class="dropdown-item"
                                @click="setVersion(option)">{{ option }}</button>
                    </div>
                </div>
            </div>
        </form-group>
        <div :key="myVersion">
            <small class="form-text text-muted mt-0 p-0" :title="myBibleText" v-if="myVersion != 'Eigener Text'">
                <span v-if="myBibleTextLoading" class="mdi mdi-spin mdi-loading" title="Bibeltext wird geladen..."></span>
                <span v-else>{{ myBibleText }}</span>
                <button v-if="(!myBibleTextLoading) && myBibleText && clipboard" type="button"
                        class="btn btn-link btn-sm p-0 ms-2 align-baseline" @click.prevent.stop="copyToClipboard"
                        title="Klicken, um den Text in die Zwischenablage zu kopieren">
                    <span class="mdi mdi-content-copy" aria-hidden="true"></span>
                    <span class="visually-hidden">Text in die Zwischenablage kopieren</span>
                </button>
            </small>
        </div>
    </div>
</template>

<script>
import FormGroup from "./FormGroup";
import __ from 'lodash';
import { uid } from '../../../libraries/uid';

export default {
    name: "FormBibleReferenceInput",
    components: {FormGroup},
    emits: ['input', 'update:modelValue'],
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
        modelValue: { type: null },
        value: { type: null },
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
        },
        allowOwnVersion: {
            type: Boolean,
            default: false,
        }
    },
    computed: {
        currentValue() {
            return this.modelValue !== undefined ? this.modelValue : this.value;
        },
    },
    data() {
        const sources = this.sources || {};

        let myOptions = [];
        for (const sourceKey in sources) {
            myOptions.push({ id: sources[sourceKey], name: sourceKey+': '+sources[sourceKey]});
        }

        let availableVersions = [...this.$page.props.bible.versions];
        if (this.allowOwnVersion) {
            availableVersions.push('Eigener Text');
        }

        let myValue = (this.modelValue !== undefined ? this.modelValue : this.value) || '';
        if (!myValue.includes('[')) myValue += ' ['+(this.version || this.$page.props.bible.versions[0] || '')+']';
        let parts = myValue.split('[');
        let myVersion = parts[1].replace(']', '').trim();
        let myReference = parts[0].trim();


        return {
            myId: this.id || uid(),
            myValue,
            myBibleText: '',
            myBibleTextLoading: false,
            myReference,
            myVersion,
            availableVersions,
            myOptions,
        }
    },
    mounted() {
        if (this.currentValue) this.bibleText(this);
    },
    watch: {
        currentValue(newVal) {
            if (!newVal) {
                this.myValue = '';
                this.myReference = '';
                return;
            }
            this.myValue = newVal;
            if (!newVal.includes('[')) {
                this.myReference = newVal.trim();
                return;
            }
            let parts = newVal.split('[');
            this.myVersion = parts[1].replace(']', '').trim();
            this.myReference = parts[0].trim();
        },
    },
    methods: {
        bibleText: __.debounce((component) => {
            if (!component.myValue.includes(' ')) return;
            if (!component.myValue.includes(',')) return;
            if (component.myVersion == 'Eigener Text') {
                return;
            }
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
            navigator.clipboard?.writeText(this.myBibleText+" ("+this.myReference+')').then(() => {});
        },
        setTextFromList(id) {
            this.myReference = id;
            this.setNewValue();
            this.$refs.sourceToggle?.click()
        },
        setVersion(option) {
            this.myVersion = option;
            this.setNewValue();
            this.$refs.versionToggle?.click();
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
            const val = this.fullText ? this.myBibleText+" \n("+this.myReference+')' : this.myValue;
            this.$emit('input', val);
            this.$emit('update:modelValue', val);
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
