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
    <form-group :id="myId" :label="label" :help="help" :name="name">
        <Multiselect
            :id="myId + 'Input'"
            v-model="myValue"
            :options="groupedOptions"
            value-prop="id"
            label="name"
            track-by="name"
            :mode="multiple ? 'tags' : 'single'"
            :groups="true"
            group-label="label"
            group-options="options"
            :searchable="true"
            :create-option="true"
            :placeholder="placeholder || ''"
            @change="locationChanged"
        />
    </form-group>
</template>

<script>
import FormGroup from "../forms/FormGroup";
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
import { uid } from '../../../libraries/uid';

export default {
    name: "LocationSelect",
    emits: ['input', 'update:modelValue', 'set-location'],
    components: { FormGroup, Multiselect },
    props: {
        label: String,
        id: String,
        name: String,
        modelValue: { type: null },
        value: { type: null },
        help: String,
        placeholder: String,
        locations: Array,
        error: String,
        useInput: Boolean,
        returnObject: Boolean,
        multiple: Boolean,
    },
    mounted() {
        if (this.myId === '') this.myId = uid();
    },
    data() {
        const initVal = this.modelValue !== undefined ? this.modelValue : this.value;
        let myValue = this.multiple ? [] : null;

        if (initVal !== null && initVal !== undefined) {
            if (this.multiple && Array.isArray(initVal)) {
                myValue = initVal.map(item => typeof item === 'object' ? item.id : item);
            } else if (typeof initVal === 'object') {
                myValue = initVal.id;
            } else {
                myValue = initVal;
            }
        }

        return {
            myId: this.id || '',
            myValue,
        };
    },
    computed: {
        groupedOptions() {
            const cityGroups = {};
            (this.locations || []).forEach(item => {
                const cityName = item.city?.name || '';
                if (!cityGroups[cityName]) cityGroups[cityName] = [];
                cityGroups[cityName].push(item);
            });

            const groupedOptions = Object.keys(cityGroups).map(cityName => ({
                label: cityName,
                options: cityGroups[cityName],
            }));

            const freiOptions = [];
            const currentValues = this.multiple ? (Array.isArray(this.myValue) ? this.myValue : []) : [this.myValue];
            currentValues.forEach(value => {
                if ((value !== null) && (value !== undefined) && String(value).trim() !== '' && isNaN(value)) {
                    freiOptions.push({ id: value, name: value });
                }
            });

            groupedOptions.push({ label: 'Freie Ortsangabe', options: freiOptions });

            return groupedOptions;
        },
    },
    watch: {
        value(newVal) {
            this.myValue = this.normalizeValue(newVal);
        },
        modelValue(newVal) {
            this.myValue = this.normalizeValue(newVal);
        },
    },
    methods: {
        normalizeValue(value) {
            if (this.multiple) {
                if (Array.isArray(value)) return value.map(item => typeof item === 'object' ? item.id : item);
                return [];
            }
            if ((value !== null) && (value !== undefined) && (typeof value === 'object')) return value.id;
            return value ?? null;
        },

        /**
         * Called by Multiselect @change. newVal is the value-prop value ('id').
         * @param {string|number|null} newVal
         */
        locationChanged(newVal) {
            if (newVal === null || newVal === undefined) {
                this.sendEvent(null);
                return;
            }

            if (this.multiple && Array.isArray(newVal)) {
                this.sendEvent(newVal);
                return;
            }

            if (this.returnObject) {
                if (!isNaN(newVal) && newVal !== '') {
                    const found = (this.locations || []).find(loc => loc.id == newVal);
                    this.sendEvent(found || newVal);
                } else {
                    // freetext string
                    this.sendEvent(String(newVal));
                }
            } else {
                this.sendEvent(newVal);
            }
        },

        /**
         * @param {Object|string|null} found
         */
        sendEvent(found) {
            if (this.useInput) {
                this.$emit('input', found);
                this.$emit('update:modelValue', found);
            } else {
                this.$emit('set-location', found);
            }
        },
    },
}
</script>

<style scoped>
</style>
