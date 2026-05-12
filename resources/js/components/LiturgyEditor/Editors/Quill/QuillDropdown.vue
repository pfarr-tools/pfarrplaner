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
    <div class="dropdown d-inline-block">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                :title="title" @click.prevent.stop="toggle">
            <span v-if="icon" :class="icon"></span>
            {{ myLabel }}
        </button>
        <div class="dropdown-menu" :class="{'show': t}">
            <button v-for="(item, itemKey) in items" :key="itemKey" v-if="items[itemKey]"
                    class="dropdown-item" type="button"
                    @click.prevent.stop="set(items[itemKey])">{{ itemKey }}</button>
        </div>
    </div>
</template>

<script>
export default {
    name: "QuillDropdown",
    emits: ['input'],
    props: ['title', 'label', 'icon', 'items', 'remember'],
    data() {
        return {
            t: false,
            myLabel: this.label,
        }
    },
    methods: {
        toggle() {
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
