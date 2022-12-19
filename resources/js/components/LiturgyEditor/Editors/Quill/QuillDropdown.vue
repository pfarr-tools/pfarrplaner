<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarrplaner/pfarrplaner
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
        <span class="">
            <span class="ql-picker" :class="{'ql-expanded': t}">
                <span class="ql-picker-label" :data-label="title" :title="title" @click.prevent.stop="toggle($event)">
                    <span v-if="icon" :class="icon"></span>
                    <span style="margin-right: 15px;" :key="myLabel">{{ myLabel }}</span>
                    <svg viewBox="0 0 18 18">
                        <polygon class="ql-stroke" points="7 11 9 13 11 11 7 11"></polygon> <polygon class="ql-stroke" points="7 7 9 5 11 7 7 7"></polygon> </svg>
                </span>
                <span class="ql-picker-options">
                    <span v-for="(item,itemKey) in items" v-if="items[itemKey]"
                          class="ql-picker-item" :data-value="items[itemKey]"
                          @click.prevent.stop="set(items[itemKey])">{{ itemKey }}</span>
                </span>
            </span>
        </span>
</template>

<script>
export default {
    name: "QuillDropdown",
    props: ['title', 'label', 'icon', 'items', 'remember'],
    data() {
        return {
            t: false,
            myLabel: this.label,
        }
    },
    methods: {
        toggle(e) {
            e.preventDefault();
            e.stopPropagation();
            this.t = !this.t;
        },
        set(e) {
            this.$emit('input', e);
            if (this.remember) this.myLabel = e;
            this.t = false;
        },
    },
}
</script>

<style scoped>
    .ql-picker-label {
        padding-left: 0 !important;
    }
</style>
