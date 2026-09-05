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
    <form-group
        :label="label"
        :help="help"
        :is-checked-item="isCheckedItem"
        :pre-label="preLabel"
    >
        <VueDatePicker
            :model-value="internalRange"
            range
            multi-calendars
            :inline="inline"
            timezone="Europe/Berlin"
            :locale="dpLocale"
            :formats="{ input: 'dd.MM.yyyy' }"
            :enable-time-picker="false"
            text-input
            :text-input-options="{ format: 'dd.MM.yyyy' }"
            auto-apply
            :disabled="disabled"
            @update:model-value="onRangeChange"
        />
    </form-group>
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import FormGroup from "../forms/FormGroup";
import * as dateFnsLocales from 'date-fns/locale';
import { formatPlannerDateForDisplay, plannerValueToPickerDate } from "../../../helpers/plannerDates";

export default {
    name: "DateRangeInput",
    components: { FormGroup, VueDatePicker },

    props: {
        modelValue: { type: Array, default: null },
        from: { type: null, default: null },
        to: { type: null, default: null },
        label: String,
        preLabel: String,
        help: String,
        isCheckedItem: Boolean,
        disabled: Boolean,
        inline: {
            type: Boolean,
            default: false,
        },
    },

    emits: ["update:modelValue", "input"],

    computed: {
        dpLocale() {
            return dateFnsLocales.de;
        },

        internalRange() {
            if (this.modelValue && this.modelValue.length === 2) {
                return [this.toDate(this.modelValue[0]), this.toDate(this.modelValue[1])];
            }
            if (this.from && this.to) {
                return [this.toDate(this.from), this.toDate(this.to)];
            }
            return null;
        },
    },

    methods: {
        toDate(val) {
            return plannerValueToPickerDate(val);
        },

        formatDisplay(dates) {
            if (!dates || !dates[0]) return '';
            const from = formatPlannerDateForDisplay(dates[0]);
            if (!dates[1]) return from;
            return `${from} – ${formatPlannerDateForDisplay(dates[1])}`;
        },

        onRangeChange(range) {
            if (!range || range.length < 2 || !range[1]) return;
            const start = plannerValueToPickerDate(range[0]);
            const end = plannerValueToPickerDate(range[1]);
            this.$emit('update:modelValue', [start, end]);
            this.$emit('input', [start, end]);
        },
    },
};
</script>
