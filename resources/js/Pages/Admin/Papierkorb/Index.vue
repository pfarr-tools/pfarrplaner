<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarr.tools/pfarrplaner
  - @version git: $Id$
  -->

<template>
    <admin-layout title="Papierkorb">
        <dataset v-slot="{ ds }"
                 :ds-data="records"
                 ds-sort-by="deletedAt"
                 ds-sort-direction="desc"
                 :ds-filter-fields="filterFields"
                 :ds-search-in="['label', 'typeLabel', 'cityName', 'deletedAtText']">
            <div class="alert alert-light border mb-3">
                Einträge bleiben {{ retentionDays }} Tage im Papierkorb und werden danach täglich automatisch endgültig gelöscht.
            </div>
            <div class="row mb-3" :data-page-count="ds.dsPagecount">
                <div class="col-md-4 mb-2 mb-md-0">
                    <dataset-search ds-search-placeholder="Suchen..." ref="search" autofocus />
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select v-model="selectedType" class="form-select">
                        <option value="">Alle Typen</option>
                        <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select v-model="selectedCity" class="form-select">
                        <option value="">Alle Orte</option>
                        <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <dataset-show class="float-right" />
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover d-md-table">
                    <thead>
                    <tr>
                        <th>Typ</th>
                        <th>Eintrag</th>
                        <th>Ort</th>
                        <th>Gelöscht am</th>
                        <th>Gelöscht von</th>
                        <th></th>
                    </tr>
                    </thead>
                    <dataset-item tag="tbody">
                        <template #default="{ row }">
                            <tr>
                                <td>{{ row.typeLabel }}</td>
                                <td>
                                    <div class="fw-semibold">{{ row.label }}</div>
                                    <div v-if="row.warning" class="small text-warning">{{ row.warning }}</div>
                                </td>
                                <td>{{ row.cityName || ' - ' }}</td>
                                <td>{{ row.deletedAtText }}</td>
                                <td>{{ row.deletedByName || ' - ' }}</td>
                                <td class="text-end text-nowrap">
                                    <button v-if="row.canRestore"
                                            class="btn btn-sm btn-success"
                                            title="Eintrag wiederherstellen"
                                            @click="restoreRecord(row)">
                                        <span class="mdi mdi-delete-restore"></span>
                                    </button>
                                    <button v-if="row.canForceDelete"
                                            class="btn btn-sm btn-danger ms-1"
                                            title="Eintrag endgültig löschen"
                                            @click="forceDeleteRecord(row)">
                                        <span class="mdi mdi-delete-forever"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </dataset-item>
                </table>
            </div>
            <div v-if="records.length === 0" class="alert alert-light border">
                Der Papierkorb ist leer.
            </div>
            <div class="d-flex flex-md-row flex-column justify-content-between align-items-center border-top pt-2">
                <dataset-info class="mb-2 mb-md-0" />
                <dataset-pager />
            </div>
        </dataset>
    </admin-layout>
</template>

<script>
import {Dataset, DatasetItem, DatasetSearch} from 'vue-dataset';
import DatasetInfo from "../../../components/Ui/dataset/DatasetInfo.vue";
import DatasetPager from "../../../components/Ui/dataset/DatasetPager.vue";
import DatasetShow from "../../../components/Ui/dataset/DatasetShow.vue";

export default {
    name: 'Index',
    components: {
        Dataset,
        DatasetInfo,
        DatasetItem,
        DatasetPager,
        DatasetSearch,
        DatasetShow,
    },
    props: ['records', 'types', 'cities', 'retentionDays'],
    data() {
        return {
            selectedType: '',
            selectedCity: '',
        };
    },
    computed: {
        filterFields() {
            return {
                type: this.selectedType || '',
                cityName: this.selectedCity || '',
            };
        },
    },
    methods: {
        restoreRecord(row) {
            let text = 'Willst du diesen Eintrag wirklich wiederherstellen?';
            if (row.warning) {
                text += ' ' + row.warning;
            }
            if (!confirm(text)) return;
            this.$inertia.patch(row.restoreRoute);
        },
        forceDeleteRecord(row) {
            if (!confirm('Willst du diesen Eintrag wirklich endgültig und unwiderruflich löschen?')) return;
            this.$inertia.delete(row.deleteRoute);
        },
    },
}
</script>
