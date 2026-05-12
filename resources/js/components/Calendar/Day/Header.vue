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
    <th class="day-header-cell"
        v-bind:class="{
            now: false, // TODO: next day
            limited: day.day_type == 1, // DAY_TYPE_LIMITED
            collapsed: this.day.collapsed,
            'scroll-to-me': scrollToMe,
        }"
        :ref="scrollToMe ? 'scrollToMe' : null"
        @click="clickHandler()"
        :title="day.day_type == 1 ? today.format('DD.MM.YYYY')+' (Klicken, um Ansicht umzuschalten)' : ''"
        :data-day="day.id">
        <div class="day-header-collapse-hover">{{ today.format('dddd, DD.') }}</div>
        <div class="card card-effect">
            <div class="card-header bg-dark" :class="{'bg-danger': today.format('E') == 7}">
                {{ today.format('dddd') }}
            </div>
            <div class="card-body">
                <div class="kw">
                    <span>{{ today.format('W') }}</span>
                </div>
                {{ today.format('D') }}
            </div>
            <div class="liturgy">
                <div class="liturgy-sermon" v-if="day.liturgy.perikope">
                    <div :class="day.liturgy.litColor" class="liturgy-color" :title="day.liturgy.feastCircleName"></div>
                    <bible-reference :perikope="day.liturgy.perikope" title=""/>
                </div>
            </div>
            <div class="card-footer day-name" :title="day.liturgy.title" v-if="day.liturgy.title">
                {{ day.liturgy.title }}
            </div>
        </div>
        <div v-if="hasPermission('urlaub-lesen')">
            <div class="vacation me-1" v-for="(absence,absenceIndex,absenceKey) in day.absences" :absence="absence"
                 :key="absenceKey"
                 :title="absence.label">
                <span class="mdi mdi-earth"></span> {{ absence.name }}
            </div>
        </div>
    </th>
</template>

<script>
import dayjs from 'dayjs';
import EventBus from "../../../plugins/EventBus";
import {CalendarToggleDayColumnEvent} from "../../../events/CalendarToggleDayColumnEvent";
import BibleReference from "../../LiturgyEditor/Elements/BibleReference";

export default {
    name: 'CalendarDayHeader',
    components: {BibleReference},
    props: ['day', 'index', 'scrollToDate'],
    computed: {
        today() {
            return dayjs(this.day.date).locale('de');
        },
    },
    data: function () {
        var scrollToMe = false;
        if (this.scrollToDate) {
            if (dayjs(this.scrollToDate).format('YYYYMMDD') == dayjs(this.date).format('YYYYMMDD')) {
                scrollToMe = true;
            }
        }


        return {
            limited: this.day.day_type == 1,
            scrollToMe,
            currentUser: this.$page.props.currentUser.data.id,
        }
    },
    methods: {
        clickHandler: function () {
            this.$emit('collapse', {day: this.day, state: !(this.day.collapsed)});
            this.$forceUpdate();
        },
    },
}
</script>

<style scoped>
.liturgy-color.white {
    background-color: white;
    border-color: darkgray;
}

.liturgy-color.black {
    background-color: black;
}

.liturgy-color.green {
    background-color: darkgreen;
}

.liturgy-color.purple {
    background-color: rebeccapurple;
}

th.day-header-cell .card-body {
    line-height: .5em;
    padding-bottom: .75em;
}

.kw {
    font-size: .3em !important;
    font-weight: normal;
    text-align: left;
    color: gray;
    padding: 1px;
}


:deep(.bible-reference), :deep(.bible-reference div) {
    display: inline;
}

</style>
