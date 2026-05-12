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

<!--
  Compatibility shim: replaces vue-bootstrap-datetimepicker's <date-picker> component.
  Accepts the same :config prop (moment-style format strings) and v-model binding,
  but renders @vuepic/vue-datepicker internally.
-->
<template>
    <VueDatePicker
        :model-value="internalDate"
        :formats="{ input: dpFormat }"
        :enable-time-picker="hasTime"
        :locale="locale"
        :clearable="showClear"
        :disabled="disabled"
        text-input
        :text-input-options="{ format: dpFormat }"
        auto-apply
        @update:model-value="onDateChange"
    />
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import * as dateFnsLocales from 'date-fns/locale';

export default {
    name: 'DatePickerShim',
    components: { VueDatePicker },
    props: {
        modelValue: { type: null, default: null },
        config: { type: Object, default: () => ({ locale: 'de', format: 'DD.MM.YYYY' }) },
        disabled: { type: Boolean, default: false },
        isoDate: { type: Boolean, default: false },
    },
    computed: {
        momentFormat() { return (this.config && this.config.format) || 'DD.MM.YYYY'; },
        dpFormat() { return this.momentFormat.replace(/DD/g, 'dd').replace(/YYYY/g, 'yyyy'); },
        localeString() { return (this.config && this.config.locale) || 'de'; },
        locale() {
            const key = this.localeString.replace('-', '');
            return dateFnsLocales[key] || dateFnsLocales[this.localeString] || dateFnsLocales.de;
        },
        hasTime() { return this.momentFormat.includes('HH'); },
        showClear() { return !!(this.config && this.config.showClear); },
    },
    data() {
        return { internalDate: this.parseInput(this.modelValue) };
    },
    watch: {
        modelValue(v) { this.internalDate = this.parseInput(v); },
    },
    methods: {
        parseInput(v) {
            if (!v) return null;
            if (v instanceof Date) return isNaN(v.getTime()) ? null : v;
            const m = window.moment(v, this.momentFormat, true);
            if (m.isValid()) return m.toDate();
            const fallback = window.moment(v);
            return fallback.isValid() ? fallback.toDate() : null;
        },
        onDateChange(date) {
            if (!date) {
                this.$emit('update:modelValue', null);
                this.$emit('input', null);
                return;
            }
            const formatted = window.moment(date).format(this.momentFormat);
            this.$emit('update:modelValue', formatted);
            if (this.isoDate) {
                this.$emit('input', window.moment(date).toISOString());
            } else {
                this.$emit('input', formatted);
            }
        },
    },
};
</script>
