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
    <td valign="top"
        class="calendar-cell"
        v-bind:class="{
            now: false, // TODO: next day
        }"
    >
        <div class="celldata">
            <template v-if="loading">
                <calendar-service-skeleton v-for="index in skeletonCount" :key="'skeleton_'+index" />
            </template>
            <div v-for="(service,index) in services" :key="service.id">
                <calendar-service :service="service" :key="service.id" :index="index" :city="city"
                                  :targetMode="targetMode" :target="target"/>
            </div>
        </div>
    </td>
</template>
<script>
import EventBus from "../../plugins/EventBus";
import {CalendarToggleDayColumnEvent} from "../../events/CalendarToggleDayColumnEvent";
import NavButton from "../Ui/buttons/NavButton";
import CalendarService from "./Service.vue";
import CalendarServiceSkeleton from "./Service/Skeleton.vue";

export default {
    name: 'CalendarCell',
    props: ['city', 'day', 'services', 'targetMode', 'target', 'loading'],
    components: {CalendarServiceSkeleton, CalendarService, NavButton},
    computed: {
        skeletonCount() {
            return this.city?.is_org ? 3 : 2;
        }
    }
}
</script>
<style scoped>
.calendar-cell {
    padding: 0;
}

.city-loading {
    text-align: center;
    background-color: transparent !important;
    color: lightgray !important;
    font-size: 3em !important;
}


</style>
