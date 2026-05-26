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
  Outputs two hidden <input> elements (nameFrom / nameTo) for native form submission.
  With isoDate enabled, values are submitted as YYYY-MM-DD to avoid UTC shifts.
-->

<template>
    <form-group :id="myId" :input-id="`${myId}Input`" :label="label" v-slot="field">
        <VueDatePicker
            :id="field.fieldId"
            :model-value="pickerRange"
            range
            multi-calendars
            :locale="dpLocale"
            :formats="{ input: 'dd.MM.yyyy' }"
            :enable-time-picker="false"
            text-input
            :text-input-options="{ format: 'dd.MM.yyyy' }"
            auto-apply
            :disabled="disabled"
            :aria-describedby="field.describedBy || undefined"
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
import { uid } from '../../../libraries/uid';

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

    data() {
        return {
            myId: uid(),
            pickerRange: [null, null],
        };
    },

    computed: {
        dpLocale() {
            return dateFnsLocales.de;
        },

        hiddenFrom() {
            if (!this.from) return '';
            return this.isoDate
                ? this.formatIsoDate(this.from)
                : this.formatDisplayDate(this.from);
        },

        hiddenTo() {
            if (!this.to) return '';
            return this.isoDate
                ? this.formatIsoDate(this.to)
                : this.formatDisplayDate(this.to);
        },
    },

    watch: {
        from: {
            immediate: true,
            handler() {
                this.syncPickerRangeFromProps();
            },
        },
        to: {
            immediate: true,
            handler() {
                this.syncPickerRangeFromProps();
            },
        },
    },

    methods: {
        syncPickerRangeFromProps() {
            const nextRange = [this.toDate(this.from), this.toDate(this.to)];
            if (!this.isSameRange(this.pickerRange, nextRange)) {
                this.pickerRange = nextRange;
            }
        },

        toDate(val) {
            if (!val) return null;
            if (val instanceof Date) {
                return isNaN(val.getTime()) ? null : val;
            }
            if (typeof val !== 'string') return null;

            let match = val.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (match) {
                return new Date(Date.UTC(parseInt(match[1]), parseInt(match[2]) - 1, parseInt(match[3])));
            }

            match = val.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
            if (match) {
                return new Date(Date.UTC(parseInt(match[3]), parseInt(match[2]) - 1, parseInt(match[1])));
            }

            const nativeDate = new Date(val);
            if (isNaN(nativeDate.getTime())) return null;
            return new Date(Date.UTC(
                nativeDate.getUTCFullYear(),
                nativeDate.getUTCMonth(),
                nativeDate.getUTCDate()
            ));
        },

        formatDisplayDate(value) {
            const date = this.toDate(value);
            if (!date) return '';
            const day = String(date.getUTCDate()).padStart(2, '0');
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const year = String(date.getUTCFullYear());
            return `${day}.${month}.${year}`;
        },

        formatIsoDate(value) {
            const date = this.toDate(value);
            if (!date) return '';
            const day = String(date.getUTCDate()).padStart(2, '0');
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const year = String(date.getUTCFullYear());
            return `${year}-${month}-${day}`;
        },

        isSameDate(a, b) {
            if (!a && !b) return true;
            if (!a || !b) return false;
            return a.getTime() === b.getTime();
        },

        isSameRange(a, b) {
            return this.isSameDate(a?.[0] || null, b?.[0] || null)
                && this.isSameDate(a?.[1] || null, b?.[1] || null);
        },

        onRangeChange(range) {
            if (!range || !range[0]) {
                this.pickerRange = [null, null];
                this.$emit('update:from', null);
                this.$emit('update:to', null);
                return;
            }
            if (range.length < 2 || !range[1]) return;
            const start = this.toDate(range[0]);
            const end = this.toDate(range[1]);
            const nextRange = [start, end];
            if (!this.isSameRange(this.pickerRange, nextRange)) {
                this.pickerRange = nextRange;
            }
            if (this.isoDate) {
                const nextFrom = this.formatIsoDate(start);
                const nextTo = this.formatIsoDate(end);
                if (nextFrom !== this.from) {
                    this.$emit('update:from', nextFrom);
                }
                if (nextTo !== this.to) {
                    this.$emit('update:to', nextTo);
                }
            } else {
                const nextFrom = this.formatDisplayDate(start);
                const nextTo = this.formatDisplayDate(end);
                if (nextFrom !== this.from) {
                    this.$emit('update:from', nextFrom);
                }
                if (nextTo !== this.to) {
                    this.$emit('update:to', nextTo);
                }
            }
        },
    },
};
</script>
