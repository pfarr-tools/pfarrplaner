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
    <form-group :id="myId" :label="label" :help="help" :name="name" :pre-label="preLabel" :required="required"
                :value="currentValue" :is-checked-item="isCheckedItem">
        <input class="form-control" :class="{'is-invalid': $page.props.errors[name], 'checked-input': isCheckedItem}" :type="type" v-model="myValue" :id="myId+'Input'"
               :placeholder="placeholder" :aria-placeholder="placeholder" :autofocus="autofocus"
               @input="onInput($event.target.value)" :disabled="disabled"
               :required="required" :aria-required="required" :name="name"/>
    </form-group>
</template>

<script>
import FormGroup from "./FormGroup";
import ValueCheck from "../elements/ValueCheck";
import { uid } from '../../../libraries/uid';

export default {
    name: "FormInput",
    components: {ValueCheck, FormGroup},
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
        preLabel: String,
        autofocus: Boolean,
        disabled: {
            type: Boolean,
            default: false,
        },
        handleInput: Object,
        isCheckedItem: {
            type: null,
            default: false,
        },
    },
    computed: {
        currentValue() {
            return this.modelValue !== undefined ? this.modelValue : this.value;
        }
    },
    mounted() {
        if (this.myId == '') this.myId = uid();
    },
    data() {
        return {
            errors: this.$page.props.errors,
            error: this.$page.props.errors[this.name] || false,
            myId: this.id || '',
            myValue: this.modelValue !== undefined ? this.modelValue : this.value,
        }
    },
    methods: {
        onInput(v) {
            this.myValue = v;
            this.$emit('input', v);
            this.$emit('update:modelValue', v);
        }
    },
    watch: {
        currentValue(newVal) {
            this.myValue = newVal;
            if (this.required) {
                this.error = this.$page.props.errors[this.name] = newVal ? '' : 'Dieses Feld darf nicht leer bleiben.';
                this.$forceUpdate();
            }
        }
    },
}
</script>

<style scoped>
</style>
