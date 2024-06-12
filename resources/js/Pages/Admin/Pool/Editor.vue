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
    <admin-layout :title="getTitle()">
        <template slot="navbar-left">
            <button class="btn btn-primary" title="Speichern" @click="savePool">
                <span class="d-inline d-md-none mdi mdi-content-save"></span> <span class="d-none d-md-inline">Speichern</span>
            </button>
        </template>
        <form-input label="Bezeichnung des Pools" v-model="myPool.name" autofocus />
        <form-selectize label="Zugehörige Kirchengemeinden" v-model="myPool.cities" :options="cities" multiple />
        <people-select label="Zugehörige Personen" v-model="myPool.users" :people="people" multiple />
        <hr />
        <p>In besonderen Fällen können für einen Pool feste Kontaktinformationen (z.B. Dekanatamt) hinterlegt werden.
            Diese sind dann nicht mit einem Benutzer verknüpft.</p>
        <form-input label="Ansprechperson" v-model="myPool.contact" />
        <form-input label="Institution/Amt" v-model="myPool.office" />
        <form-input label="Telefon" v-model="myPool.phone" />
        <form-input label="E-Mailadresse" v-model="myPool.email" />
    </admin-layout>
</template>

<script>
import FormInput from "../../../components/Ui/forms/FormInput.vue";
import FormSelectize from "../../../components/Ui/forms/FormSelectize.vue";
import PeopleSelect from "../../../components/Ui/elements/PeopleSelect.vue";

export default {
    name: "Editor",
    components: {PeopleSelect, FormSelectize, FormInput},
    props: ['pool', 'cities', 'people'],
    data() {
        return {
            myPool: this.pool ? {
                ...this.pool,
                cities: this.pool.cities.map(city => city.id),
                users: this.pool.users.map(user => user.id),
            } : {
                id: null,
                name: '',
                contact: '',
                office: '',
                phone: '',
                email: '',
                cities: [],
                users: [],
            }
        }
    },
    methods: {
        getTitle() {
            if (!this.myPool.id) return 'Neuen Pool anlegen';
            return 'Pool "' + this.myPool.name + '" bearbeiten';
        },
        savePool() {
            if (!this.myPool.id) {
                this.$inertia.post(route('admin.pools.store'), {
                    ...this.myPool,
                    users: this.myPool.users.map(user => isNaN(user) ? user.id : user)
                });
            } else {
                this.$inertia.patch(route('admin.pool.update', {modelId: this.myPool.id}), {
                    ...this.myPool,
                    users: this.myPool.users.map(user => isNaN(user) ? user.id : user)
                });
            }
        },
    },
}
</script>

<style scoped>

</style>
