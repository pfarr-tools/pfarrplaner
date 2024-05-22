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

<script>

import Selectize from "vue2-selectize";

export default {
    name: "CalendarSelect",
    props: ['value', 'calendars'],
    components: {Selectize},
    data() {
        let myGroups = [];
        let setGroups = [];
        this.calendars.forEach(item => {
            if (undefined == setGroups[item.group]) {
                setGroups[item.group] = true;
                myGroups.push({group: item.group});
            }
        });

        return {
            myValue: this.value,
            mySettings: {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'category'],
                optgroupField: 'category',
                optgroupLabelField: 'group',
                optgroupValueField: 'group',
                optgroups: myGroups,
                options: this.calendars,
            }
        }
    }
}
</script>

<template>
    <div class="calendar-select">
        <selectize class="form-control ms-1 mt-1" v-model="myValue" :options="calendars" :settings="mySettings" @input="$emit('input', $event)"/>
    </div>
</template>

<style scoped>
.calendar-select {
    min-width: 200px;
}
</style>
