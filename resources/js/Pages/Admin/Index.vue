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
    <admin-layout title="Administration">
        <div class="admin-index">
            <div v-for="(groupModules, groupName) in modules">
                <h3 v-if="groupModules.length" class="mt-3">{{ groupName }}</h3>
                <div v-if="groupModules.length" class="row">
                    <div v-for="module in groupModules"
                         class="col-md-4 p-2 module"
                         @click="$inertia.get(module.url)"
                         :title="'Klicken, um '+module.text+' zu verwalten'">
                        <div class="fw-semibold">
                        <span v-if="module.icon" :class="module.icon" class="me-1"></span>
                        {{ module.text }}
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </admin-layout>
</template>

<script>

import {Dataset, DatasetItem, DatasetSearch,} from 'vue-dataset';
import DatasetInfo from "../../components/Ui/dataset/DatasetInfo";
import DatasetShow from "../../components/Ui/dataset/DatasetShow";
import DatasetPager from "../../components/Ui/dataset/DatasetPager";


export default {
    name: "Index",
    props: ['modules'],
    components: {
        Dataset,
        DatasetItem,
        DatasetInfo,
        DatasetPager,
        DatasetSearch,
        DatasetShow
    },
}
</script>

<style scoped lang="scss">
@import '../../../sass/_variables.scss';

h3 {
    width: 100%;
    border-bottom: solid 1px lightgray;
}

.module {
    cursor: pointer;
}

.module:hover {
    background-color: map-get($theme-colors, "secondary");
    color: white;
}
</style>
