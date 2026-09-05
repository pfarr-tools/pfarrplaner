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
    <form-group :id="myId" :input-id="`${myId}Input`" :label="label" :help="help" :name="name"
                :pre-label="preLabel" :is-checked-item="isCheckedItem" v-slot="field">
        <Multiselect
            :id="field.fieldId"
            :class="{'is-invalid': field.error}"
            v-model="myValue"
            :options="resolvedOptions"
            :value-prop="resolvedIdKey"
            :label="resolvedTitleKey"
            :track-by="resolvedTitleKey"
            :mode="multiple ? 'tags' : 'single'"
            :placeholder="placeholder"
            :disabled="disabled"
            :searchable="true"
            :name="name"
            :native-support="hasNativeSupport"
            :aria-invalid="field.error ? 'true' : 'false'"
            :aria-describedby="field.describedBy || undefined"
            locale="de"
            :no-results-text="{ de: 'Keine Ergebnisse gefunden', en: 'No results found' }"
            :no-options-text="{ de: 'Die Liste ist leer', en: 'The list is empty' }"
            :groups="isGrouped"
            :group-label="isGrouped ? '_groupLabel' : undefined"
            :group-options="isGrouped ? '_groupOptions' : undefined"
            @change="changed"
        >
            <template v-if="$slots.option" #option="slotProps">
                <slot name="option" v-bind="slotProps" />
            </template>
            <template v-if="$slots.singlelabel" #singlelabel="slotProps">
                <slot name="singlelabel" v-bind="slotProps" />
            </template>
            <template v-if="$slots.tag" #tag="slotProps">
                <slot name="tag" v-bind="slotProps" />
            </template>
        </Multiselect>
    </form-group>
</template>

<script>
import FormGroup from './FormGroup';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
import { uid } from '../../../libraries/uid';

export default {
    name: 'FormSelectize',
    components: { FormGroup, Multiselect },
    props: {
        label: String,
        id: { type: null },
        type: { type: String, default: 'text' },
        name: String,
        value: { type: null },
        modelValue: { type: null },
        items: Array,
        idKey: { type: String, default: 'id' },
        titleKey: { type: String, default: 'name' },
        help: String,
        placeholder: String,
        error: String,
        settings: {},
        preLabel: String,
        multiple: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        options: { type: Array, default: () => [] },
        itemRenderer: { type: null },
        optionRenderer: { type: null },
        isCheckedItem: { type: Boolean },
    },
    emits: ['input', 'update:modelValue'],
    data() {
        const initialValue = this.modelValue !== undefined ? this.modelValue : this.value;
        return {
            myId: this.id || uid(),
            myValue: this.normalizeValue(initialValue),
        };
    },
    computed: {
        resolvedIdKey() {
            if (this.settings && this.settings.valueField) return this.settings.valueField;
            return this.idKey || 'id';
        },
        resolvedTitleKey() {
            if (this.settings && this.settings.labelField) return this.settings.labelField;
            return this.titleKey || 'name';
        },
        isGrouped() {
            return !!(this.settings && this.settings.optgroupField);
        },
        hasNativeSupport() {
            return !!this.name;
        },
        resolvedOptions() {
            let opts = [];
            if (this.options && this.options.length > 0) opts = this.options;
            else if (this.items && this.items.length > 0) opts = this.items;
            else if (this.settings && this.settings.options) opts = this.settings.options;

            if (!this.isGrouped) {
                if (this.settings && this.settings.allowEmptyOption) {
                    const emptyLabel = this.settings.emptyOptionLabel || '';
                    opts = [{ [this.resolvedIdKey]: null, [this.resolvedTitleKey]: emptyLabel }, ...opts];
                }
                return opts;
            }

            const { optgroupField, optgroupLabelField, optgroupValueField, optgroups = [] } = this.settings;
            const groups = optgroups.map(group => ({
                _groupLabel: group[optgroupLabelField],
                _groupOptions: opts.filter(item => item[optgroupField] === group[optgroupValueField]),
            }));

            if (this.settings.allowEmptyOption) {
                const emptyLabel = this.settings.emptyOptionLabel || '';
                groups.unshift({
                    _groupLabel: '',
                    _groupOptions: [{ [this.resolvedIdKey]: null, [this.resolvedTitleKey]: emptyLabel }],
                });
            }

            return groups;
        },
    },
    watch: {
        value(v) { this.myValue = this.normalizeValue(v); },
        modelValue(v) { this.myValue = this.normalizeValue(v); },
    },
    methods: {
        normalizeValue(value) {
            if (this.multiple) return Array.isArray(value) ? value : [];
            return value;
        },
        changed(newVal) {
            this.$emit('input', newVal);
            this.$emit('update:modelValue', newVal);
        },
    },
};
</script>

<style scoped>
</style>
