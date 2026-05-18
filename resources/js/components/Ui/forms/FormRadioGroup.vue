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
    <fieldset class="form-group form-field" :aria-describedby="describedBy || undefined">
        <legend v-if="label || preLabel" class="control-label form-label mb-2">
            <span v-if="preLabel"><span :class="preLabel" aria-hidden="true"></span> </span>{{ label }}
        </legend>
        <div>
            <div class="form-check" :class="{'form-check-inline': inline}" v-for="(subLabel, subValue, index) in items" :key="subValue">
                <input :id="`radio${myId}${index}`" class="form-check-input" :class="{'is-invalid': hasError}"
                       type="radio" :name="name" :value="subValue" :checked="myValue === normalizeValue(subValue)"
                       :disabled="disabled" :aria-invalid="hasError ? 'true' : 'false'" @input="changed($event)" />
                <label class="form-check-label" :for="`radio${myId}${index}`">{{ subLabel }}</label>
            </div>
        </div>
        <small v-if="help" :id="helpId" class="message form-text text-muted">{{ help }}</small>
        <div v-if="hasError" :id="errorId" class="message invalid-feedback d-block">{{ errorMessage }}</div>
    </fieldset>
</template>

<script>
import { uid } from '../../../libraries/uid';
export default {
    name: "FormRadioGroup",
    emits: ['input', 'update:modelValue'],
    props: {
        id: String,
        label: String,
        name: String,
        modelValue: { type: null },
        value: String,
        help: String,
        items: Object,
        preLabel: String,
        disabled: {
            type: Boolean,
            default: false,
        },
        inline: {
            type: Boolean,
            default: true,
        }
    },
    computed: {
        errorMessage() {
            const message = this.name ? this.$page.props.errors?.[this.name] : null;
            if (!message) return '';
            return Array.isArray(message) ? message.join(' ') : message;
        },
        hasError() {
            return this.errorMessage !== '';
        },
        helpId() {
            return `${this.myId}Help`;
        },
        errorId() {
            return `${this.myId}Error`;
        },
        describedBy() {
            return [this.help ? this.helpId : null, this.hasError ? this.errorId : null].filter(Boolean).join(' ');
        },
    },
    data() {
        return {
            myId: this.id || uid(),
            myValue: (this.modelValue !== undefined ? this.modelValue : this.value) || '',
        }
    },
    watch: {
        modelValue(v) { this.myValue = v; },
        value(v) { if (this.modelValue === undefined) this.myValue = v; },
    },
    methods: {
        normalizeValue(value) {
            if (value === '') return '';
            if (!isNaN(value) && value !== null && value !== false) return parseInt(value);
            return value;
        },
        changed(event) {
            let result = Array.isArray(event.target.value) ? event.target.value[0] : event.target.value;
            if (result !== '' && !isNaN(result)) result = parseInt(result);
            if (event.target.checked) {
                this.myValue = result;
                this.$emit('input', result);
                this.$emit('update:modelValue', result);
            }
        },
    }
}
</script>

<style scoped>
.message {
    font-size: .8em;
}

</style>
