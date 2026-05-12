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
    <admin-layout title="Bestattung hinzufügen">
        <template #navbar-left>
            <button class="btn btn-primary" @click.prevent="createFuneral"
                    :disabled="!(funeral.city && funeral.location && funeral.date && funeral.name)">Erstellen
            </button>
        </template>
        <form-group label="Datum">
            <date-picker :config="myDatePickerConfig" v-model="funeral.date" iso-date @input="checkPools"/>
        </form-group>
        <form-selectize v-model="funeral.city" :options="cities" name="city"
                        id-key="id" title-key="name"
                        label="Kirchengemeinde"/>
        <location-select v-model="funeral.location" :locations="locations" name="location"
                         label="Ort" @set-location="setLocation"/>
        <form-input label="Verstorbene:r" placeholder="Nachname, Vorname" name="name"
                    v-model="funeral.name"/>
        <form-group :label="$page.props.labels.pastor" :key="pastorUpdated">
            <people-select :people="people" v-model="funeral.pastor" :city="{id: funeral.city}"/>
        </form-group>
        <div v-if="mastered.length">
            <hr/>
            <div v-for="masteredPool in mastered">
                <h3><span class="mdi mdi-pool"></span> Pool "{{ masteredPool.pool.name }}"</h3>
                <div class="text-sm text-muted">(Du bist Poolmaster:in von
                    {{ moment(masteredPool.start).format('DD.MM.YYYY') }}
                    bis {{ moment(masteredPool.end).format('DD.MM.YYYY') }})
                </div>

                <fake-table v-if="Object.keys(poolUsers).length > 0" :key="'pools_'+poolsUpdated"
                            :columns="[3,4,4, 1]" :headers="['Name', 'Vorhandene Dienste', 'Abwesenheiten', '']"
                            collapsed-header="Anwesende Kolleg:innen">
                    <div v-for="(user,userIndex) in masteredPool.pool.users" :key="'pool_user_'+userIndex">
                        <div class="row mb-3 p-1" :class="{'stripe-odd': (userIndex % 2 == 0)}" v-if="!poolUsers[user.id].absent">
                            <div class="col-md-3 font-bold">
                                {{ user.name }}
                                <div class="text-sm" v-if="user.phone">{{ user.phone }}</div>
                                <div class="text-sm" v-if="user.email"><a :href="'mailto:'+user.email">{{ user.email }}</a></div>
                            </div>
                            <div class="col-md-4">
                                <div v-if="poolUsers[user.id].services.length == 0">keine</div>
                                <ul v-else class="text-sm">
                                    <li v-for="service in poolUsers[user.id].services">
                                        {{ moment(service.date).format('DD.MM.YYYY, HH:mm') }} Uhr, {{ service.locationText }}<br />
                                        <b>{{ service.titleText }}</b>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <div v-if="poolUsers[user.id].absences.length == 0">keine</div>
                                <ul v-else class="text-sm">
                                    <li v-for="absence in poolUsers[user.id].absences">
                                        {{ moment(absence.from).format('DD.MM.YYYY') }}-{{ moment(absence.to).format('DD.MM.YYYY') }}<br />
                                        <b>{{ absence.reason }}</b>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-primary me-2" @click="setPastor(user)">Übernehmen</button>
                            </div>
                        </div>
                    </div>

                </fake-table>
            </div>
        </div>
    </admin-layout>
</template>

<script>
import FormSelectize from "../../components/Ui/forms/FormSelectize";
import FormGroup from "../../components/Ui/forms/FormGroup";
import LocationSelect from "../../components/Ui/elements/LocationSelect";
import FormInput from "../../components/Ui/forms/FormInput";
import PeopleSelect from "../../components/Ui/elements/PeopleSelect";
import FakeTable from "../../components/Ui/FakeTable.vue";

export default {
    name: "FuneralWizard",
    components: {FakeTable, PeopleSelect, FormInput, LocationSelect, FormGroup, FormSelectize},
    props: ['cities', 'locations', 'people', 'user', 'mastered'],
    data() {
        return {
            myDatePickerConfig: {
                format: 'DD.MM.YYYY HH:mm',
                locale: 'de',
            },
            funeral: {
                date: null,
                city: null,
                location: null,
                name: null,
                pastor: [this.$page.props.currentUser.data],
            },
            poolsUpdated: 0,
            pastorUpdated: 0,
            poolUsers: [],
            myCities: this.cities,
            originalCities: Object.assign(this.cities),
        }
    },
    methods: {
        setLocation(e) {
            this.funeral.location = e;
        },
        createFuneral() {
            this.$inertia.post(route('funerals.wizard.save'), this.funeral);
        },
        checkPools(date) {
            console.log('checkPools', date);
            this.$api()
                .get(route('api.pools.mastered', {user: this.user.id, date: date.substring(0, 10)}))
                .then(response => {
                    this.poolUsers = response.data.users;
                    this.poolsUpdated++;
                });
        },
        setPastor(user) {
            this.funeral.pastor = [user];
            this.pastorUpdated++;
        }
    }
}
</script>

<style scoped>
    .text-sm {
        font-size: .8em;
    }
</style>
