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
    <admin-layout title="Doppelte Personeneinträge finden">
        <template slot="navbar-left">
            <save-button @click="fixDuplicates" />
        </template>

        <div v-for="person in people" :key="person.id" class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <!-- Keeper: editable name fields + info -->
                    <div class="col-md-4 border-end">
                        <div class="mb-2 text-muted small fw-semibold">
                            <span :class="person.isOfficialUser ? 'mdi mdi-account-check text-primary' : 'mdi mdi-account-question-outline text-secondary'" class="me-1"></span>
                            Behalten
                        </div>
                        <div class="mb-2">
                            <input class="form-control form-control-sm mb-1"
                                   v-model="person.editTitle"
                                   placeholder="Titel (z.B. Pfarrer, Dr.)" />
                            <div class="input-group input-group-sm mb-1">
                                <input class="form-control"
                                       v-model="person.editFirstName"
                                       placeholder="Vorname" />
                                <input class="form-control"
                                       v-model="person.editLastName"
                                       placeholder="Nachname" />
                            </div>
                        </div>
                        <small class="text-muted">
                            <div v-if="person.email" class="mb-1">{{ person.email }}</div>
                            <div v-if="person.home_cities && person.home_cities.length">
                                <span v-for="city in person.home_cities" :key="city.id" class="badge bg-dark me-1 mb-1">{{ city.name }}</span>
                            </div>
                            <div v-if="person.city_scopes && person.city_scopes.length">
                                <span v-for="city in person.city_scopes" :key="city.id" class="badge bg-secondary me-1 mb-1">{{ city.name }}</span>
                            </div>
                        </small>
                    </div>

                    <!-- Duplicates with checkboxes -->
                    <div class="col-md-8">
                        <div class="mb-2 text-muted small fw-semibold">Zusammenführen mit</div>
                        <div v-for="dup in person.duplicates" :key="dup.id"
                             class="d-flex align-items-start mb-2 p-2 rounded"
                             :class="dup.selected ? 'bg-light border' : 'border border-dashed text-muted'">
                            <div class="me-2 mt-1">
                                <input type="checkbox" :id="'dup-'+dup.id" v-model="dup.selected" />
                            </div>
                            <label :for="'dup-'+dup.id" class="mb-0 flex-grow-1" style="cursor:pointer">
                                <span :class="dup.isOfficialUser ? 'mdi mdi-account-check text-primary' : 'mdi mdi-account-question-outline text-secondary'" class="me-1"></span>
                                <strong>{{ dup.fullNameText || dup.name }}</strong>
                                <small class="d-block">
                                    <span v-if="dup.email" class="me-2">{{ dup.email }}</span>
                                    <span v-for="city in dup.home_cities" :key="city.id" class="badge bg-dark me-1">{{ city.name }}</span>
                                    <span v-for="city in dup.city_scopes" :key="city.id" class="badge bg-secondary me-1">{{ city.name }}</span>
                                </small>
                            </label>
                        </div>
                        <form-selectize
                            :key="'add-dup-'+person.id+'-'+person.duplicates.length"
                            :options="availableFor(person)"
                            placeholder="Weiteres Duplikat hinzufügen..."
                            @input="addDuplicateToPerson(person, $event)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <hr />
        <form-selectize
            :key="'add-to-list-'+people.length"
            label="Zur Liste hinzufügen"
            :options="availableUsers"
            @input="addUserToList"
        />
    </admin-layout>
</template>

<script>

import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import SaveButton from "../../../components/Ui/buttons/SaveButton";

export default {
    name: "DuplicatesWizard",
    props: { possibleDuplicates: Array, withoutDuplicates: Array },
    components: { SaveButton, FormSelectize },
    data() {
        const people = this.possibleDuplicates.map(person => ({
            ...person,
            editTitle: person.title || '',
            editFirstName: person.first_name || '',
            editLastName: person.last_name || '',
            duplicates: Object.values(person.duplicates).map(d => ({ ...d, selected: true })),
        }));
        return { people };
    },
    computed: {
        usedIds() {
            const ids = new Set(this.people.map(p => p.id));
            this.people.forEach(p => p.duplicates.forEach(d => ids.add(d.id)));
            return ids;
        },
        availableUsers() {
            return this.withoutDuplicates.filter(u => !this.usedIds.has(u.id));
        },
    },
    methods: {
        availableFor() {
            return this.availableUsers;
        },
        addDuplicateToPerson(person, id) {
            const user = this.withoutDuplicates.find(u => u.id == id);
            if (user && !this.usedIds.has(user.id)) {
                person.duplicates.push({
                    ...user,
                    selected: true,
                    fullNameText: user.fullNameText || user.name,
                    duplicates: [],
                });
            }
        },
        addUserToList(id) {
            const user = this.withoutDuplicates.find(u => u.id == id);
            if (user && !this.usedIds.has(user.id)) {
                this.people.push({
                    ...user,
                    editTitle: user.title || '',
                    editFirstName: user.first_name || '',
                    editLastName: user.last_name || '',
                    duplicates: [],
                });
            }
        },
        fixDuplicates() {
            const groups = [];
            this.people.forEach(person => {
                const selectedSources = person.duplicates
                    .filter(d => d.selected)
                    .map(d => d.id);
                if (selectedSources.length > 0) {
                    groups.push({
                        target_id: person.id,
                        source_ids: selectedSources,
                        name_update: {
                            title: person.editTitle,
                            first_name: person.editFirstName,
                            last_name: person.editLastName,
                        },
                    });
                }
            });

            this.$inertia.post(route('users.duplicates.fix'), { groups }, { preserveState: false });
        },
    },
}
</script>

<style scoped>
.border-dashed {
    border-style: dashed !important;
}
</style>
