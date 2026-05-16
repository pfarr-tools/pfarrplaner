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
            sunday: today.format('E') == 7,
        }"
        :ref="scrollToMe ? 'scrollToMe' : null"
        @click="clickHandler()"
        :title="headerTitle"
        :data-day="day.id">
        <div class="day-header-panel">
            <div class="day-header-top">
                <div class="day-header-title">
                    <span class="day-weekday">{{ today.format('dd') }}</span>
                    <span class="day-date">{{ today.format('D.') }}</span>
                </div>
                <div class="day-week-number">KW {{ weekNumber }}</div>
            </div>

            <div class="day-header-subtitle">
                {{ today.format('dddd, DD.MM.') }}
            </div>

            <div v-if="normalizedLiturgy.perikope || liturgicalDayName" class="liturgy-summary">
                <div v-if="normalizedLiturgy.perikope" class="liturgy-sermon">
                    <div :class="normalizedLiturgy.litColor" class="liturgy-color" :title="normalizedLiturgy.feastCircleName"></div>
                    <bible-reference :perikope="normalizedLiturgy.perikope" title=""/>
                </div>
                <div v-if="liturgicalDayName" class="day-name" :title="liturgicalDayName">
                    {{ liturgicalDayName }}
                </div>
            </div>
        </div>
        <div v-if="hasPermission('urlaub-lesen')" class="day-absences">
            <div class="vacation" v-for="(absence,absenceIndex,absenceKey) in day.absences" :absence="absence"
                 :key="absenceKey"
                 :title="absence.label">
                <span class="mdi mdi-earth"></span> {{ absence.name }}
            </div>
        </div>
    </th>
</template>

<script>
import dayjs from 'dayjs';
import BibleReference from "../../LiturgyEditor/Elements/BibleReference";

export default {
    name: 'CalendarDayHeader',
    components: {BibleReference},
    props: ['day', 'index', 'scrollToDate'],
    computed: {
        today() {
            return dayjs(this.day.date).locale('de');
        },
        normalizedLiturgy() {
            if (Array.isArray(this.day?.liturgy)) {
                return this.day.liturgy[0] || {};
            }
            return this.day?.liturgy || {};
        },
        weekNumber() {
            return moment(this.day.date).isoWeek();
        },
        liturgicalDayName() {
            return this.normalizedLiturgy.title || this.normalizedLiturgy.name || this.normalizedLiturgy.Bezeichnung || '';
        },
        headerTitle() {
            let title = this.today.format('DD.MM.YYYY');
            if (this.day.day_type == 1) title += ' (eingeschränkter Tag)';
            return title;
        },
    },
    data: function () {
        var scrollToMe = false;
        if (this.scrollToDate) {
            if (dayjs(this.scrollToDate).format('YYYYMMDD') == dayjs(this.day.date).format('YYYYMMDD')) {
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
.day-header-cell {
    min-width: 12rem;
    padding: 0.5rem;
    background: #fff;
    cursor: default;
}

.day-header-panel {
    padding: 0.65rem 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #f8f9fa;
    line-height: 1.25;
}

.day-header-top {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
}

.day-header-title {
    display: flex;
    align-items: baseline;
    gap: 0.45rem;
    min-width: 0;
}

.day-weekday {
    font-size: 1.05rem;
    font-weight: 700;
    color: #212529;
    text-transform: capitalize;
}

.day-date {
    font-size: 1rem;
    font-weight: 700;
    color: #0d6efd;
}

.day-week-number {
    flex-shrink: 0;
    font-size: 0.72rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
}

.day-header-subtitle {
    margin-top: 0.25rem;
    font-size: 0.78rem;
    color: #6c757d;
}

.liturgy-summary {
    margin-top: 0.55rem;
    padding-top: 0.45rem;
    border-top: 1px solid #dee2e6;
}

.day-absences {
    margin-top: 0.4rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.2rem;
}

.vacation {
    display: inline-flex;
    align-items: center;
    width: auto;
    max-width: 100%;
    margin-bottom: 0;
}

.day-header-cell.limited .day-header-panel {
    border-color: #f0ad4e;
    background: #fff8e8;
}

.day-header-cell[data-day] {
    cursor: pointer;
}

.day-header-cell[data-day].scroll-to-me .day-header-panel {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
}

.day-header-cell.sunday .day-weekday,
.day-header-cell.sunday .day-date {
    color: #dc3545;
}

.day-name {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #495057;
}

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

.liturgy-color.red {
    background-color: red;
}

.liturgy-color {
    display: inline-block;
    width: 0.75rem;
    height: 0.75rem;
    margin-right: 0.35rem;
    vertical-align: middle;
    border: 1px solid transparent;
    border-radius: 50%;
}

:deep(.bible-reference), :deep(.bible-reference div) {
    display: inline;
}

@media (max-width: 991.98px) {
    .day-header-cell {
        min-width: 9.75rem;
        padding: 0.35rem;
    }

    .day-header-panel {
        padding: 0.5rem 0.55rem;
    }

    .day-weekday,
    .day-date {
        font-size: 0.92rem;
    }

    .day-header-subtitle,
    .day-name {
        font-size: 0.72rem;
    }
}

</style>
