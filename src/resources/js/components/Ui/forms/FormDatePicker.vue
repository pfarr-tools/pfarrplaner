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
                :pre-label="preLabel" :required="required" :value="currentValue" :is-checked-item="isCheckedItem"
                v-slot="field">
        <date-picker :id="field.fieldId" :name="name" :model-value="currentValue" :config="myDatePickerConfig"
                     :iso-date="isoDate"
                     :disabled="disabled" :required="required" :aria-required="required ? 'true' : 'false'"
                     :aria-invalid="field.error ? 'true' : 'false'" :aria-describedby="field.describedBy || undefined"
                     @update:modelValue="handleModelUpdate" @input="handleInputEvent" @dp-update="$emit('dp-update', $event)"/>
    </form-group>
</template>

<script>
import FormGroup from "./FormGroup";
import { uid } from '../../../libraries/uid';
import { DateTime } from 'luxon';

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
        return {
            myId: this.id || uid(),
            myDatePickerConfig: this.config || {
                locale: 'de',
                format: 'DD.MM.YYYY',
                showClear: true,
            },
        }
    },
    methods: {
        toLuxonFormat(format) {
            return (format || 'DD.MM.YYYY')
                .replace(/DD/g, 'dd')
                .replace(/YYYY/g, 'yyyy');
        },
        berlinFromLocalParts(parts) {
            return DateTime.fromObject(
                {
                    year: parts.year,
                    month: parts.month,
                    day: parts.day,
                    hour: parts.hour || 0,
                    minute: parts.minute || 0,
                    second: parts.second || 0,
                    millisecond: parts.millisecond || 0,
                },
                {
                    zone: 'Europe/Berlin',
                    locale: this.myDatePickerConfig.locale || 'de',
                }
            );
        },
        toIsoDate(value) {
            if (!value) return value;

            const luxonFormat = this.toLuxonFormat(this.myDatePickerConfig.format);
            let dateTime = null;

            if (value instanceof Date) {
                const local = DateTime.fromJSDate(value);
                dateTime = this.berlinFromLocalParts(local);
            } else if (typeof value === 'string' && value.includes('T')) {
                const iso = DateTime.fromISO(value, { setZone: true });
                if (iso.isValid) {
                    dateTime = this.berlinFromLocalParts(iso);
                }
            }

            if (!dateTime && this.myDatePickerConfig.format == 'DD.MM.YYYY') {
                dateTime = DateTime.fromFormat(value, luxonFormat, {
                    zone: 'Europe/Berlin',
                    locale: this.myDatePickerConfig.locale || 'de',
                });
            }

            if (!dateTime) {
                dateTime = DateTime.fromFormat(value, luxonFormat, {
                    zone: 'Europe/Berlin',
                    locale: this.myDatePickerConfig.locale || 'de',
                });
            }

            return dateTime.isValid ? dateTime.toUTC().toISO() : value;
        },
        handleModelUpdate(value) {
            this.$emit('update:modelValue', this.isoDate ? this.toIsoDate(value) : value);
        },
        handleInputEvent(e) {
            let out;
            if (this.isoDate) {
                out = this.toIsoDate(e);
            } else {
                out = e;
            }
            this.$emit('input', out);
        },
    }
};
</script>

<style scoped>

</style>
