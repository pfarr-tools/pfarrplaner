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
import NavButton from "../Ui/buttons/NavButton.vue";
import {Dataset, DatasetItem, DatasetSearch} from "vue-dataset";
import DatasetInfo from "../Ui/dataset/DatasetInfo.vue";
import DatasetPager from "../Ui/dataset/DatasetPager.vue";
import DatasetShow from "../Ui/dataset/DatasetShow.vue";

export default {
    name: "ModelIndexList",
    components: {
        NavButton,
        Dataset,
        DatasetItem,
        DatasetInfo,
        DatasetPager,
        DatasetSearch,
        DatasetShow
    },
    props: ['title', 'records', 'canCreate', 'createLabel', 'createRoute', 'modelLabel', 'editRouteName', 'deleteRouteName'],
    methods: {
        deleteRecord(row, rowKey) {
            if (!confirm('Willst du diesen Eintrag wirklich unwiderruflich löschen?')) return;
            this.$api().delete(route(this.deleteRouteName, {modelId: row.id})).then(response => {
                delete(this.records[rowKey]);
            });
        }
    }
}
</script>

<template>
    <div class="model-index-list">
        <div>
            <h3>
                {{ title }}
                <div style="float: right">
                    <nav-button v-if="canCreate" icon="plus" type="success" :href="createRoute">{{ createLabel }}</nav-button>
                </div>
            </h3>
        </div>
        <dataset v-slot="{ ds }"
                 :ds-data="records"
                 ds-sort-by="name"
                 :ds-search-in="['name']">
            <div class="row mb-3" :data-page-count="ds.dsPagecount">
                <div class="col-md-6 mb-2 mb-md-0">
                    <dataset-search ds-search-placeholder="Suchen..." ref="search" autofocus/>
                </div>
                <div class="col-md-5 text-end">
                    <dataset-show class="float-right"/>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover d-md-table">
                            <thead>
                            <tr>
                                <slot name="table-header-label-column">
                                    <th>{{ modelLabel }}</th>
                                </slot>
                                <slot name="table-header-additional-columns"/>
                                <th></th>
                            </tr>
                            </thead>
                            <dataset-item tag="tbody">
                                <template #default="{ row, rowIndex }">
                                    <tr>
                                        <slot name="table-row-label-column" v-bind:row="row">
                                            <td>{{ row.label }}</td>
                                        </slot>
                                        <slot name="table-row-additional-columns" v-bind:row="row"/>
                                        <td class="text-end">
                                            <inertia-link
                                                          class="btn btn-sm btn-primary"
                                                          title="Eintrag bearbeiten"
                                                          :href="route(editRouteName, {modelId: row.id})">
                                                <span class="mdi mdi-pencil"></span>
                                            </inertia-link>
                                            <slot name="table-row-action-buttons" v-bind:row="row"/>
                                            <button class="btn  btn-sm btn-danger ms-1" title="Eintrag löschen"
                                                    @click="deleteRecord(row, rowIndex)">
                                                <span class="mdi mdi-delete"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </dataset-item>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-md-row flex-column justify-content-between align-items-center border-top pt-2">
                <dataset-info class="mb-2 mb-md-0"/>
                <dataset-pager/>
            </div>
        </dataset>
    </div>
</template>

<style scoped>

</style>
