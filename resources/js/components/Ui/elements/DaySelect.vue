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
    <form-group :id="id" :name="name" :label="label" :help="help">
        <select :id="id+'Input'" :name="name" class="form-control"
                :class="{'is-invalid' :error}" v-model="myValue"
                @input="changed">
            <option v-for="day in days" :value="day.id">{{ moment(day.date).format('DD.MM.YYYY') }}</option>
        </select>
    </form-group>
</template>

<script>
import FormGroup from "../forms/FormGroup";
import { uid } from '../../../libraries/uid';

export default {
    name: "DaySelect",
    emits: ['input', 'update:modelValue'],
    components: {FormGroup},
    props: {
        label: String,
        id: String,
        type: {
            type: String,
            default: 'text',
        },
        name: String,
        modelValue: { type: null },
        value: Object,
        help: String,
        placeholder: String,
        error: String,
        city: Object,
        days: Array,
    },
    mounted() {
        if (this.myId == '') this.myId = uid();
    },
    data() {
        const initVal = this.modelValue !== undefined ? this.modelValue : this.value;
        return {
            myId: this.id || '',
            myValue: initVal ? initVal.id : null,
        }
    },
    methods: {
        changed(event) {
            var found = false;
            const target = event.target.value;
            this.days.forEach(function (day) {
                if (target == day.id) found = day;
            });
            if (found) {
                this.myValue = found.id;
                this.$emit('input', found);
                this.$emit('update:modelValue', found);
            }
        }
    },

}
</script>

<style scoped>

</style>
