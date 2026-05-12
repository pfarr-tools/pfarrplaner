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
    <div class="service-group-select">
        <form-selectize :name="name" :label="label" :help="help"
                        :options="serviceGroups" :value="myValue"
                        @input="handleInput"
                        multiple />
    </div>
</template>

<script>
import FormSelectize from "../forms/FormSelectize";
export default {
    name: "ServiceGroupSelect",
    emits: ['update:modelValue'],
    components: {FormSelectize},
    props: ['serviceGroups', 'name', 'label', 'help', 'modelValue'],
    data() {
        var myValue = [];
        if (this.modelValue) {
            this.modelValue.forEach(item => { myValue.push(item.id)});
        }

        return {
            myValue: myValue,
        }
    },
    methods: {
        handleInput(e) {
            var items = [];
            this.serviceGroups.forEach(group => { if (e.includes(group.id.toString())) items.push(group); });
            this.$emit('update:modelValue', items);
        },
    }
}
</script>

<style scoped>

</style>
