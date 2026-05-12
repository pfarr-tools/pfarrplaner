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
  Single range datepicker replacing two adjacent <form-date-picker> fields.
  Outputs two hidden <input> elements (nameFrom / nameTo) for native form submission
  in d.m.Y format, matching what the backend's Carbon::createFromFormat expects.
-->

<template>
    <form-group :label="label">
        <VueDatePicker
            :model-value="internalRange"
            range
            multi-calendars
            :locale="dpLocale"
            :formats="{ input: 'dd.MM.yyyy' }"
            :enable-time-picker="false"
            text-input
            :text-input-options="{ format: 'dd.MM.yyyy' }"
            auto-apply
            :disabled="disabled"
            @update:model-value="onRangeChange"
        />
        <input type="hidden" :name="nameFrom" :value="hiddenFrom" />
        <input type="hidden" :name="nameTo" :value="hiddenTo" />
    </form-group>
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import FormGroup from './FormGroup';
import * as dateFnsLocales from 'date-fns/locale';

export default {
    name: 'FormDateRangePicker',
    components: { FormGroup, VueDatePicker },

    props: {
        from: { type: null, default: null },
        to: { type: null, default: null },
        label: String,
        nameFrom: { type: String, default: 'start' },
        nameTo: { type: String, default: 'end' },
        isoDate: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
    },

    emits: ['update:from', 'update:to'],

    computed: {
        dpLocale() {
            return dateFnsLocales.de;
        },

        internalRange() {
            return [this.toDate(this.from), this.toDate(this.to)];
        },

        hiddenFrom() {
            if (!this.from) return '';
            return window.moment(this.from).format('DD.MM.YYYY');
        },

        hiddenTo() {
            if (!this.to) return '';
            return window.moment(this.to).format('DD.MM.YYYY');
        },
    },

    methods: {
        toDate(val) {
            if (!val) return null;
            const m = window.moment(val);
            if (!m.isValid()) return null;
            return new Date(Date.UTC(m.year(), m.month(), m.date()));
        },

        formatDisplay(dates) {
            if (!dates || !dates[0]) return '';
            const from = window.moment(dates[0]).format('DD.MM.YYYY');
            if (!dates[1]) return from;
            return `${from} – ${window.moment(dates[1]).format('DD.MM.YYYY')}`;
        },

        onRangeChange(range) {
            if (!range || range.length < 2 || !range[1]) return;
            const start = window.moment(range[0]).startOf('day');
            const end = window.moment(range[1]).endOf('day');
            if (this.isoDate) {
                this.$emit('update:from', start.toISOString());
                this.$emit('update:to', end.toISOString());
            } else {
                this.$emit('update:from', start.format('DD.MM.YYYY'));
                this.$emit('update:to', end.format('DD.MM.YYYY'));
            }
        },
    },
};
</script>
