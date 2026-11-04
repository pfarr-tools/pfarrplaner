<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -->

<template>
    <model-index-page title="Teams" :records="teams" :can-create="canCreate"
                      create-label="Neues Team" create-route-name="admin.teams.create"
                      model-label="Team">
        <template #table-header-additional-columns>
            <th>Kirchengemeinde</th>
            <th>Mitglieder</th>
        </template>
        <template #table-row-additional-columns="{ row }">
            <td>{{ row.city?.name }}</td>
            <td>
                <span v-for="user in row.users" :key="user.id" class="badge bg-light me-1">
                    {{ user.name }}
                </span>
            </td>
        </template>
    </model-index-page>
</template>

<script>
import ModelIndexPage from "../../components/Admin/ModelIndexPage.vue";

export default {
    name: "Index",
    components: {
        ModelIndexPage,
    },
    props: ['teams', 'canCreate'],
}
</script>
