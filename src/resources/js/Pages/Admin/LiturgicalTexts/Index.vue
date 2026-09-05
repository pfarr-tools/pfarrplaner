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
    <admin-layout enable-control-sidebar="true" title="Liturgische Texte">
        <template v-slot:navbar-left>
            <nav-button :href="route('admin.text.create')" type="success">Neuen Text anlegen</nav-button>
        </template>
        <dataset v-slot="{ ds }"
                 :ds-data="texts"
                 ds-sort-by="title"
                 :ds-search-in="['title', 'code', 'quelle']">
            <dataset-search ds-search-placeholder="Suchen..." ref="search" autofocus/>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover d-md-table">
                            <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Code</th>
                                <th></th>
                            </tr>
                            </thead>
                            <dataset-item tag="tbody">
                                <template #default="{ row, rowIndex }">
                                    <tr>
                                        <td><b>{{ row.title }}</b><div class="text-muted text-small">{{ row.source }}</div></td>
                                        <td>{{ row.agenda_code }}</td>
                                        <td class="text-end">
                                            <nav-button type="light" icon="mdi mdi-pencil" title="Text bearbeiten"
                                                        @click="editText(row)" force-icon force-no-text/>
                                            <nav-button type="danger" icon="mdi mdi-delete"
                                                        title="Text löschen"
                                                        @click="deleteText(row)" force-icon force-no-text/>
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
    </admin-layout>
</template>

<script>
import {Dataset, DatasetItem, DatasetSearch} from "vue-dataset";
import DatasetInfo from "../../../components/Ui/dataset/DatasetInfo";
import DatasetPager from "../../../components/Ui/dataset/DatasetPager";
import DatasetShow from "../../../components/Ui/dataset/DatasetShow";
import NavButton from "../../../components/Ui/buttons/NavButton.vue";

export default {
    components: {
        NavButton, Dataset,
        DatasetItem,
        DatasetInfo,
        DatasetPager,
        DatasetSearch,
        DatasetShow
    },
    props: ['texts'],
    data() {
        return {
            selectedText: null,
            importMode: false,
            importText: '',
        }
    },
    methods: {
        editText(text) {
            this.$inertia.get(route('admin.text.edit', text.id));
        },
        deleteText(text) {
            if (!confirm('Willst du diesen Text wirklich unwiderruflich löschen?')) return;
            this.$inertia.delete(route('admin.text.destroy', text.id));
        },
    }
}
</script>
<style scoped>
.text-small {
    font-size: .7em;
}
</style>
