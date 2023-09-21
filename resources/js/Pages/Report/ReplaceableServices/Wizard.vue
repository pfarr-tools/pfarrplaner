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
    <admin-layout :title="'Vertretungen für '+user.name+' bearbeiten'">
        <template slot="after-flash">
            <div v-if="saving" class="alert alert-warning">Änderungen werden gespeichert ... <span
                class="mdi mdi-spin mdi-loading"></span></div>
            <div v-if="saved" class="alert alert-success"><span class="mdi mdi-check"></span> Änderungen wurden
                automatisch gespeichert.
            </div>
        </template>
        <dataset v-slot="{ ds }" ref="ds"
                 :ds-data="myServices"
                 ds-sort-by="date">
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover d-md-table">
                            <thead>
                            <tr>
                                <th>Gottesdienst</th>
                                <th></th>
                            </tr>
                            </thead>
                            <dataset-item tag="tbody">
                                <template #default="{ row, rowIndex }">
                                    <tr>
                                        <td>
                                            <div class="pb-1 text-bold">
                                                {{ row.service.titleText }}
                                            </div>
                                            {{ moment(row.service.date).format('DD.MM.YYYY') }}, {{
                                                row.service.timeText
                                            }} <br/>{{ row.service.locationText }}
                                        </td>
                                        <td>
                                            <people-select :label="ministryText(row.category)" :people="people"
                                                           v-model="row.service.ministries[row.category]"
                                                           @input="updateService(row.service, row.category)"
                                                           :teams="teams" :include-teams-from-city="row.service.city"
                                                           :city="row.service.city"/>
                                        </td>
                                        <td>
                                            <nav-button class="btn-sm" type="primary" icon="mdi mdi-pencil"
                                                        force-no-text force-icon title="Gottesdienst bearbeiten"
                                                        :href="route('service.edit', row.service)"></nav-button>
                                            <nav-button class="btn-sm" type="danger" icon="mdi mdi-delete"
                                                        force-no-text force-icon title="Gottesdienst löschen"
                                                        @click="deleteService(row, rowIndex)"></nav-button>
                                        </td>
                                    </tr>
                                </template>
                                <template #noDataFound>
                                    <div class="col-md-12 pt-2">
                                        <p class="text-center">No results found</p>
                                    </div>
                                </template>
                            </dataset-item>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-md-row flex-column justify-content-between align-items-center border-top pt-2">
                <dataset-info class="mb-2 mb-md-0"/>
                <dataset-show class="mb-2 mb-md-0" :ds-show-entries="showEntries"/>
                <dataset-pager/>
            </div>
        </dataset>
    </admin-layout>
</template>

import FormGroup from "../../../components/Ui/forms/FormGroup";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import NavButton from "../../../components/Ui/buttons/NavButton";
import LocationSelect from "../../../components/Ui/elements/LocationSelect";
import FormInput from "../../../components/Ui/forms/FormInput";
import {Dataset, DatasetItem, DatasetSearch} from "vue-dataset";
import DatasetInfo from "../../../components/Ui/dataset/DatasetInfo";
import DatasetPager from "../../../components/Ui/dataset/DatasetPager";
import DatasetShow from "../../../components/Ui/dataset/DatasetShow";
import FormBibleReferenceInput from "../../../components/Ui/forms/FormBibleReferenceInput";
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker";


<script>
import FormDatePicker from "../../../components/Ui/forms/FormDatePicker.vue";
import FormBibleReferenceInput from "../../../components/Ui/forms/FormBibleReferenceInput.vue";
import FormInput from "../../../components/Ui/forms/FormInput.vue";
import LocationSelect from "../../../components/Ui/elements/LocationSelect.vue";
import NavButton from "../../../components/Ui/buttons/NavButton.vue";
import FormSelectize from "../../../components/Ui/forms/FormSelectize.vue";
import FormGroup from "../../../components/Ui/forms/FormGroup.vue";
import {Dataset, DatasetItem, DatasetSearch} from "vue-dataset";
import DatasetInfo from "../../../components/Ui/dataset/DatasetInfo.vue";
import DatasetPager from "../../../components/Ui/dataset/DatasetPager.vue";
import DatasetShow from "../../../components/Ui/dataset/DatasetShow.vue";
import PeopleSelect from "../../../components/Ui/elements/PeopleSelect.vue";

export default {
    name: "Wizard",
    props: ['user', 'services', 'ministries', 'people', 'teams'],
    components: {
        PeopleSelect,
        FormDatePicker,
        FormBibleReferenceInput,
        FormInput, LocationSelect, NavButton, FormSelectize, FormGroup,
        Dataset,
        DatasetItem,
        DatasetInfo,
        DatasetPager,
        DatasetSearch,
        DatasetShow
    },
    data() {
        let myMembers = {};
        let myServices = this.services;
        let basicMinistryKeys = {P: 'pastors', O: 'organists', M: 'sacristans', A: 'otherParticipants'};
        for (const myServiceId in myServices) {
            myServices[myServiceId]['service']['ministries'] = {};
            myServices[myServiceId]['service']['ministries'][myServices[myServiceId].category] = this.getMembers(myServices[myServiceId]);
        }

        return {
            showEntries: 25,
            basicMinistryKeys,
            saving: false,
            saved: false,
            myServices,
            apiToken: this.$page.props.currentUser.data.api_token,
        }
    },
    methods: {
        ministryText(category) {
            return this.ministries[category] || category;
        },
        getMembers(row) {
            switch (row.category) {
                case 'P':
                    return row.service.pastors || [];
                case 'O':
                    return row.service.organists || [];
                case 'M':
                    return row.service.sacristans || [];
            }
            return row.service.ministriesByCategory[row.category] || [];
        },
        updateService(service, category) {
            axios.post(route('inputs.save', 'planning'), service).then(response => {
                this.saving = false;
                this.saved = true;
                this.$forceUpdate();
            });
        },
        deleteService(row, rowIndex) {
            if (!confirm('Willst du den gewählten Gottesdienst wirklich komplett löschen?')) return;
            axios.delete(route('api.service.destroy', {
                service: row.service.slug,
                api_token: this.apiToken,
            })).then(response => {
                console.log('rowIndex', rowIndex);
                this.myServices.splice(rowIndex, 1);
                this.$forceUpdate();
            });
        }
    }
}
</script>

<style scoped>

</style>
