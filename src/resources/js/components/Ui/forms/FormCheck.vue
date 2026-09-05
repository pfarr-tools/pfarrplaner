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
    <form-group :id="myId" :input-id="`${myId}Input`" :help="help" :name="name" :value="currentValue"
                :is-checked-item="isCheckedItem" v-slot="field">
        <div class="form-check">
            <input v-if="name" type="hidden" :name="name" value="0" />
            <input :id="field.fieldId" type="checkbox" class="form-check-input"
                   :class="{'is-invalid': field.error}" :name="name" :checked="isChecked" value="1"
                   :disabled="disabled" :aria-invalid="field.error ? 'true' : 'false'"
                   :aria-describedby="field.describedBy || undefined" @input="handleInput"/>
            <label class="form-check-label" v-if="label" :for="field.fieldId">{{ label }}</label>
            <span v-if="isCheckedItem" :class="myValue ? 'mdi mdi-check-circle' : 'mdi mdi-close-circle'" :key="myValue"></span>
        </div>
    </form-group>
</template>

<script>
import FormGroup from "./FormGroup";
import { uid } from '../../../libraries/uid';
export default {
    name: "FormCheck",
    components: {FormGroup},
    emits: ['input', 'update:modelValue'],
    props: {
        label: String,
        id: String,
        name: String,
        modelValue: { type: null },
        value: { type: null },
        help: String,
        preLabel: String,
        disabled: {
            type: Boolean,
            default: false,
        },
        isCheckedItem: Boolean,
    },
    computed: {
        currentValue() {
            return this.modelValue !== undefined ? this.modelValue : this.value;
        },
        isChecked() {
            return (this.myValue) && (this.myValue != 0) && (this.myValue != '0');
        }
    },
    data() {
        return {
            myId: this.id || uid(),
            myValue: this.modelValue !== undefined ? this.modelValue : this.value,
        }
    },
    watch: {
        modelValue(v) { this.myValue = v; },
        value(v) { if (this.modelValue === undefined) this.myValue = v; },
    },
    methods: {
        handleInput(event) {
            this.myValue = event.target.checked ? 1: 0;
            this.$emit('input', this.myValue);
            this.$emit('update:modelValue', this.myValue);
        }
    }
}
</script>

<style scoped>
    .mdi-check-circle {
        color: green;
    }
    .mdi-close-circle {
        color: red;
    }
</style>
