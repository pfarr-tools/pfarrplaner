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
    <div class="admin-tab">
        <div class="btn-group" role="group" aria-label="Bereiche">
            <inertia-link v-for="module,moduleKey in modules" :href="module.url" class="btn btn-light" :key="moduleKey">
                <span :class="module.icon" class="mr-1"></span>
                {{ module.text }}
            </inertia-link>
        </div>
        <hr class="my-3" />
        <dataset v-slot="{ ds }"
                 :ds-data="people"
                 ds-sort-by="text"
                 :ds-search-in="['name']">
            <div class="row mb-3" :data-page-count="ds.dsPagecount">
                <div class="col-md-6 mb-2 mb-md-0">
                    <dataset-search ds-search-placeholder="Suchen..." ref="search" autofocus />
                </div>
                <div class="col-md-5 text-right">
                    <dataset-show class="float-right" />
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover d-md-table">
                            <thead>
                            <tr>
                                <th>Benutzer</th>
                                <th></th>
                            </tr>
                            </thead>
                            <dataset-item tag="tbody">
                                <template #default="{ row, rowIndex }">
                                    <tr v-if="row.isOfficialUser">
                                        <td>
                                            <span class="mdi mdi-account mr-1"></span>
                                            {{ row.name }}
                                        </td>
                                        <td style="text-align: right">
                                            <inertia-link class="btn btn-sm btn-primary mt-md-2" v-if="canEdit(row)"
                                                          :href="route('user.edit', {user: row.id})"><span class="mdi mdi-pencil"></span></inertia-link>
                                            <a class="btn btn-sm btn-light mt-md-2" v-if="canEdit(row)"
                                               :href="route('user.switch', {user: row.id})"><span class="mdi mdi-account-switch"></span></a>
                                            <nav-button type="light" icon="mdi mdi-lock-reset" title="Passwort zurücksetzen"
                                                        class="btn-sm mt-md-2" v-if="canEdit(row)"
                                                        force-icon force-no-text @click="resetPassword(row)"/>
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

<script>
import FormSelectize from "../Ui/forms/FormSelectize";
import FakeTable from "../Ui/FakeTable";
import CheckedProcessItem from "../Ui/elements/CheckedProcessItem";
import NavButton from "../Ui/buttons/NavButton";
import {Dataset, DatasetItem, DatasetSearch,} from 'vue-dataset';
import DatasetInfo from "../../components/Ui/dataset/DatasetInfo";
import DatasetShow from "../../components/Ui/dataset/DatasetShow";
import DatasetPager from "../../components/Ui/dataset/DatasetPager";


export default {
    name: "AdminTab",
    components: {CheckedProcessItem, FakeTable, FormSelectize, NavButton, Dataset, DatasetItem, DatasetInfo, DatasetPager, DatasetSearch, DatasetShow},
    props: ['modules', 'people'],
    data() {
        return {
            isAdmin: this.$page.props.currentUser.data.isAdmin,
            currentUser: this.$page.props.currentUser.data,
            selectedPerson: null,
        }
    },
    computed: {
        selectedUser() {
            if (!this.selectedPerson) return null;
            return this.people.filter(person => person.id == this.selectedPerson)[0];
        }
    },
    methods: {
        canEdit(user) {
            if (user.isAdmin) {
                if (user.name == 'Admin') return (this.currentUser.name == 'Admin');
                return (this.currentUser.isAdmin);
            }
            return this.currentUser.isAdmin
                || this.currentUser.isLocalAdmin
                || this.hasPermission('benutzer-bearbeiten');
        },
        resetPassword(user) {
            if (confirm('Willst du das Passwort für '+user.name+' wirklich zurücksetzen? '
                +(user.first_name || user.name)+' erhält dann eine E-Mail mit neuen Zugangsdaten. Das bisherige Passwort '
                +'ist dann ab sofort ungültig.')) {
                this.$inertia.post(route('user.password.reset', user.id), { preserveState: false });
            }
        }
    }
}
</script>

<style scoped>

</style>
