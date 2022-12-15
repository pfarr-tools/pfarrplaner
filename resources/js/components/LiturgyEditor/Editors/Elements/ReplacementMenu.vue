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
    <div class="dropdown-menu bg-light" :style="{display: menu.show ? 'block' : 'none'}"
         aria-labelledby="dropdownMenuButton">
        <a v-for="(item,itemKey) in myItems" :key="itemKey"
           class="dropdown-item" :title="item.title || ''"
           href="#" @click.prevent.stop="submit(item)">
            {{ itemKey }}: {{ item || '' }}
        </a>
    </div>
</template>

<script>
export default {
    name: "ReplacementMenu",
    props: ['menu'],
    data() {
        let myItems = {};
        // filter empty items
        for (let itemKey in this.menu.items) {
            if (this.menu.items[itemKey]) myItems[itemKey] = this.menu.items[itemKey];
        }
        return { myItems }
    },
    methods: {
        submit(text) {
            this.$emit('input', text);
        }
    }
}
</script>

<style scoped>
</style>
