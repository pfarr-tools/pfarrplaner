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
    <form-group :id="id" :label="label" :help="help" :name="name" :pre-label="preLabel" :required="required"
                :value="myValue" :is-checked-item="isCheckedItem">
        <date-picker :name="name" v-model="myValue" :config="myDatePickerConfig" :disabled="disabled" :required="required"
                     :aria-required="required" @input="handleInputEvent" @dp-update="$emit('dp-update', $event)"/>
    </form-group>
</template>

<script>
import FormGroup from "./FormGroup";
import ValueCheck from "../elements/ValueCheck";

export default {
    name: "FormDatePicker",
    components: {FormGroup},
    emits: ['input', 'update:modelValue', 'dp-update'],
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
        config: Object,
        isoDate: Boolean,
    },
    computed: {
        currentValue() {
            return this.modelValue !== undefined ? this.modelValue : this.value;
        }
    },
    data() {
        const v = this.modelValue !== undefined ? this.modelValue : this.value;
        return {
            myValue: v,
            myDatePickerConfig: this.config || {
                locale: 'de',
                format: 'DD.MM.YYYY',
                showClear: true,
            },
        }
    },
    watch: {
        currentValue(v) {
            this.myValue = v;
        }
    },
    methods: {
        handleInputEvent(e) {
            let out;
            if (this.isoDate) {
                if (this.myDatePickerConfig.format == 'DD.MM.YYYY') {
                    out = moment.utc(e, this.myDatePickerConfig.format).toISOString();
                } else {
                    out = moment(e, this.myDatePickerConfig.format).toISOString();
                }
            } else {
                out = e;
            }
            this.$emit('input', out);
            this.$emit('update:modelValue', out);
        },
    }
};
</script>

<style scoped>

</style>
