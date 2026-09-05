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
    <model-index-page title="Pools" :records="pools" :can-create="canCreate"
                      create-label="Neuer Pool" create-route-name="admin.pools.create"
                      model-label="Pool">
        <template v-slot:table-header-additional-columns>
            <th>Kirchengemeinden</th>
            <th>Teilnehmer</th>
        </template>
        <template v-slot:table-row-additional-columns="data">
            <td><span class="badge bg-light me-1" v-for="city in data.row.cities">{{ city.name }}</span></td>
            <td><span class="badge bg-light me-1" v-for="user in data.row.users">{{ user.name }}</span></td>

        </template>
    </model-index-page>
</template>

<script>
import ModelIndexPage from "../../../components/Admin/ModelIndexPage.vue";


export default {
    name: "Index",
    components: {
        ModelIndexPage,
    },
    props: ['pools', 'canCreate'],
    methods: {
        logData(data) {
            console.log(data);
            return '';
        }
    },
}
</script>

<style scoped>
</style>
