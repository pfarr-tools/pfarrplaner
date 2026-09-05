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
import FormGroup from "./FormGroup.vue";

export default {
    name: "FormSkipLabelsInput",
    props: ['label', 'labels', 'length', 'modelValue'],
    emits: ['update:modelValue', 'input'],
    components: {FormGroup},
    data() {
        return {
            myValue: this.modelValue,
        }
    },
    computed: {
        states() {
            return Array.from({ length: this.labels }, (_, index) => index);
        },
    },
    watch: {
        modelValue(v) { this.myValue = v; },
    },
    methods: {
        setValue(value) {
            this.myValue = value;
            this.$emit('update:modelValue', value);
            this.$emit('input', value);
        }
    }
}
</script>

<template>
    <form-group :label="label">
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="row page m-3" role="listbox" aria-label="Druckbeginn für Etiketten">
                    <button v-for="labelIndex in states" :key="labelIndex" type="button"
                         class="col-4 label p-3"
                         :class="labelIndex < myValue ? 'skipped' : (labelIndex < myValue+length ? 'active' : 'empty')"
                         :aria-selected="labelIndex === myValue ? 'true' : 'false'"
                         @click="setValue(labelIndex)"
                    >
                        <span v-if="(labelIndex >= myValue) && (labelIndex < myValue+length)">Dieses Etikett wird bedruckt.</span>
                        <span v-else class="visually-hidden">Druck beginnt ab diesem Etikett.</span>
                    </button>
                </div>
            </div>
        </div>
    </form-group>
</template>

<style scoped>
    .page {
        background-color: white;
        border: solid 1px black;
    }
    .label {
        background-color: lightgray;
        border: solid 1px black;
        min-height: 7em !important;
        font-size: .6em;
        text-align: left;
    }

    .label.active, .label.empty {
        background-color: white;
    }
</style>
