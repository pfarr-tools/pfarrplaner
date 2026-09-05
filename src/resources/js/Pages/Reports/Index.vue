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
    <admin-layout title="Ausgabeformat wählen">
        <div v-for="(groupReports, groupTitle) in reports">
            <h3 v-if="groupReports.length" class="mt-3">{{ groupTitle}}</h3>
            <div v-if="groupReports.length" class="row">
                <div v-for="report in groupReports" class="col-md-4 p-2 border-light report-card"
                @click="createReport(report)">
                    <div class="fw-semibold">
                        <span :class="report.icon"></span>
                        {{ report.title }}
                    </div>
                    <div>{{ report.description }}</div>
                </div>
            </div>
        </div>
    </admin-layout>
</template>

<script>

import Card from "../../components/Ui/cards/card.vue";
import CardBody from "../../components/Ui/cards/cardBody.vue";

export default {
    name: "Index",
    props: ['reports'],
    components: {
        Card, CardBody
    },
    methods: {
        createReport(report) {
            if (report.inertia) {
                this.$inertia.get(route('reports.setup', report.key));
            } else {
                window.location.href = route('reports.setup', report.key);
            }
        }
    }
}
</script>

<style scoped lang="scss">

@use '../../../sass/theme' as theme;

h3 {
    width: 100%;
    border-bottom: solid 1px lightgray;
}


.report-card {
    cursor: pointer;
}

.report-card:hover {
    background-color: theme.theme-color("secondary");
    color: white;
}

</style>
