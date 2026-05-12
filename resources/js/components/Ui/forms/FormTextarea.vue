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
    <form-group :id="myId" :label="label" :help="help" :name="name" :pre-label="preLabel" :required="required">
        <textarea class="form-control" :class="{'is-invalid': $page.props.errors[name]}" :rows="rows" v-model="myValue" :id="myId+'Input'"
               :placeholder="placeholder" :aria-placeholder="placeholder" :disabled="disabled" :name="name" :aria-required="required"
               @input="onInput($event.target.value)" />
    </form-group>
</template>

<script>
import FormGroup from "./FormGroup";
import { uid } from '../../../libraries/uid';
export default {
    name: "FormTextarea",
    components: {FormGroup},
    emits: ['input', 'update:modelValue'],
    props: {
        label: String,
        id: String,
        rows: {
            type: Number,
            default: 5,
        },
        name: String,
        modelValue: { type: null },
        value: String,
        help: String,
        placeholder: String,
        error: String,
        preLabel: String,
        required: Boolean,
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    mounted() {
        if (this.myId == '') this.myId = uid();
    },
    data() {
        return {
            myId: this.id || '',
            myValue: this.modelValue !== undefined ? this.modelValue : this.value,
        }
    },
    watch: {
        modelValue(v) { this.myValue = v; },
        value(v) { if (this.modelValue === undefined) this.myValue = v; },
    },
    methods: {
        onInput(v) {
            this.myValue = v;
            this.$emit('input', v);
            this.$emit('update:modelValue', v);
        }
    }
}
</script>

<style scoped>

</style>
